<?php
require_once __DIR__ . '/config/app.php';

// Platform-level routes — no org-slug prefix.
$platformRouteMap = [
    'login'      => 'AuthController',
    'logout'     => 'AuthController',
    'superadmin' => 'SuperAdminController',
    'privacy'    => 'StaticController',
    'terms'      => 'StaticController',
    'support'    => 'StaticController',
    'deletion'   => 'StaticController',
];
$platformDefaultAction = [
    'login'      => 'index',
    'logout'     => 'logout',
    'superadmin' => 'index',
    'privacy'    => 'privacy',
    'terms'      => 'terms',
    'support'    => 'support',
    'deletion'   => 'deletion',
];

$url = trim($_GET['url'] ?? '', '/');

if ($url === '') {
    include __DIR__ . '/views/landing.php';
    exit;
}

$parts = explode('/', $url);
$firstSegment = strtolower($parts[0]);

function dispatch(string $controllerName, string $actionRaw, ?string $param = null): void {
    $controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';
    if (!file_exists($controllerFile)) {
        http_response_code(404);
        include __DIR__ . '/views/404.php';
        exit;
    }
    require_once $controllerFile;
    if (!class_exists($controllerName)) {
        http_response_code(404);
        include __DIR__ . '/views/404.php';
        exit;
    }
    $action = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($actionRaw))))) . 'Action';
    $controller = new $controllerName();
    if (!method_exists($controller, $action)) {
        http_response_code(404);
        include __DIR__ . '/views/404.php';
        exit;
    }
    $controller->$action($param);
}

// Public customer review page: /review/{shop-slug}[/draft|/click] — the slug is the action-param, the sub-action is optional.
if ($firstSegment === 'review') {
    $sub = strtolower($parts[2] ?? 'show');
    dispatch('ReviewController', in_array($sub, ['draft', 'click'], true) ? $sub : 'show', $parts[1] ?? null);
    exit;
}

// Public sector pages, sitemap and robots (no workspace, no login): /pos[/slug], /software[/slug], /sitemap, /sitemap.xml, /robots.txt
if (in_array($firstSegment, ['pos', 'software', 'sitemap', 'sitemap.xml', 'robots.txt'], true)) {
    $sectorRoutes = ['pos' => ['trade', $parts[1] ?? null], 'software' => ['service', $parts[1] ?? null],
                     'sitemap' => ['sitemap', null], 'sitemap.xml' => ['sitemapxml', null], 'robots.txt' => ['robots', null]];
    [$sectorAction, $sectorArg] = $sectorRoutes[$firstSegment];
    dispatch('SectorPageController', $sectorAction, $sectorArg);
    exit;
}

if (isset($platformRouteMap[$firstSegment])) {
    $defaultAction = $platformDefaultAction[$firstSegment] ?? 'index';
    dispatch($platformRouteMap[$firstSegment], $parts[1] ?? $defaultAction, $parts[2] ?? null);
    exit;
}

// Not a platform route — resolve the first segment as a tenant org slug.
$org = Tenant::resolve($firstSegment);

if (!$org) {
    http_response_code(404);
    include __DIR__ . '/views/404.php';
    exit;
}

if ($org['status'] === 'suspended' && !Auth::isSuperAdmin()) {
    http_response_code(403);
    include __DIR__ . '/views/403.php';
    exit;
}

// The public demo business: a link straight to any of its pages (e.g. a printed QR to /smarttech-demo/choose) signs
// the visitor in as the demo user, instead of showing a login form they have no credentials for.
// /login and /logout of the demo stay reachable, so someone can still sign in or out on purpose.
if ($org['slug'] === 'smarttech-demo' && !Auth::check() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && !in_array(strtolower($parts[1] ?? ''), ['login', 'logout'], true)) {
    dispatch('AuthController', 'demo');
    exit;
}

$tenantRouteMap = [
    'dashboard'          => 'DashboardController',
    'service-dashboard'  => 'DashboardController',
    'choose'             => 'DashboardController',
    'login'              => 'AuthController',
    'logout'             => 'AuthController',
    // TradeERP
    'products'           => 'ProductController',
    'customers'          => 'CustomerController',
    'suppliers'          => 'SupplierController',
    'brokers'            => 'BrokerController',
    'purchases'          => 'PurchaseController',
    'sales'              => 'SaleController',
    'pos'                => 'SaleController',
    'expenses'           => 'ExpenseController',
    'reports'            => 'ReportController',
    // ServiceERP
    'service-items'      => 'ServiceItemController',
    'service-customers'  => 'ServiceCustomerController',
    'appointments'       => 'AppointmentController',
    'job-cards'          => 'JobCardController',
    'service-staff'      => 'ServiceStaffController',
    'specialized-staff'  => 'SpecializedStaffController',
    'service-invoices'   => 'ServiceInvoiceController',
    'service-quotations' => 'ServiceQuotationController',
    // Mobile card POS (both modules share the same fast-tap shell)
    'pos-mobile'         => 'MobilePosController',
    // Shared across both modules — review/promotion WhatsApp campaigns
    'outreach'           => 'OutreachController',
    'reviewdesk'         => 'ReviewDeskController',
    // Platform-within-tenant
    'settings'           => 'SettingsController',
    // System Admin (owner/admin only — team accounts, audit trail)
    'team'               => 'TeamController',
    'activity-log'       => 'ActivityLogController',
];
$moduleDefaultAction = [
    'dashboard'          => 'index',
    'service-dashboard'  => 'service',
    'choose'             => 'choose',
    'login'              => 'index',
    'logout'             => 'logout',
    'products'           => 'list',
    'customers'          => 'list',
    'suppliers'          => 'list',
    'brokers'            => 'list',
    'purchases'          => 'list',
    'sales'              => 'newSale',
    'pos'                => 'newSale',
    'expenses'           => 'list',
    'reports'            => 'index',
    'service-items'      => 'list',
    'service-customers'  => 'list',
    'appointments'       => 'list',
    'job-cards'          => 'list',
    'service-staff'      => 'list',
    'specialized-staff'  => 'list',
    'service-invoices'   => 'list',
    'service-quotations' => 'list',
    'pos-mobile'         => 'index',
    'outreach'           => 'list',
    'reviewdesk'         => 'index',
    'settings'           => 'index',
    'team'               => 'list',
    'activity-log'       => 'list',
];

// Bare org slug (no module segment) sends staff to login/dashboard.
if (count($parts) === 1) {
    dispatch('AuthController', Auth::check() ? 'redirectHome' : 'index', null);
    exit;
}

$module = strtolower($parts[1] ?? 'dashboard');
$controllerName = $tenantRouteMap[$module] ?? null;

if (!$controllerName) {
    http_response_code(404);
    include __DIR__ . '/views/404.php';
    exit;
}

$defaultAction = $moduleDefaultAction[$module] ?? 'index';
dispatch($controllerName, $parts[2] ?? $defaultAction, $parts[3] ?? null);
