<?php
class BrokerController {
    private Broker $model;

    public function __construct() {
        $this->model = new Broker();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $brokers = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $pageTitle = 'Brokers';
        require __DIR__ . '/../views/trade/brokers/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $broker = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $broker = $_POST;
            } else {
                $this->model->create($org['id'], $_POST);
                redirect($org['slug'] . '/brokers');
                return;
            }
        }

        $pageTitle = 'New Broker';
        require __DIR__ . '/../views/trade/brokers/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $broker = $this->model->find($org['id'], (int) $id);
        if (!$broker) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $broker = array_merge($broker, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/brokers');
                return;
            }
        }

        $pageTitle = 'Edit Broker';
        require __DIR__ . '/../views/trade/brokers/form.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/brokers');
    }

    /** Marks the broker's currently-owed commission as paid out — a manual
     * settle-up action, not tied to any specific sale. */
    public function payCommissionAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $broker = $this->model->find($org['id'], (int) $id);
        if ($broker) {
            $this->model->payCommission($org['id'], (int) $id, (float) $broker['commission_payable']);
        }
        redirect($org['slug'] . '/brokers');
    }
}
