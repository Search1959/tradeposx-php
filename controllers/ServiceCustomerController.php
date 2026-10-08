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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $customers = $this->model->allForOrg($org['id'], $search ?: null, $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id'], $search ?: null);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
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

    public function viewAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $customer = $this->model->find($org['id'], (int) $id);
        if (!$customer) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $history = $this->model->history($org['id'], (int) $id);
        $due = 0.0;
        foreach ($history['invoices'] as $i) { if ($i['status'] !== 'CANCELLED') $due += (float) $i['balance_amount']; }
        foreach ($history['jobs'] as $j) { if ($j['status'] !== 'Cancelled') $due += (float) $j['balance_amount']; }
        $pageTitle = $customer['name'];
        require __DIR__ . '/../views/service/customers/view.php';
    }}
