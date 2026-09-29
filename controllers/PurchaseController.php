<?php
class PurchaseController {
    private Purchase $model;
    private Product $productModel;
    private Supplier $supplierModel;

    public function __construct() {
        $this->model = new Purchase();
        $this->productModel = new Product();
        $this->supplierModel = new Supplier();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $purchases = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $pageTitle = 'Purchases';
        require __DIR__ . '/../views/trade/purchases/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $lines = json_decode($_POST['items_json'] ?? '[]', true) ?: [];
            $items = [];
            foreach ($lines as $line) {
                $product = $this->productModel->find($org['id'], (int) $line['product_id']);
                if (!$product) continue;
                $qty = (float) $line['quantity'];
                $rate = (float) $line['purchase_price'];
                $lineTotal = round($qty * $rate, 2);
                $gstPercent = (float) ($line['gst_percent'] ?? $product['gst_percent']);
                $gstAmount = round($lineTotal * $gstPercent / 100, 2);
                $items[] = [
                    'product_id' => $product['id'], 'product_name' => $product['name'], 'unit' => $product['unit'],
                    'quantity' => $qty, 'purchase_price' => $rate, 'gst_percent' => $gstPercent, 'gst_amount' => $gstAmount,
                    'total_price' => $lineTotal, 'hsn' => $product['hsn'] ?: ($line['hsn'] ?? null),
                    'batch_number' => $line['batch_number'] ?? null, 'expiry_date' => $line['expiry_date'] ?? null,
                ];
            }

            if (empty($items)) {
                $error = 'At least one line item is required.';
            } else {
                $supplier = $_POST['supplier_id'] ? $this->supplierModel->find($org['id'], (int) $_POST['supplier_id']) : null;
                $result = $this->model->createPurchase($org['id'], [
                    'supplier_id'     => $_POST['supplier_id'] ?: null,
                    'supplier_name'   => $supplier['name'] ?? null,
                    'invoice_number'  => $_POST['invoice_number'] ?? null,
                    'purchase_date'   => $_POST['purchase_date'] ?? null,
                    'invoice_date'    => $_POST['invoice_date'] ?? null,
                    'discount_amount' => $_POST['discount_amount'] ?? 0,
                    'freight_charges' => $_POST['freight_charges'] ?? 0,
                    'paid_amount'     => $_POST['paid_amount'] ?? null,
                    'payment_method'  => $_POST['payment_method'] ?? 'CASH',
                    'notes'           => $_POST['notes'] ?? null,
                ], $items);

                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    redirect($org['slug'] . '/purchases/view/' . $result['id']);
                    return;
                }
            }
        }

        $products = $this->productModel->allForOrg($org['id']);
        $suppliers = $this->supplierModel->allForOrg($org['id']);
        $pageTitle = 'New Purchase';
        require __DIR__ . '/../views/trade/purchases/form.php';
    }

    /** GET shows the upload form; POST runs the uploaded photo/PDF through
     * Gemini and, on success, renders an editable review screen (nothing is
     * saved yet — the owner checks/corrects the AI's read before committing). */
    public function scanAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $scanned = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            if (empty($_FILES['bill_file']['tmp_name']) || $_FILES['bill_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Please choose a photo or PDF of the bill.';
            } else {
                $mimeType = mime_content_type($_FILES['bill_file']['tmp_name']);
                if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
                    $error = 'Unsupported file type — upload a JPG, PNG, WEBP, or PDF.';
                } else {
                    $content = file_get_contents($_FILES['bill_file']['tmp_name']);
                    $result = GeminiHelper::scanPurchaseBill($content, $mimeType);
                    if (isset($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $scanned = $result;
                    }
                }
            }
        }

        $suppliers = $this->supplierModel->allForOrg($org['id']);
        $pageTitle = 'Scan Purchase Bill';
        require __DIR__ . '/../views/trade/purchases/scan.php';
    }

    /** Commits the (possibly hand-corrected) reviewed scan — resolves each line
     * to a product by exact name match, creating a new one (category 'Other')
     * for anything not already in the catalog, then saves exactly like a
     * manually-entered purchase. */
    public function saveScannedAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $lines = json_decode($_POST['items_json'] ?? '[]', true) ?: [];
            $items = [];
            foreach ($lines as $line) {
                $name = trim($line['name'] ?? '');
                if ($name === '') continue;
                $qty = (float) ($line['quantity'] ?? 0);
                $rate = (float) ($line['purchase_price'] ?? 0);
                if ($qty <= 0) continue;

                $productId = $this->productModel->findOrCreateByName($org['id'], $name, [
                    'unit' => $line['unit'] ?: 'pcs', 'purchase_price' => $rate,
                    'selling_price' => $rate, 'mrp' => $rate, 'gst_percent' => $line['gst_percent'] ?? 0,
                    'hsn' => $line['hsn'] ?? null,
                ]);
                $lineTotal = round($qty * $rate, 2);
                $gstPercent = (float) ($line['gst_percent'] ?? 0);
                $items[] = [
                    'product_id' => $productId, 'product_name' => $name, 'unit' => $line['unit'] ?? null,
                    'quantity' => $qty, 'purchase_price' => $rate, 'gst_percent' => $gstPercent,
                    'gst_amount' => round($lineTotal * $gstPercent / 100, 2), 'total_price' => $lineTotal,
                    'hsn' => $line['hsn'] ?? null,
                ];
            }

            if (empty($items)) {
                $error = 'At least one valid line item is required.';
            } else {
                $supplier = $_POST['supplier_id'] ? $this->supplierModel->find($org['id'], (int) $_POST['supplier_id']) : null;
                $result = $this->model->createPurchase($org['id'], [
                    'supplier_id'    => $_POST['supplier_id'] ?: null,
                    'supplier_name'  => $supplier['name'] ?? ($_POST['supplier_name'] ?? null),
                    'invoice_number' => $_POST['invoice_number'] ?? null,
                    'purchase_date'  => $_POST['purchase_date'] ?? null,
                    'paid_amount'    => $_POST['paid_amount'] ?? null,
                    'payment_method' => $_POST['payment_method'] ?? 'CASH',
                    'notes'          => 'Created from AI-scanned bill',
                ], $items);

                if (!isset($result['error'])) {
                    redirect($org['slug'] . '/purchases/view/' . $result['id']);
                    return;
                }
                $error = $result['error'];
            }
        }

        redirect($org['slug'] . '/purchases/scan');
    }

    public function viewAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $purchase = $this->model->find($org['id'], (int) $id);
        if (!$purchase) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $pageTitle = 'Purchase ' . $purchase['purchase_number'];
        require __DIR__ . '/../views/trade/purchases/view.php';
    }

    public function cancelAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireRole(['owner', 'admin']);
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $reason = $_POST['reason'] ?? 'No reason given';
        $this->model->cancelPurchase($org['id'], (int) $id, $reason);
        logActivity($org['id'], 'purchase_cancelled', 'purchase', (int) $id, $reason);
        redirect($org['slug'] . '/purchases/view/' . (int) $id);
    }
}
