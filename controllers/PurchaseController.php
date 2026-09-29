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

    public function viewAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $purchase = $this->model->find($org['id'], (int) $id);
        if (!$purchase) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $pageTitle = 'Purchase ' . $purchase['purchase_number'];
        require __DIR__ . '/../views/trade/purchases/view.php';
    }
}
