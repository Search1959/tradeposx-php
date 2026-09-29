<?php
class JobCardController {
    private JobCard $model;
    private ServiceCustomer $customerModel;
    private ServiceStaff $staffModel;

    public function __construct() {
        $this->model = new JobCard();
        $this->customerModel = new ServiceCustomer();
        $this->staffModel = new ServiceStaff();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $jobs = $this->model->allForOrg($org['id']);
        $pageTitle = 'Job Cards';
        require __DIR__ . '/../views/service/jobcards/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $customer = $_POST['customer_id'] ? $this->customerModel->find($org['id'], (int) $_POST['customer_id']) : null;
            $staffMember = $_POST['assigned_staff_id'] ? $this->staffModel->find($org['id'], (int) $_POST['assigned_staff_id']) : null;
            $rawParts = json_decode($_POST['parts_json'] ?? '[]', true) ?: [];
            $parts = [];
            // total_price is computed here, not trusted from the client — the JS cart
            // recomputes it reactively, but a form submit can fire before that render
            // tick flushes to the hidden field, so the server is the source of truth.
            foreach ($rawParts as $p) {
                if (trim($p['name'] ?? '') === '') continue;
                $qty = (float) ($p['quantity'] ?? 0);
                $unitPrice = (float) ($p['unit_price'] ?? 0);
                $parts[] = ['name' => $p['name'], 'quantity' => $qty, 'unit_price' => $unitPrice, 'total_price' => round($qty * $unitPrice, 2)];
            }

            if (trim($_POST['issue_description'] ?? '') === '') {
                $error = 'Issue description is required.';
            } else {
                $jobId = $this->model->create($org['id'], [
                    'customer_id' => $_POST['customer_id'] ?: null,
                    'customer_name' => $customer['name'] ?? $_POST['customer_name'] ?? null,
                    'mobile' => $customer['mobile'] ?? $_POST['mobile'] ?? null,
                    'service_sector' => $_POST['service_sector'] ?? null,
                    'assigned_staff_id' => $_POST['assigned_staff_id'] ?: null,
                    'assigned_staff_name' => $staffMember['name'] ?? null,
                    'priority' => $_POST['priority'] ?? 'Medium',
                    'issue_description' => $_POST['issue_description'],
                    'device_or_vehicle_info' => $_POST['device_or_vehicle_info'] ?? null,
                    'warranty_status' => $_POST['warranty_status'] ?? 'None',
                    'warranty_expiry_date' => $_POST['warranty_expiry_date'] ?? null,
                    'labour_charges' => $_POST['labour_charges'] ?? 0,
                    'estimated_completion_date' => $_POST['estimated_completion_date'] ?? null,
                ], $parts);
                redirect($org['slug'] . '/job-cards/view/' . $jobId);
                return;
            }
        }

        $customers = $this->customerModel->allForOrg($org['id']);
        $staff = $this->staffModel->allForOrg($org['id']);
        $pageTitle = 'New Job Card';
        require __DIR__ . '/../views/service/jobcards/form.php';
    }

    public function viewAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $job = $this->model->find($org['id'], (int) $id);
        if (!$job) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $pageTitle = $job['job_no'];
        require __DIR__ . '/../views/service/jobcards/view.php';
    }

    public function statusAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->updateStatus($org['id'], (int) $id, $_POST['status'] ?? 'Pending');
        redirect($org['slug'] . '/job-cards/view/' . (int) $id);
    }

    public function grantWarrantyAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $days = (int) ($_POST['warranty_days'] ?? 0);
        if ($days > 0) $this->model->grantServiceWarranty($org['id'], (int) $id, $days);
        redirect($org['slug'] . '/job-cards/view/' . (int) $id);
    }

    public function recordPaymentAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $amount = (float) ($_POST['amount'] ?? 0);
        if ($amount > 0) $this->model->recordPayment($org['id'], (int) $id, $amount);
        redirect($org['slug'] . '/job-cards/view/' . (int) $id);
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/job-cards');
    }
}
