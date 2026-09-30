<?php
class SupplierController {
    private Supplier $model;

    public function __construct() {
        $this->model = new Supplier();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $suppliers = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $pageTitle = 'Suppliers';
        require __DIR__ . '/../views/trade/suppliers/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $supplier = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $supplier = $_POST;
            } else {
                $this->model->create($org['id'], $_POST);
                logActivity($org['id'], 'supplier_create', 'supplier', 0, $name);
                redirect($org['slug'] . '/suppliers');
                return;
            }
        }

        $pageTitle = 'New Supplier';
        require __DIR__ . '/../views/trade/suppliers/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $supplier = $this->model->find($org['id'], (int) $id);
        if (!$supplier) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $supplier = array_merge($supplier, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/suppliers');
                return;
            }
        }

        $pageTitle = 'Edit Supplier';
        require __DIR__ . '/../views/trade/suppliers/form.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/suppliers');
    }
}
