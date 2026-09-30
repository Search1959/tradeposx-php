<?php
class SpecializedStaffController {
    private SpecializedStaff $model;

    public function __construct() {
        $this->model = new SpecializedStaff();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $staff = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $pageTitle = 'Specialized Staff';
        require __DIR__ . '/../views/service/specialized-staff/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $staffMember = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $staffMember = $_POST;
            } else {
                $this->model->create($org['id'], $_POST);
                redirect($org['slug'] . '/specialized-staff');
                return;
            }
        }

        $pageTitle = 'New Specialized Staff';
        require __DIR__ . '/../views/service/specialized-staff/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $staffMember = $this->model->find($org['id'], (int) $id);
        if (!$staffMember) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $staffMember = array_merge($staffMember, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/specialized-staff');
                return;
            }
        }

        $pageTitle = 'Edit Specialized Staff';
        require __DIR__ . '/../views/service/specialized-staff/form.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/specialized-staff');
    }

    /** Marks this staff member's currently-owed commission as paid out — a manual
     * settle-up action, not tied to any specific invoice. */
    public function payCommissionAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $staffMember = $this->model->find($org['id'], (int) $id);
        if ($staffMember) {
            $this->model->payCommission($org['id'], (int) $id, (float) $staffMember['commission_payable']);
        }
        redirect($org['slug'] . '/specialized-staff');
    }
}
