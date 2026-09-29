<?php
class ServiceInvoiceController {
    private ServiceInvoice $model;
    private ServiceItem $itemModel;
    private ServiceCustomer $customerModel;
    private ServiceStaff $staffModel;

    public function __construct() {
        $this->model = new ServiceInvoice();
        $this->itemModel = new ServiceItem();
        $this->customerModel = new ServiceCustomer();
        $this->staffModel = new ServiceStaff();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $invoices = $this->model->allForOrg($org['id']);
        $pageTitle = 'Service Invoices';
        require __DIR__ . '/../views/service/invoices/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $cart = json_decode($_POST['cart_json'] ?? '[]', true) ?: [];
            $taxType = in_array($_POST['tax_type'] ?? '', ['EXCLUSIVE', 'INCLUSIVE', 'EXEMPT'], true) ? $_POST['tax_type'] : 'EXCLUSIVE';
            $items = [];
            $taxableTotal = 0.0;
            $taxTotal = 0.0;
            foreach ($cart as $line) {
                $service = $this->itemModel->find($org['id'], (int) $line['id']);
                if (!$service) continue;
                $qty = max(1, (float) ($line['qty'] ?? 1));
                $lineAmount = round($qty * (float) $service['price'], 2);
                $gstRate = $taxType === 'EXEMPT' ? 0.0 : (float) $service['gst_percent'];

                if ($taxType === 'INCLUSIVE' && $gstRate > 0) {
                    $lineTaxable = round($lineAmount / (1 + $gstRate / 100), 2);
                    $gstAmount = round($lineAmount - $lineTaxable, 2);
                } else {
                    $lineTaxable = $lineAmount;
                    $gstAmount = round($lineAmount * $gstRate / 100, 2);
                }
                $taxableTotal += $lineTaxable;
                $taxTotal += $gstAmount;

                $items[] = [
                    'service_id' => $service['id'], 'name' => $service['name'], 'item_type' => 'SERVICE',
                    'price' => $service['price'], 'quantity' => $qty, 'total' => $lineAmount,
                    'gst_rate' => $gstRate, 'gst_amount' => $gstAmount, 'staff_id' => $line['staff_id'] ?: null,
                ];
            }

            if (empty($items)) {
                $error = 'Cart is empty.';
            } else {
                $customer = $_POST['customer_id'] ? $this->customerModel->find($org['id'], (int) $_POST['customer_id']) : null;
                $cgstAmount = round($taxTotal / 2, 2);
                $sgstAmount = round($taxTotal - $cgstAmount, 2);
                $grandTotal = round($taxableTotal + $taxTotal
                    + (float) ($_POST['labour_charges'] ?? 0) - (float) ($_POST['discount'] ?? 0), 2);
                // Only PARTIAL trusts the client's typed amount — see SaleController for why.
                $paidAmount = match ($_POST['payment_status'] ?? 'FULL') {
                    'CREDIT' => 0.0,
                    'PARTIAL' => (float) ($_POST['paid_amount'] ?? 0),
                    default => $grandTotal,
                };

                $result = $this->model->createInvoice($org['id'], [
                    'customer_id'     => $_POST['customer_id'] ?: null,
                    'customer_name'   => $customer['name'] ?? null,
                    'mobile'          => $customer['mobile'] ?? null,
                    'labour_charges'  => $_POST['labour_charges'] ?? 0,
                    'discount'        => $_POST['discount'] ?? 0,
                    'tax_type'        => $taxType,
                    'gst_amount'      => $taxTotal,
                    'taxable_amount'  => $taxType === 'EXEMPT' ? null : $taxableTotal,
                    'cgst_amount'     => $taxType === 'EXEMPT' ? null : $cgstAmount,
                    'sgst_amount'     => $taxType === 'EXEMPT' ? null : $sgstAmount,
                    'payment_method'  => $_POST['payment_method'] ?? 'CASH',
                    'paid_amount'     => $paidAmount,
                    'notes'           => $_POST['notes'] ?? null,
                ], $items);

                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    redirect($org['slug'] . '/service-invoices/view/' . $result['id']);
                    return;
                }
            }
        }

        $services = $this->itemModel->allForOrg($org['id']);
        $customers = $this->customerModel->allForOrg($org['id']);
        $staff = $this->staffModel->allForOrg($org['id']);
        $pageTitle = 'New Service Invoice';
        require __DIR__ . '/../views/service/invoices/new.php';
    }

    public function viewAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $invoice = $this->model->find($org['id'], (int) $id);
        if (!$invoice) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $pageTitle = $invoice['invoice_no'];
        require __DIR__ . '/../views/service/invoices/view.php';
    }

    public function recordPaymentAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $amount = (float) ($_POST['amount'] ?? 0);
        if ($amount > 0) $this->model->recordPayment($org['id'], (int) $id, $amount);
        redirect($org['slug'] . '/service-invoices/view/' . (int) $id);
    }

    public function cancelAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireRole(['owner', 'admin']);
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->cancel($org['id'], (int) $id, $_POST['reason'] ?? 'No reason given');
        redirect($org['slug'] . '/service-invoices/view/' . (int) $id);
    }
}
