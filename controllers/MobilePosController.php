<?php
class MobilePosController {
    private Product $productModel;
    private Customer $customerModel;
    private Sale $saleModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->customerModel = new Customer();
        $this->saleModel = new Sale();
    }

    /** The fast, big-card, minimal-chrome POS screen. PWA-installable, works
     * offline (service worker caches the shell + product/customer data). */
    public function indexAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $products = $this->productModel->allForOrg($org['id']);
        $customers = $this->customerModel->allForOrg($org['id']);
        $pageTitle = 'Quick Sale';
        require __DIR__ . '/../views/pos-mobile/index.php';
    }

    /** Product/customer catalog as JSON — the service worker caches this response
     * so the mobile POS can look up prices and stock with no network at all. */
    public function dataAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        header('Content-Type: application/json');
        echo json_encode([
            'products' => array_map(fn($p) => [
                'id' => (int)$p['id'], 'name' => $p['name'], 'category' => $p['category'],
                'selling_price' => (float)$p['selling_price'], 'gst_percent' => (float)$p['gst_percent'],
                'current_stock' => (float)$p['current_stock'], 'unit' => $p['unit'],
                'sku' => $p['sku'], 'barcode' => $p['barcode'],
            ], $this->productModel->allForOrg($org['id'])),
            'customers' => array_map(fn($c) => [
                'id' => (int)$c['id'], 'name' => $c['name'], 'mobile' => $c['mobile'],
                'current_balance' => (float)$c['current_balance'],
            ], $this->customerModel->allForOrg($org['id'])),
            'csrf_token' => CsrfHelper::generateToken(),
        ]);
    }

    /** JSON sync endpoint for both the online happy-path and the offline queue's
     * retry — a fetch() POST, not a form submit, so CSRF is checked against a
     * token field in the JSON body rather than $_POST (fetch with a JSON content
     * type never populates $_POST). client_ref makes a retried sync idempotent:
     * if a sale with this org+client_ref already exists, its id is returned again
     * instead of creating a duplicate — a flaky connection can retry safely. */
    public function syncAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body) || empty($body['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid session — please reload the page.']);
            return;
        }

        $clientRef = $body['client_ref'] ?? null;
        if ($clientRef) {
            $existing = $this->findByClientRef($org['id'], $clientRef);
            if ($existing) {
                echo json_encode(['success' => true, 'saleNumber' => $existing['sale_number'], 'id' => $existing['id']]);
                return;
            }
        }

        $cart = $body['cart'] ?? [];
        $items = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        foreach ($cart as $line) {
            $product = $this->productModel->find($org['id'], (int) ($line['id'] ?? 0));
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

        $result = $this->saleModel->createSale($org['id'], [
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

    private function findByClientRef(int $orgId, string $clientRef): ?array {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id, sale_number FROM sales WHERE org_id = ? AND client_ref = ?');
        $stmt->execute([$orgId, $clientRef]);
        return $stmt->fetch() ?: null;
    }
}
