<?php
class ServiceItemController {
    private ServiceItem $model;

    public function __construct() {
        $this->model = new ServiceItem();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $items = $this->model->allForOrg($org['id']);
        $pageTitle = 'Service Items';
        require __DIR__ . '/../views/service/items/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $item = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $item = $_POST;
            } else {
                $this->model->create($org['id'], $_POST);
                redirect($org['slug'] . '/service-items');
                return;
            }
        }

        $pageTitle = 'New Service Item';
        require __DIR__ . '/../views/service/items/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $item = $this->model->find($org['id'], (int) $id);
        if (!$item) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $item = array_merge($item, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/service-items');
                return;
            }
        }

        $pageTitle = 'Edit Service Item';
        require __DIR__ . '/../views/service/items/form.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/service-items');
    }
}
