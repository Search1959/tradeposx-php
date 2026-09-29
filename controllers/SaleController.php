<?php
class SaleController {
    private Sale $model;
    private Product $productModel;
    private Customer $customerModel;

    public function __construct() {
        $this->model = new Sale();
        $this->productModel = new Product();
        $this->customerModel = new Customer();
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
                [$items, $subtotal, $taxTotal] = $this->buildItemsFromCart($org['id'], $cart);
                $discount = (float) ($_POST['discount'] ?? 0);
                $grandTotal = round($subtotal + $taxTotal - $discount, 2);
                // Only PARTIAL trusts the client's typed amount — FULL/CREDIT are
                // derived here, not read from a field the client may not have
                // touched (it's only ever shown/edited in the PARTIAL case).
                $receivedAmount = match ($_POST['payment_status'] ?? 'FULL') {
                    'CREDIT' => 0.0,
                    'PARTIAL' => (float) ($_POST['received_amount'] ?? 0),
                    default => $grandTotal,
                };

                $result = $this->model->createSale($org['id'], [
                    'customer_id'      => $_POST['customer_id'] ?: null,
                    'customer_name'    => $_POST['customer_name'] ?? null,
                    'customer_mobile'  => $_POST['customer_mobile'] ?? null,
                    'subtotal'         => $subtotal,
                    'discount'         => $discount,
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
        $pageTitle = 'New Sale';
        require __DIR__ . '/../views/trade/sales/pos.php';
    }

    /** @return array{0: array, 1: float, 2: float} [line items, subtotal, tax total] */
    private function buildItemsFromCart(int $orgId, array $cart): array {
        $items = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        foreach ($cart as $line) {
            $product = $this->productModel->find($orgId, (int) $line['id']);
            if (!$product) continue;
            $qty = (float) $line['qty'];
            $unitPrice = (float) $product['selling_price'];
            $lineTotal = round($qty * $unitPrice, 2);
            $gstRate = (float) $product['gst_percent'];
            $gstAmount = round($lineTotal * $gstRate / 100, 2);
            $subtotal += $lineTotal;
            $taxTotal += $gstAmount;
            $items[] = [
                'product_id' => $product['id'], 'product_name' => $product['name'], 'quantity' => $qty,
                'unit_price' => $unitPrice, 'mrp' => $product['mrp'], 'total_price' => $lineTotal,
                'gst_rate' => $gstRate, 'gst_amount' => $gstAmount, 'hsn' => $product['hsn'],
                'batch_number' => $product['batch_number'], 'expiry_date' => $product['expiry_date'],
            ];
        }
        return [$items, round($subtotal, 2), round($taxTotal, 2)];
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $sales = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $pageTitle = 'Sales';
        require __DIR__ . '/../views/trade/sales/list.php';
    }

    public function receiptAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $sale = $this->model->find($org['id'], (int) $id);
        if (!$sale) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        require __DIR__ . '/../views/trade/sales/receipt.php';
    }

    public function voidAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireRole(['owner', 'admin']);
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->voidSale($org['id'], (int) $id, $_POST['reason'] ?? 'No reason given');
        redirect($org['slug'] . '/sales/receipt/' . (int) $id);
    }
}
