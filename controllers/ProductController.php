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
}
