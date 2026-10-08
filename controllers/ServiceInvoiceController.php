<?php
class ServiceInvoiceController {
    private ServiceInvoice $model;
    private ServiceItem $itemModel;
    private ServiceCustomer $customerModel;
    private ServiceStaff $staffModel;
    private SpecializedStaff $specializedStaffModel;

    public function __construct() {
        $this->model = new ServiceInvoice();
        $this->itemModel = new ServiceItem();
        $this->customerModel = new ServiceCustomer();
        $this->staffModel = new ServiceStaff();
        $this->specializedStaffModel = new SpecializedStaff();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $invoices = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
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
                // A bargained price typed into the cart overrides the service
                // item's own price for this invoice only — floored at 0 so a cart
                // edit can never produce a negative line; the service item's own
                // master price is never touched by this.
                $unitPrice = isset($line['price']) ? max(0, (float) $line['price']) : (float) $service['price'];
                $lineAmount = round($qty * $unitPrice, 2);
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
                    'price' => $unitPrice, 'quantity' => $qty, 'total' => $lineAmount,
                    'gst_rate' => $gstRate, 'gst_amount' => $gstAmount, 'staff_id' => $line['staff_id'] ?: null,
                ];
            }

            if (empty($items)) {
                $error = 'Cart is empty.';
            } else {
                $customerId = $_POST['customer_id'] ?: null;
                $customer = $customerId ? $this->customerModel->find($org['id'], (int) $customerId) : null;

                // A WhatsApp number typed for a walk-in (no customer selected) turns
                // them into a real customer record — reusing one that already has this
                // mobile number instead of creating a duplicate. This is also the
                // entire mechanism that gets them into Customer Outreach: that screen
                // just lists everyone in the service_customers table, no separate
                // opt-in flag, so existing as a customer row is enough.
                $walkinMobile = trim($_POST['walkin_mobile'] ?? '');
                if (!$customerId && $walkinMobile !== '') {
                    $customer = $this->customerModel->findByMobile($org['id'], $walkinMobile);
                    if ($customer) {
                        $customerId = $customer['id'];
                    } else {
                        $customerId = $this->customerModel->create($org['id'], ['name' => 'Walk-in Customer', 'mobile' => $walkinMobile]);
                        $customer = ['name' => 'Walk-in Customer', 'mobile' => $walkinMobile];
                    }
                }

                // Same trust boundary as SaleController's broker commission — only the
                // percent crosses the wire, the rupee amount is computed here from the
                // server's own taxable total.
                $specializedStaffId = $_POST['specialized_staff_id'] ?: null;
                $selectedSpecializedStaff = $specializedStaffId ? $this->specializedStaffModel->find($org['id'], (int) $specializedStaffId) : null;
                $staffCommissionPercent = $selectedSpecializedStaff ? max(0, min(100, (float) ($_POST['specialized_staff_commission_percent'] ?? 0))) : null;
                $staffCommissionAmount = $selectedSpecializedStaff ? round($taxableTotal * $staffCommissionPercent / 100, 2) : 0.0;

                $cgstAmount = round($taxTotal / 2, 2);
                $sgstAmount = round($taxTotal - $cgstAmount, 2);
                $grandTotal = round($taxableTotal + $taxTotal
                    + (float) ($_POST['labour_charges'] ?? 0) - (float) ($_POST['discount'] ?? 0), 2);
                // Only set when a 5%/10% preset was actually used — a manually typed
                // Custom rupee amount has no percent to record.
                $discountPercent = in_array($_POST['discount_percent'] ?? '', ['5', '10'], true) ? (float) $_POST['discount_percent'] : null;
                // Only PARTIAL trusts the client's typed amount — see SaleController for why.
                $paidAmount = match ($_POST['payment_status'] ?? 'FULL') {
                    'CREDIT' => 0.0,
                    'PARTIAL' => (float) ($_POST['paid_amount'] ?? 0),
                    default => $grandTotal,
                };

                $result = $this->model->createInvoice($org['id'], [
                    'customer_id'     => $customerId,
                    'customer_name'   => $customer['name'] ?? null,
                    'mobile'          => $customer['mobile'] ?? null,
                    'specialized_staff_id'          => $specializedStaffId,
                    'staff_commission_percent'      => $staffCommissionPercent,
                    'staff_commission_amount'       => $staffCommissionAmount,
                    'labour_charges'  => $_POST['labour_charges'] ?? 0,
                    'discount'        => $_POST['discount'] ?? 0,
                    'discount_percent' => $discountPercent,
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
        $specializedStaff = $this->specializedStaffModel->allForOrg($org['id']);
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

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $invoice = $this->model->find($org['id'], (int) $id);
        if (!$invoice) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $backUrl = '/' . $org['slug'] . '/service-invoices/view/' . (int) $id;
        if ($invoice['status'] === 'CANCELLED') { redirect($org['slug'] . '/service-invoices/view/' . (int) $id); return; }

        $methods = ['CASH', 'UPI', 'BANK', 'OTHER'];
        $error = null;
        $values = $invoice;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $date = $_POST['invoice_date'] ?? '';
            $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false;
            $data = [
                'invoice_date' => $validDate ? $date : $invoice['invoice_date'],
                'payment_method' => in_array($_POST['payment_method'] ?? '', $methods, true) ? $_POST['payment_method'] : ($invoice['payment_method'] ?? 'CASH'),
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ];
            if (!$invoice['customer_id']) {
                $data['customer_name'] = mb_substr(trim($_POST['customer_name'] ?? ''), 0, 150) ?: null;
                $data['mobile'] = mb_substr(trim($_POST['mobile'] ?? ''), 0, 20) ?: null;
            }
            $this->model->updateDetails($org['id'], (int) $id, $data);
            logActivity($org['id'], 'service_invoice_edited', 'service_invoice', (int) $id, $invoice['invoice_no']);
            redirect($org['slug'] . '/service-invoices/view/' . (int) $id);
            return;
        }

        $linked = (bool) $invoice['customer_id'];
        $docTitle = 'Edit invoice';
        $docNo = $invoice['invoice_no'];
        $note = 'You can correct the customer details, date, payment method and notes. Services, amounts and tax are fixed — to change those, cancel this invoice and bill again.';
        $fields = [
            ['name' => 'customer_name', 'label' => 'Customer name', 'readonly' => $linked, 'help' => $linked ? 'Linked customer — edit their details under Service Customers.' : ''],
            ['name' => 'mobile', 'label' => 'Customer mobile / WhatsApp', 'readonly' => $linked],
            ['name' => 'invoice_date', 'label' => 'Invoice date', 'type' => 'date'],
            ['name' => 'payment_method', 'label' => 'Payment method', 'type' => 'select', 'options' => $methods],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
        require __DIR__ . '/../views/shared/doc-edit.php';
    }}
