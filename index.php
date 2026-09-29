<?php
require_once __DIR__ . '/config/app.php';

// Platform-level routes — no org-slug prefix.
$platformRouteMap = [
    'login'      => 'AuthController',
    'logout'     => 'AuthController',
    'superadmin' => 'SuperAdminController',
];
$platformDefaultAction = [
    'login'      => 'index',
    'logout'     => 'logout',
    'superadmin' => 'index',
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
    'service-invoices'   => 'ServiceInvoiceController',
    'service-quotations' => 'ServiceQuotationController',
    // Mobile card POS (both modules share the same fast-tap shell)
    'pos-mobile'         => 'MobilePosController',
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
    'service-invoices'   => 'list',
    'service-quotations' => 'list',
    'pos-mobile'         => 'index',
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
