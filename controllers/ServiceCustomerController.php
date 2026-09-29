<?php
class ServiceCustomerController {
    private ServiceCustomer $model;

    public function __construct() {
        $this->model = new ServiceCustomer();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $search = trim($_GET['q'] ?? '');
        $customers = $this->model->allForOrg($org['id'], $search ?: null);
        $pageTitle = 'Service Customers';
        require __DIR__ . '/../views/service/customers/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $customer = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            if ($name === '' || $mobile === '') {
                $error = 'Name and mobile are required.';
                $customer = $_POST;
            } else {
                $this->model->create($org['id'], $_POST);
                redirect($org['slug'] . '/service-customers');
                return;
            }
        }

        $pageTitle = 'New Service Customer';
        require __DIR__ . '/../views/service/customers/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $customer = $this->model->find($org['id'], (int) $id);
        if (!$customer) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            if ($name === '' || $mobile === '') {
                $error = 'Name and mobile are required.';
                $customer = array_merge($customer, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/service-customers');
                return;
            }
        }

        $pageTitle = 'Edit Service Customer';
        require __DIR__ . '/../views/service/customers/form.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/service-customers');
    }
}
