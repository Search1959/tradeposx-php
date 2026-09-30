<?php
class MobilePosController {
    private Product $productModel;
    private Customer $customerModel;
    private Sale $saleModel;
    private ServiceItem $serviceItemModel;
    private ServiceCustomer $serviceCustomerModel;
    private ServiceInvoice $serviceInvoiceModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->customerModel = new Customer();
        $this->saleModel = new Sale();
        $this->serviceItemModel = new ServiceItem();
        $this->serviceCustomerModel = new ServiceCustomer();
        $this->serviceInvoiceModel = new ServiceInvoice();
    }

    /** Same resolution header.php uses to decide which sidebar to show — this
     * page has no header (it's a standalone, no-CDN shell for offline caching,
     * see indexAction's own note) so it can't just read that file's $workspace. */
    private function resolveWorkspace(array $org): string {
        return match ($org['business_type'] ?? 'both') {
            'trade' => 'trade',
            'service' => 'service',
            default => $_SESSION['workspace'] ?? 'trade',
        };
    }

    /** The fast, big-card, minimal-chrome POS screen. PWA-installable, works
     * offline (service worker caches the shell + product/customer data).
     * Shared shell for both modules — which catalog/customer list it shows,
     * and whether completing a sale creates a Sale or a ServiceInvoice, follows
     * whichever workspace the visitor is currently in (same as every other
     * shared-shell screen in this app). */
    public function indexAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $workspace = $this->resolveWorkspace($org);
        $pageTitle = 'Quick Sale';
        require __DIR__ . '/../views/pos-mobile/index.php';
    }

    /** Catalog/customer data as JSON, normalized to one shape regardless of
     * workspace — the service worker caches this response so the mobile POS
     * can look up prices with no network at all. current_stock/unit are only
     * meaningful for products; a service item's current_stock is always null
     * so the view knows to skip that line rather than showing a fake "0 pcs". */
    public function dataAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $workspace = $this->resolveWorkspace($org);
        header('Content-Type: application/json');

        if ($workspace === 'service') {
            $items = array_map(fn($s) => [
                'id' => (int)$s['id'], 'name' => $s['name'], 'category' => $s['category'],
                'selling_price' => (float)$s['price'], 'gst_percent' => (float)$s['gst_percent'],
                'current_stock' => null, 'unit' => null, 'sku' => null, 'barcode' => null,
            ], $this->serviceItemModel->allForOrg($org['id']));
            $customers = array_map(fn($c) => [
                'id' => (int)$c['id'], 'name' => $c['name'], 'mobile' => $c['mobile'], 'current_balance' => 0.0,
            ], $this->serviceCustomerModel->allForOrg($org['id']));
        } else {
            $items = array_map(fn($p) => [
                'id' => (int)$p['id'], 'name' => $p['name'], 'category' => $p['category'],
                'selling_price' => (float)$p['selling_price'], 'gst_percent' => (float)$p['gst_percent'],
                'current_stock' => (float)$p['current_stock'], 'unit' => $p['unit'],
                'sku' => $p['sku'], 'barcode' => $p['barcode'],
            ], $this->productModel->allForOrg($org['id']));
            $customers = array_map(fn($c) => [
                'id' => (int)$c['id'], 'name' => $c['name'], 'mobile' => $c['mobile'],
                'current_balance' => (float)$c['current_balance'],
            ], $this->customerModel->allForOrg($org['id']));
        }

        echo json_encode([
            'workspace' => $workspace,
            'products' => $items,
            'customers' => $customers,
            'csrf_token' => CsrfHelper::generateToken(),
        ]);
    }

    /** JSON sync endpoint for both the online happy-path and the offline queue's
     * retry — a fetch() POST, not a form submit, so CSRF is checked against a
     * token field in the JSON body rather than $_POST (fetch with a JSON content
     * type never populates $_POST). client_ref makes a retried sync idempotent:
     * if a sale/invoice with this org+client_ref already exists, its id is
     * returned again instead of creating a duplicate — a flaky connection can
     * retry safely. Branches on workspace the same way indexAction/dataAction
     * do, creating a ServiceInvoice instead of a Sale on the service side. */
    public function syncAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $workspace = $this->resolveWorkspace($org);
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body) || empty($body['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid session — please reload the page.']);
            return;
        }

        $clientRef = $body['client_ref'] ?? null;
        if ($clientRef) {
            $existing = $this->findByClientRef($org['id'], $clientRef, $workspace);
            if ($existing) {
                echo json_encode(['success' => true, 'saleNumber' => $existing['number'], 'id' => $existing['id']]);
                return;
            }
        }

        if ($workspace === 'service') {
            $this->syncServiceInvoice($org['id'], $body, $clientRef);
        } else {
            $this->syncTradeSale($org['id'], $body, $clientRef);
        }
    }

    private function syncTradeSale(int $orgId, array $body, ?string $clientRef): void {
        $cart = $body['cart'] ?? [];
        $items = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        foreach ($cart as $line) {
            $product = $this->productModel->find($orgId, (int) ($line['id'] ?? 0));
            if (!$product) continue;
            $qty = (float) ($line['qty'] ?? 0);
            if ($qty <= 0) continue;
            $unitPrice = (float) $product['selling_price'];
            $lineTotal = round($qty * $unitPrice, 2);
            $gstAmount = round($lineTotal * (float) $product['gst_percent'] / 100, 2);
            $subtotal += $lineTotal;
            $taxTotal += $gstAmount;
            $items[] = [
                'product_id' => $product['id'], 'product_name' => $product['name'], 'quantity' => $qty,
                'unit_price' => $unitPrice, 'mrp' => $product['mrp'], 'total_price' => $lineTotal,
                'gst_rate' => (float) $product['gst_percent'], 'gst_amount' => $gstAmount, 'hsn' => $product['hsn'],
                'batch_number' => $product['batch_number'], 'expiry_date' => $product['expiry_date'],
            ];
        }

        if (empty($items)) {
            http_response_code(422);
            echo json_encode(['error' => 'Cart is empty or all items are invalid.']);
            return;
        }

        $grandTotal = round($subtotal + $taxTotal, 2);
        // Mobile quick-sale keeps payment simple: paid in full or full credit —
        // no partial-payment entry on this screen (that stays on the desktop POS).
        $paymentMethod = $body['payment_method'] ?? 'CASH';
        $isCredit = $paymentMethod === 'CREDIT';
        $receivedAmount = $isCredit ? 0.0 : $grandTotal;

        $result = $this->saleModel->createSale($orgId, [
            'customer_id'     => $body['customer_id'] ?: null,
            'subtotal'        => $subtotal,
            'total_tax_amount'=> $taxTotal,
            'grand_total'     => $grandTotal,
            'payment_method'  => $isCredit ? 'CASH' : $paymentMethod,
            'received_amount' => $receivedAmount,
            'client_ref'      => $clientRef,
        ], $items);

        if (isset($result['error'])) {
            http_response_code(422);
            echo json_encode(['error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true, 'saleNumber' => $result['saleNumber'], 'id' => $result['id']]);
    }

    private function syncServiceInvoice(int $orgId, array $body, ?string $clientRef): void {
        $cart = $body['cart'] ?? [];
        $items = [];
        $taxableTotal = 0.0;
        $taxTotal = 0.0;
        foreach ($cart as $line) {
            $service = $this->serviceItemModel->find($orgId, (int) ($line['id'] ?? 0));
            if (!$service) continue;
            $qty = (float) ($line['qty'] ?? 0);
            if ($qty <= 0) continue;
            $lineAmount = round($qty * (float) $service['price'], 2);
            $gstRate = (float) $service['gst_percent'];
            $gstAmount = round($lineAmount * $gstRate / 100, 2);
            $taxableTotal += $lineAmount;
            $taxTotal += $gstAmount;
            $items[] = [
                'service_id' => $service['id'], 'name' => $service['name'], 'item_type' => 'SERVICE',
                'price' => $service['price'], 'quantity' => $qty, 'total' => $lineAmount,
                'gst_rate' => $gstRate, 'gst_amount' => $gstAmount,
            ];
        }

        if (empty($items)) {
            http_response_code(422);
            echo json_encode(['error' => 'Cart is empty or all items are invalid.']);
            return;
        }

        $cgstAmount = round($taxTotal / 2, 2);
        $sgstAmount = round($taxTotal - $cgstAmount, 2);
        $grandTotal = round($taxableTotal + $taxTotal, 2);
        $paymentMethod = $body['payment_method'] ?? 'CASH';
        $isCredit = $paymentMethod === 'CREDIT';
        $paidAmount = $isCredit ? 0.0 : $grandTotal;

        $customer = !empty($body['customer_id']) ? $this->serviceCustomerModel->find($orgId, (int) $body['customer_id']) : null;

        $result = $this->serviceInvoiceModel->createInvoice($orgId, [
            'customer_id'    => $body['customer_id'] ?: null,
            'customer_name'  => $customer['name'] ?? null,
            'mobile'         => $customer['mobile'] ?? null,
            'tax_type'       => 'EXCLUSIVE',
            'gst_amount'     => $taxTotal,
            'taxable_amount' => $taxableTotal,
            'cgst_amount'    => $cgstAmount,
            'sgst_amount'    => $sgstAmount,
            'payment_method' => $isCredit ? 'CASH' : $paymentMethod,
            'paid_amount'    => $paidAmount,
            'client_ref'     => $clientRef,
        ], $items);

        if (isset($result['error'])) {
            http_response_code(422);
            echo json_encode(['error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true, 'saleNumber' => $result['invoiceNo'], 'id' => $result['id']]);
    }

    /** Served at /{org}/pos-mobile/sw (no .js — the front controller's action-name
     * parsing can't route a dotted segment, and browsers only check the
     * Content-Type header for a service worker script, not the URL suffix).
     * Its default scope is its own directory
     * (/{org}/pos-mobile/), which is exactly what should work offline: the shell
     * page and the data endpoint. No Service-Worker-Allowed header needed. */
    public function swAction(): void {
        $org = Tenant::current();
        $base = '/' . $org['slug'];
        $shellUrl = $base . '/pos-mobile';
        $dataUrl = $base . '/pos-mobile/data';
        $js = <<<JS
const CACHE = 'tradeposx-pos-mobile-v1';
const SHELL_URL = '{$shellUrl}';
const DATA_URL = '{$dataUrl}';

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll([SHELL_URL])));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

// Network-first for the shell and data (so changes show up immediately when
// online), falling back to the last cached copy the moment the network fails —
// this is what lets the page and its product/customer data load with zero
// connectivity.
self.addEventListener('fetch', (event) => {
  const url = event.request.url;
  if (event.request.method !== 'GET' || (url.indexOf(SHELL_URL) === -1 && url.indexOf(DATA_URL) === -1)) return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        const copy = response.clone();
        caches.open(CACHE).then((cache) => cache.put(event.request, copy));
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});
JS;
        // PHP's built-in dev server (php -S) never sends Content-Length for
        // dynamically echoed output, only `Connection: close` — which the browser's
        // stricter Service Worker script-fetch algorithm rejects outright (a normal
        // fetch() doesn't care, but registration does), even though the exact same
        // bytes work fine everywhere else. Setting it explicitly fixes registration
        // under the dev server and is a harmless no-op under real Apache/LiteSpeed
        // hosting, which sets it correctly on its own.
        header('Content-Type: application/javascript');
        header('Cache-Control: no-cache'); // so a redeploy's cache-busted asset list is picked up promptly
        header('Content-Length: ' . strlen($js));
        echo $js;
    }

    private function findByClientRef(int $orgId, string $clientRef, string $workspace): ?array {
        $db = Database::getInstance()->getConnection();
        if ($workspace === 'service') {
            $stmt = $db->prepare('SELECT id, invoice_no as number FROM service_invoices WHERE org_id = ? AND client_ref = ?');
        } else {
            $stmt = $db->prepare('SELECT id, sale_number as number FROM sales WHERE org_id = ? AND client_ref = ?');
        }
        $stmt->execute([$orgId, $clientRef]);
        return $stmt->fetch() ?: null;
    }
}
