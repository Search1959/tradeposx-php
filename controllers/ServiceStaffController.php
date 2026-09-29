<?php
class ServiceStaffController {
    private ServiceStaff $model;

    public function __construct() {
        $this->model = new ServiceStaff();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $staff = $this->model->allForOrg($org['id']);
        $pageTitle = 'Staff';
        require __DIR__ . '/../views/service/staff/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $member = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $member = $_POST;
            } else {
                $this->model->create($org['id'], $_POST);
                redirect($org['slug'] . '/service-staff');
                return;
            }
        }

        $pageTitle = 'New Staff Member';
        require __DIR__ . '/../views/service/staff/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $member = $this->model->find($org['id'], (int) $id);
        if (!$member) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $member = array_merge($member, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/service-staff');
                return;
            }
        }

        $pageTitle = 'Edit Staff Member';
        require __DIR__ . '/../views/service/staff/form.php';
    }

    public function payCommissionAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $amount = (float) ($_POST['amount'] ?? 0);
        if ($amount > 0) $this->model->payCommission($org['id'], (int) $id, $amount);
        redirect($org['slug'] . '/service-staff');
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/service-staff');
    }
}
