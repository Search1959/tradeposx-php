<?php
class ServiceQuotationController {
    private ServiceQuotation $model;
    private ServiceItem $itemModel;
    private ServiceCustomer $customerModel;

    public function __construct() {
        $this->model = new ServiceQuotation();
        $this->itemModel = new ServiceItem();
        $this->customerModel = new ServiceCustomer();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $quotations = $this->model->allForOrg($org['id']);
        $pageTitle = 'Service Quotations';
        require __DIR__ . '/../views/service/quotations/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $cart = json_decode($_POST['cart_json'] ?? '[]', true) ?: [];
            $items = [];
            foreach ($cart as $line) {
                $service = $this->itemModel->find($org['id'], (int) $line['id']);
                if (!$service) continue;
                $qty = max(1, (float) ($line['qty'] ?? 1));
                $items[] = [
                    'service_id' => $service['id'], 'name' => $service['name'], 'item_type' => 'SERVICE',
                    'price' => $service['price'], 'quantity' => $qty, 'total' => round($qty * (float) $service['price'], 2),
                ];
            }

            if (empty($items)) {
                $error = 'Cart is empty.';
            } else {
                $customer = $_POST['customer_id'] ? $this->customerModel->find($org['id'], (int) $_POST['customer_id']) : null;
                $result = $this->model->create($org['id'], [
                    'customer_id'     => $_POST['customer_id'] ?: null,
                    'customer_name'   => $customer['name'] ?? null,
                    'mobile'          => $customer['mobile'] ?? null,
                    'quote_date'      => $_POST['quote_date'] ?? null,
                    'valid_until'     => $_POST['valid_until'] ?? null,
                    'labour_charges'  => $_POST['labour_charges'] ?? 0,
                    'discount'        => $_POST['discount'] ?? 0,
                    'notes'           => $_POST['notes'] ?? null,
                ], $items);

                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    redirect($org['slug'] . '/service-quotations/view/' . $result['id']);
                    return;
                }
            }
        }

        $services = $this->itemModel->allForOrg($org['id']);
        $customers = $this->customerModel->allForOrg($org['id']);
        $pageTitle = 'New Quotation';
        require __DIR__ . '/../views/service/quotations/new.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $quotation = $this->model->find($org['id'], (int) $id);
        if (!$quotation) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $cart = json_decode($_POST['cart_json'] ?? '[]', true) ?: [];
            $items = [];
            foreach ($cart as $line) {
                $service = $this->itemModel->find($org['id'], (int) $line['id']);
                if (!$service) continue;
                $qty = max(1, (float) ($line['qty'] ?? 1));
                $items[] = [
                    'service_id' => $service['id'], 'name' => $service['name'], 'item_type' => 'SERVICE',
                    'price' => $service['price'], 'quantity' => $qty, 'total' => round($qty * (float) $service['price'], 2),
                ];
            }

            if (empty($items)) {
                $error = 'Cart is empty.';
            } else {
                $customer = $_POST['customer_id'] ? $this->customerModel->find($org['id'], (int) $_POST['customer_id']) : null;
                $result = $this->model->update($org['id'], (int) $id, [
                    'customer_id'     => $_POST['customer_id'] ?: null,
                    'customer_name'   => $customer['name'] ?? null,
                    'mobile'          => $customer['mobile'] ?? null,
                    'quote_date'      => $_POST['quote_date'] ?? null,
                    'valid_until'     => $_POST['valid_until'] ?? null,
                    'labour_charges'  => $_POST['labour_charges'] ?? 0,
                    'discount'        => $_POST['discount'] ?? 0,
                    'notes'           => $_POST['notes'] ?? null,
                ], $items);

                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    redirect($org['slug'] . '/service-quotations/view/' . (int) $id);
                    return;
                }
            }
        }

        $services = $this->itemModel->allForOrg($org['id']);
        $customers = $this->customerModel->allForOrg($org['id']);
        $pageTitle = 'Edit Quotation';
        require __DIR__ . '/../views/service/quotations/new.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/service-quotations');
    }

    public function viewAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $quotation = $this->model->find($org['id'], (int) $id);
        if (!$quotation) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $pageTitle = $quotation['quote_no'];
        require __DIR__ . '/../views/service/quotations/view.php';
    }

    public function statusAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $status = $_POST['status'] ?? 'Draft';
        if (in_array($status, ['Draft', 'Sent', 'Approved'], true)) {
            $this->model->updateStatus($org['id'], (int) $id, $status);
        }
        redirect($org['slug'] . '/service-quotations/view/' . (int) $id);
    }

    public function convertToInvoiceAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $result = $this->model->convertToInvoice($org['id'], (int) $id);
        if (isset($result['error'])) {
            redirect($org['slug'] . '/service-quotations/view/' . (int) $id);
            return;
        }
        redirect($org['slug'] . '/service-invoices/view/' . $result['invoiceId']);
    }
}
