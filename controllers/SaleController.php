<?php
class SaleController {
    private Sale $model;
    private Product $productModel;
    private Customer $customerModel;
    private Broker $brokerModel;

    public function __construct() {
        $this->model = new Sale();
        $this->productModel = new Product();
        $this->customerModel = new Customer();
        $this->brokerModel = new Broker();
    }

    /** The desktop POS screen. Cart state lives client-side (Alpine.js); on submit
     * the cart is JSON-serialized into a hidden field and posted as a normal form,
     * matching the rest of this app's server-rendered, no-build-step approach. */
    public function newSaleAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);

        $error = null;
        $result = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $cart = json_decode($_POST['cart_json'] ?? '[]', true) ?: [];
            if (empty($cart)) {
                $error = 'Cart is empty.';
            } else {
                $taxType = in_array($_POST['tax_type'] ?? '', ['EXCLUSIVE', 'INCLUSIVE', 'EXEMPT'], true) ? $_POST['tax_type'] : 'EXCLUSIVE';
                [$items, $lineTotal, $taxableAmount, $taxTotal] = $this->buildItemsFromCart($org['id'], $cart, $taxType);
                $discount = (float) ($_POST['discount'] ?? 0);
                // Only set when a 5%/10% preset was actually used — a manually typed
                // Custom rupee amount has no percent to record, same as before this field existed.
                $discountPercent = in_array($_POST['discount_percent'] ?? '', ['5', '10'], true) ? (float) $_POST['discount_percent'] : null;
                $grandTotal = round($taxableAmount + $taxTotal - $discount, 2);
                $cgstAmount = round($taxTotal / 2, 2);
                $sgstAmount = round($taxTotal - $cgstAmount, 2); // avoids a 1-paisa mismatch from naive halving
                // Only PARTIAL trusts the client's typed amount — FULL/CREDIT are
                // derived here, not read from a field the client may not have
                // touched (it's only ever shown/edited in the PARTIAL case).
                $receivedAmount = match ($_POST['payment_status'] ?? 'FULL') {
                    'CREDIT' => 0.0,
                    'PARTIAL' => (float) ($_POST['received_amount'] ?? 0),
                    default => $grandTotal,
                };

                // Looked up server-side from customer_id, not trusted from a client-echoed
                // name/mobile field — the picker has no free-text name entry at all, so
                // there's nothing legitimate for the client to be sending here anyway.
                $customerId = $_POST['customer_id'] ?: null;
                $selectedCustomer = $customerId ? $this->customerModel->find($org['id'], (int) $customerId) : null;

                // A WhatsApp number typed for a walk-in (no customer selected) turns
                // them into a real customer record — reusing one that already has this
                // mobile number instead of creating a duplicate if they've given it
                // before. This is also the entire mechanism that gets them into Customer
                // Outreach: that screen just lists everyone in the customers table, no
                // separate opt-in flag, so existing as a customer row is enough.
                $walkinMobile = trim($_POST['walkin_mobile'] ?? '');
                if (!$customerId && $walkinMobile !== '') {
                    $selectedCustomer = $this->customerModel->findByMobile($org['id'], $walkinMobile);
                    if ($selectedCustomer) {
                        $customerId = $selectedCustomer['id'];
                    } else {
                        $customerId = $this->customerModel->create($org['id'], ['name' => 'Walk-in Customer', 'mobile' => $walkinMobile]);
                        $selectedCustomer = ['name' => 'Walk-in Customer', 'mobile' => $walkinMobile];
                    }
                }

                // Broker commission is computed from the server's own taxable amount, not
                // trusted as a rupee figure from the client — only the percent (0-100,
                // either the broker's stored default or a manually typed override) crosses
                // the wire, same trust boundary as gst_rate/taxable_amount above.
                $brokerId = $_POST['broker_id'] ?: null;
                $selectedBroker = $brokerId ? $this->brokerModel->find($org['id'], (int) $brokerId) : null;
                $brokerCommissionPercent = $selectedBroker ? max(0, min(100, (float) ($_POST['broker_commission_percent'] ?? 0))) : null;
                $brokerCommissionAmount = $selectedBroker ? round($taxableAmount * $brokerCommissionPercent / 100, 2) : 0.0;

                $result = $this->model->createSale($org['id'], [
                    'customer_id'      => $customerId,
                    'customer_name'    => $selectedCustomer['name'] ?? null,
                    'customer_mobile'  => $selectedCustomer['mobile'] ?? null,
                    'broker_id'                 => $brokerId,
                    'broker_commission_percent' => $brokerCommissionPercent,
                    'broker_commission_amount'  => $brokerCommissionAmount,
                    'subtotal'         => $lineTotal,
                    'discount'         => $discount,
                    'discount_percent' => $discountPercent,
                    'tax_type'         => $taxType,
                    'taxable_amount'   => $taxType === 'EXEMPT' ? null : $taxableAmount,
                    'cgst_amount'      => $taxType === 'EXEMPT' ? null : $cgstAmount,
                    'sgst_amount'      => $taxType === 'EXEMPT' ? null : $sgstAmount,
                    'total_tax_amount' => $taxTotal,
                    'grand_total'      => $grandTotal,
                    'payment_method'   => $_POST['payment_method'] ?? 'CASH',
                    'received_amount'  => $receivedAmount,
                    'notes'            => $_POST['notes'] ?? null,
                ], $items);

                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    redirect($org['slug'] . '/sales/receipt/' . $result['id']);
                    return;
                }
            }
        }

        $products = $this->productModel->allForOrg($org['id']);
        $customers = $this->customerModel->allForOrg($org['id']);
        $brokers = $this->brokerModel->allForOrg($org['id']);
        $pageTitle = 'New Sale';
        require __DIR__ . '/../views/trade/sales/pos.php';
    }

    /** @return array{0: array, 1: float, 2: float, 3: float} [line items, sticker line total, taxable amount, tax total]
     * EXCLUSIVE: selling_price is the pre-tax base — tax is added on top.
     * INCLUSIVE: selling_price already has GST baked in — tax is backed out of it.
     * EXEMPT: no tax charged regardless of the product's own gst_percent. */
    private function buildItemsFromCart(int $orgId, array $cart, string $taxType): array {
        $items = [];
        $lineTotal = 0.0;
        $taxableAmount = 0.0;
        $taxTotal = 0.0;
        $allowedGst = [0.0, 5.0, 12.0, 18.0, 28.0];
        foreach ($cart as $line) {
            if (!empty($line['custom'])) {
                // A free item written on the cash memo (not in stock): no product record, no stock movement.
                // The shop's own price and GST pick are trusted like an edited price; GST is still whitelisted.
                $name = mb_substr(trim((string) ($line['name'] ?? '')), 0, 200);
                if ($name === '') continue;
                $qty = max(0.001, (float) ($line['qty'] ?? 1));
                $unitPrice = max(0, (float) ($line['price'] ?? 0));
                $gstPick = (float) ($line['gst'] ?? 18);
                $gstPick = in_array($gstPick, $allowedGst, true) ? $gstPick : 18.0;
                $gstRate = $taxType === 'EXEMPT' ? 0.0 : $gstPick;
                $meta = ['product_id' => null, 'product_name' => $name, 'mrp' => null, 'hsn' => null, 'batch_number' => null, 'expiry_date' => null];
            } else {
                $product = $this->productModel->find($orgId, (int) $line['id']);
                if (!$product) continue;
                $qty = (float) $line['qty'];
                // A bargained price typed into the cart overrides the product's own selling_price for this sale
                // only — floored at 0; the product record itself (and its MRP/purchase_price) is never touched.
                $unitPrice = isset($line['price']) ? max(0, (float) $line['price']) : (float) $product['selling_price'];
                $gstRate = $taxType === 'EXEMPT' ? 0.0 : (float) $product['gst_percent'];
                $meta = ['product_id' => $product['id'], 'product_name' => $product['name'], 'mrp' => $product['mrp'], 'hsn' => $product['hsn'],
                         'batch_number' => $product['batch_number'], 'expiry_date' => $product['expiry_date']];
            }
            $lineAmount = round($qty * $unitPrice, 2);

            if ($taxType === 'INCLUSIVE' && $gstRate > 0) {
                $lineTaxable = round($lineAmount / (1 + $gstRate / 100), 2);
                $gstAmount = round($lineAmount - $lineTaxable, 2);
            } else {
                $lineTaxable = $lineAmount;
                $gstAmount = round($lineAmount * $gstRate / 100, 2);
            }

            $lineTotal += $lineAmount;
            $taxableAmount += $lineTaxable;
            $taxTotal += $gstAmount;
            $items[] = $meta + [
                'quantity' => $qty, 'unit_price' => $unitPrice, 'total_price' => $lineAmount,
                'gst_rate' => $gstRate, 'gst_amount' => $gstAmount,
            ];
        }
        return [$items, round($lineTotal, 2), round($taxableAmount, 2), round($taxTotal, 2)];
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $sales = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $pageTitle = 'Invoices';
        require __DIR__ . '/../views/trade/sales/list.php';
    }

    public function receiptAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $sale = $this->model->find($org['id'], (int) $id);
        if (!$sale) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $pageTitle = 'Invoice ' . ($sale['sale_number'] ?? '');
        require __DIR__ . '/../views/trade/sales/receipt.php';
    }

    public function voidAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireRole(['owner', 'admin']);
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $reason = $_POST['reason'] ?? 'No reason given';
        $this->model->voidSale($org['id'], (int) $id, $reason);
        logActivity($org['id'], 'sale_voided', 'sale', (int) $id, $reason);
        redirect($org['slug'] . '/sales/receipt/' . (int) $id);
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $sale = $this->model->find($org['id'], (int) $id);
        if (!$sale) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $backUrl = '/' . $org['slug'] . '/sales/receipt/' . (int) $id;
        if ($sale['status'] === 'CANCELLED') { redirect($org['slug'] . '/sales/receipt/' . (int) $id); return; }

        $methods = ['CASH', 'UPI', 'BANK', 'OTHER', 'CREDIT'];
        $error = null;
        $values = $sale;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $data = [
                'payment_method' => in_array($_POST['payment_method'] ?? '', $methods, true) ? $_POST['payment_method'] : $sale['payment_method'],
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ];
            if (!$sale['customer_id']) {
                $data['customer_name'] = mb_substr(trim($_POST['customer_name'] ?? ''), 0, 150) ?: null;
                $data['customer_mobile'] = mb_substr(trim($_POST['customer_mobile'] ?? ''), 0, 20) ?: null;
            }
            $this->model->updateDetails($org['id'], (int) $id, $data);
            logActivity($org['id'], 'sale_edited', 'sale', (int) $id, $sale['sale_number']);
            redirect($org['slug'] . '/sales/receipt/' . (int) $id);
            return;
        }

        $linked = (bool) $sale['customer_id'];
        $docTitle = 'Edit invoice';
        $docNo = $sale['sale_number'];
        $note = 'You can correct the customer details, payment method and notes. Items, amounts and tax are fixed — to change those, void this invoice and bill again so stock and Udhaar stay correct.';
        $fields = [
            ['name' => 'customer_name', 'label' => 'Customer name', 'readonly' => $linked, 'help' => $linked ? 'Linked customer — edit their details under Customers / Udhaar.' : ''],
            ['name' => 'customer_mobile', 'label' => 'Customer mobile / WhatsApp', 'readonly' => $linked],
            ['name' => 'payment_method', 'label' => 'Payment method', 'type' => 'select', 'options' => $methods],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
        require __DIR__ . '/../views/shared/doc-edit.php';
    }
    /** GET /sales/export?from=&to= — invoice register as CSV (owner/admin only: it's the whole sales book). */
    public function exportAction(): void {
        $org = Tenant::current();
        Auth::requireRole(['owner', 'admin']);
        Auth::requireOrgAccess($org['id']);
        $from = CsvExport::dateParam($_GET['from'] ?? null);
        $to = CsvExport::dateParam($_GET['to'] ?? null);

        $rows = [];
        foreach ($this->model->forExport($org['id'], $from, $to) as $s) {
            $cancelled = $s['status'] === 'CANCELLED';
            $tax = (float) $s['total_tax_amount'];
            $grand = (float) $s['grand_total'];
            $taxable = $s['taxable_amount'] !== null ? (float) $s['taxable_amount'] : round($grand - $tax, 2);
            $received = (float) $s['received_amount'];
            $cgst = $s['cgst_amount'] !== null ? (float) $s['cgst_amount'] : round($tax / 2, 2);
            $rows[] = [
                $s['sale_number'], substr($s['created_at'], 0, 10), $s['customer_name'] ?: 'Walk-in', $s['customer_mobile'], $s['customer_gstin'],
                (float) $s['subtotal'], (float) $s['discount'], $taxable, $cgst, $tax - $cgst, $tax, $grand,
                $s['payment_method'], $s['payment_status'], $received, $cancelled ? 0 : max(0, round($grand - $received, 2)),
                $cancelled ? 'Cancelled' : 'Active', $s['notes'],
            ];
        }
        $label = ($from ?: 'all') . '_' . ($to ?: 'all');
        logActivity($org['id'], 'invoices_exported', 'sale', 0, "{$label} rows=" . count($rows));
        CsvExport::send("invoices_{$org['slug']}_{$label}.csv", [
            'Invoice No', 'Date', 'Customer', 'Mobile', 'Customer GSTIN', 'Items Total', 'Discount', 'Taxable Amount', 'CGST', 'SGST', 'Total GST',
            'Grand Total', 'Payment Method', 'Payment Status', 'Received', 'Balance', 'Status', 'Notes',
        ], $rows);
    }}
