<?php
class AppointmentController {
    private Appointment $model;
    private ServiceCustomer $customerModel;
    private ServiceItem $itemModel;
    private ServiceStaff $staffModel;

    public function __construct() {
        $this->model = new Appointment();
        $this->customerModel = new ServiceCustomer();
        $this->itemModel = new ServiceItem();
        $this->staffModel = new ServiceStaff();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $appointments = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $pageTitle = 'Appointments';
        require __DIR__ . '/../views/service/appointments/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $customer = $_POST['customer_id'] ? $this->customerModel->find($org['id'], (int) $_POST['customer_id']) : null;
            $service = $_POST['service_id'] ? $this->itemModel->find($org['id'], (int) $_POST['service_id']) : null;
            $staff = $_POST['staff_id'] ? $this->staffModel->find($org['id'], (int) $_POST['staff_id']) : null;

            if (empty($_POST['appt_date']) || empty($_POST['appt_time'])) {
                $error = 'Date and time are required.';
            } else {
                $this->model->create($org['id'], [
                    'customer_id' => $_POST['customer_id'] ?: null,
                    'customer_name' => $customer['name'] ?? $_POST['customer_name'] ?? null,
                    'mobile' => $customer['mobile'] ?? $_POST['mobile'] ?? null,
                    'service_id' => $_POST['service_id'] ?: null,
                    'service_name' => $service['name'] ?? null,
                    'service_sector' => $service['sector'] ?? null,
                    'staff_id' => $_POST['staff_id'] ?: null,
                    'staff_name' => $staff['name'] ?? null,
                    'appt_date' => $_POST['appt_date'],
                    'appt_time' => $_POST['appt_time'],
                    'notes' => $_POST['notes'] ?? null,
                    'total_amount' => $service['price'] ?? 0,
                ]);
                redirect($org['slug'] . '/appointments');
                return;
            }
        }

        $customers = $this->customerModel->allForOrg($org['id']);
        $services = $this->itemModel->allForOrg($org['id']);
        $staff = $this->staffModel->allForOrg($org['id']);
        $pageTitle = 'New Appointment';
        require __DIR__ . '/../views/service/appointments/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $appointment = $this->model->find($org['id'], (int) $id);
        if (!$appointment) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $customer = $_POST['customer_id'] ? $this->customerModel->find($org['id'], (int) $_POST['customer_id']) : null;
            $service = $_POST['service_id'] ? $this->itemModel->find($org['id'], (int) $_POST['service_id']) : null;
            $staff = $_POST['staff_id'] ? $this->staffModel->find($org['id'], (int) $_POST['staff_id']) : null;

            if (empty($_POST['appt_date']) || empty($_POST['appt_time'])) {
                $error = 'Date and time are required.';
                $appointment = array_merge($appointment, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, [
                    'customer_id' => $_POST['customer_id'] ?: null,
                    'customer_name' => $customer['name'] ?? $appointment['customer_name'],
                    'mobile' => $customer['mobile'] ?? $appointment['mobile'],
                    'service_id' => $_POST['service_id'] ?: null,
                    'service_name' => $service['name'] ?? null,
                    'service_sector' => $service['sector'] ?? null,
                    'staff_id' => $_POST['staff_id'] ?: null,
                    'staff_name' => $staff['name'] ?? null,
                    'appt_date' => $_POST['appt_date'],
                    'appt_time' => $_POST['appt_time'],
                    'notes' => $_POST['notes'] ?? null,
                    'total_amount' => $service['price'] ?? $appointment['total_amount'],
                ]);
                redirect($org['slug'] . '/appointments');
                return;
            }
        }

        $customers = $this->customerModel->allForOrg($org['id']);
        $services = $this->itemModel->allForOrg($org['id']);
        $staff = $this->staffModel->allForOrg($org['id']);
        $pageTitle = 'Edit Appointment';
        require __DIR__ . '/../views/service/appointments/form.php';
    }

    public function statusAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->updateStatus($org['id'], (int) $id, $_POST['status'] ?? 'Scheduled');
        redirect($org['slug'] . '/appointments');
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/appointments');
    }
}
