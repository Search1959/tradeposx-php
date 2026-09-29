<?php
class ProductController {
    private Product $model;
    private Supplier $supplierModel;
    private InventoryTransaction $invModel;

    public function __construct() {
        $this->model = new Product();
        $this->supplierModel = new Supplier();
        $this->invModel = new InventoryTransaction();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $search = trim($_GET['q'] ?? '');
        $products = $this->model->allForOrg($org['id'], $search ?: null);
        $pageTitle = 'Products';
        require __DIR__ . '/../views/trade/products/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $product = [];
        $suppliers = $this->supplierModel->allForOrg($org['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $product = $_POST;
            } else {
                $productId = $this->model->create($org['id'], $_POST);
                $openingStock = (float) ($_POST['current_stock'] ?? 0);
                if ($openingStock > 0) {
                    $this->model->adjustStock($org['id'], $productId, $openingStock);
                    $this->invModel->record($org['id'], $productId, 'INITIAL_STOCK', $openingStock, null, 'Opening stock');
                }
                logActivity($org['id'], 'product_create', 'product', $productId, $name);
                redirect($org['slug'] . '/products');
                return;
            }
        }

        $pageTitle = 'New Product';
        require __DIR__ . '/../views/trade/products/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $product = $this->model->find($org['id'], (int) $id);
        if (!$product) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $suppliers = $this->supplierModel->allForOrg($org['id']);

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $product = array_merge($product, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/products');
                return;
            }
        }

        $pageTitle = 'Edit Product';
        require __DIR__ . '/../views/trade/products/form.php';
    }

    public function ledgerAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $product = $this->model->find($org['id'], (int) $id);
        if (!$product) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $transactions = $this->invModel->forProduct($org['id'], (int) $id);
        $pageTitle = 'Stock Ledger';
        require __DIR__ . '/../views/trade/products/ledger.php';
    }

    public function adjustStockAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $product = $this->model->find($org['id'], (int) $id);
        if (!$product) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $delta = (float) ($_POST['delta'] ?? 0);
        $reason = $_POST['reason'] ?? 'Manual adjustment';
        if ($delta !== 0.0) {
            $this->model->adjustStock($org['id'], (int) $id, $delta);
            $this->invModel->record($org['id'], (int) $id, $delta > 0 ? 'MANUAL_ADD' : 'MANUAL_REDUCE', abs($delta), null, $reason);
        }
        redirect($org['slug'] . '/products');
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/products');
    }

    /** A ready-to-fill CSV — the header row IS the contract for bulkImportAction
     * below, so keep the two in sync if this ever changes. */
    public function templateAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="product_import_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['name','sku','barcode','category','brand','unit','purchase_price','selling_price','mrp','current_stock','min_stock','gst_percent','hsn']);
        fputcsv($out, ['Aashirvaad Atta 10kg','KIR-100','8901030850069','Atta & Flours','Aashirvaad','pkt','380','420','445','25','8','0','']);
        fclose($out);
    }

    /** Matches each row to an existing product by barcode then SKU (updates it,
     * merging only the columns the row actually filled in) or creates a new one
     * (the row's current_stock becomes that new product's opening stock, same
     * as the single-product form — an update row's current_stock is ignored,
     * since bulk import isn't the place to silently overwrite live stock levels;
     * use Purchases or the stock ledger's manual adjustment for that). */
    public function bulkImportAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv_file']['tmp_name'])) {
            CsrfHelper::validate();
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if (!$handle) {
                $error = 'Could not read the uploaded file.';
            } else {
                $header = fgetcsv($handle);
                if (!$header) {
                    $error = 'File appears to be empty.';
                } else {
                    $header = array_map(fn($h) => strtolower(trim($h)), $header);
                    $created = 0; $updated = 0; $skipped = [];
                    $rowNum = 1;
                    while (($rawRow = fgetcsv($handle)) !== false) {
                        $rowNum++;
                        if (count(array_filter($rawRow, fn($v) => trim((string)$v) !== '')) === 0) continue; // blank line
                        $row = [];
                        foreach ($header as $i => $col) { $row[$col] = trim($rawRow[$i] ?? ''); }

                        $name = $row['name'] ?? '';
                        if ($name === '') { $skipped[] = "Row {$rowNum}: missing name"; continue; }

                        $data = [
                            'name' => $name, 'sku' => $row['sku'] ?? null, 'barcode' => $row['barcode'] ?? null,
                            'category' => $row['category'] ?: 'Other', 'brand' => $row['brand'] ?? null,
                            'unit' => $row['unit'] ?: 'pcs', 'purchase_price' => (float) ($row['purchase_price'] ?? 0),
                            'selling_price' => (float) ($row['selling_price'] ?? 0), 'mrp' => (float) ($row['mrp'] ?? 0),
                            'min_stock' => (float) ($row['min_stock'] ?? 0), 'gst_percent' => (float) ($row['gst_percent'] ?? 0),
                            'hsn' => $row['hsn'] ?? null,
                        ];

                        $existing = $this->model->findForImportMatch($org['id'], $data['barcode'] ?: null, $data['sku'] ?: null);
                        if ($existing) {
                            $this->model->updateFromImportMerge($org['id'], (int) $existing['id'], $existing, $data);
                            $updated++;
                        } else {
                            $productId = $this->model->create($org['id'], $data);
                            $openingStock = (float) ($row['current_stock'] ?? 0);
                            if ($openingStock > 0) {
                                $this->model->adjustStock($org['id'], $productId, $openingStock);
                                $this->invModel->record($org['id'], $productId, 'INITIAL_STOCK', $openingStock, null, 'Bulk import opening stock');
                            }
                            $created++;
                        }
                    }
                    fclose($handle);
                    $result = ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
                    logActivity($org['id'], 'product_bulk_import', 'product', 0, "created={$created} updated={$updated}");
                }
            }
        }

        $pageTitle = 'Bulk Import Products';
        require __DIR__ . '/../views/trade/products/bulk-import.php';
    }
}
