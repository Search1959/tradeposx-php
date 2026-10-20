<?php
/** Included by every tenant-scoped view. Expects $pageTitle to be set by the caller. */
$org = Tenant::current();
$user = Auth::user();

// Which side of the app is currently active, so the sidebar shows one menu,
// not both stacked together. A single-type org has no choice - it's always
// its own type. A "both" org's active side is whichever module the visitor
// most recently opened, tracked in session (defaults to trade until they
// pick, or visit a service-side page, or use "Switch Workspace" below).
// 'reports' and 'expenses' are deliberately excluded from both lists below —
// they're shared pages reachable from either workspace's nav now, so visiting
// one shouldn't flip $_SESSION['workspace'] away from whichever side the
// visitor was already on.
$serviceModules = ['service-dashboard', 'appointments', 'job-cards', 'service-invoices', 'service-quotations', 'service-customers', 'service-staff', 'service-items', 'specialized-staff'];
$tradeModules = ['dashboard', 'sales', 'pos', 'products', 'customers', 'suppliers', 'purchases', 'brokers'];
$currentModule = explode('/', trim($_GET['url'] ?? '', '/'))[1] ?? '';
if (in_array($currentModule, $serviceModules, true)) {
    $_SESSION['workspace'] = 'service';
} elseif (in_array($currentModule, $tradeModules, true)) {
    $_SESSION['workspace'] = 'trade';
}
$workspace = match ($org['business_type'] ?? 'both') {
    'trade' => 'trade',
    'service' => 'service',
    default => $_SESSION['workspace'] ?? 'trade',
};

require_once __DIR__ . '/icons.php';
$seg = explode('/', trim($_GET['url'] ?? '', '/'));
$seg2 = $seg[2] ?? '';

// Per-workspace accent so staff always know which side they're on: indigo = TradeERP, emerald = ServiceERP.
$isTrade = $workspace === 'trade';
$accentBg   = $isTrade ? 'bg-indigo-600' : 'bg-emerald-600';
$accentText = $isTrade ? 'text-indigo-600' : 'text-emerald-600';
$accentSoft = $isTrade ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700';
$accentBar  = $isTrade ? 'bg-indigo-400' : 'bg-emerald-400';

// Which of the bottom-bar destinations (mobile only, see footer.php) is active.
$mobileTab = match (true) {
    in_array($currentModule, ['dashboard', 'service-dashboard', 'choose'], true) => 'home',
    $isTrade && $currentModule === 'products' => 'second',
    $isTrade && $currentModule === 'customers' => 'third',
    !$isTrade && $currentModule === 'job-cards' => 'second',
    !$isTrade && $currentModule === 'appointments' => 'third',
    default => '',
};
$isAdmin = in_array($user['role'] ?? '', ['owner', 'admin'], true);

// [label, path, icon, modules that make it active, optional: 'new' = only /new, 'list' = not /new]
$navGroups = $isTrade ? [
    'Overview' => [['Dashboard', '/dashboard', 'home', ['dashboard']]],
    'Sales' => [
        ['New Sale (POS)', '/sales', 'cart', ['sales', 'pos'], 'notinv'],
        ['Invoices', '/sales/list', 'document', ['sales'], 'inv'],
        ['Quick Sale (Mobile)', '/pos-mobile', 'phone', ['pos-mobile']],
        ['Customers / Udhaar', '/customers', 'users', ['customers']],
        ['Brokers', '/brokers', 'briefcase', ['brokers']],
    ],
    'Stock' => [
        ['Products', '/products', 'box', ['products']],
        ['Purchases', '/purchases', 'receipt', ['purchases']],
        ['Suppliers', '/suppliers', 'truck', ['suppliers']],
    ],
    'Finance' => [
        ['Expenses', '/expenses', 'cash', ['expenses']],
        ['Reports', '/reports', 'chart', ['reports']],
    ],
    'Grow' => [['Customer Outreach', '/outreach', 'chat', ['outreach']]],
] : [
    'Overview' => [['Dashboard', '/service-dashboard', 'home', ['service-dashboard']]],
    'Billing' => [
        ['New Bill (POS)', '/service-invoices/new', 'receipt', ['service-invoices'], 'new'],
        ['Service Invoices', '/service-invoices', 'document', ['service-invoices'], 'list'],
        ['Quotations', '/service-quotations', 'clipboard', ['service-quotations']],
        ['Quick Sale (Mobile)', '/pos-mobile', 'phone', ['pos-mobile']],
    ],
    'Operations' => [
        ['Appointments', '/appointments', 'calendar', ['appointments']],
        ['Job Cards', '/job-cards', 'wrench', ['job-cards']],
        ['Service Items', '/service-items', 'tag', ['service-items']],
    ],
    'People' => [
        ['Service Customers', '/service-customers', 'users', ['service-customers']],
        ['Staff', '/service-staff', 'briefcase', ['service-staff']],
        ['Specialized Staff', '/specialized-staff', 'shield', ['specialized-staff']],
    ],
    'Finance' => [
        ['Expenses', '/expenses', 'cash', ['expenses']],
        ['Reports', '/reports', 'chart', ['reports']],
    ],
    'Grow' => [['Customer Outreach', '/outreach', 'chat', ['outreach']]],
];
if ($isAdmin) $navGroups['Grow'][] = ['Google Reviews', '/reviewdesk', 'star', ['reviewdesk']];
$navGroups['Account'] = [['Account & Password', '/settings', 'lock', ['settings']]];
if ($isAdmin) {
    $navGroups['System'] = [
        ['Team', '/team', 'users', ['team']],
        ['Activity Log', '/activity-log', 'clipboard', ['activity-log']],
    ];
}
$userInitial = strtoupper(mb_substr(trim($user['name'] ?? $org['name'] ?? 'U'), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h($pageTitle ?? 'Dashboard') ?> · <?= h($org['name'] ?? APP_NAME) ?></title>
<link rel="manifest" href="/assets/manifest.webmanifest">
<link rel="icon" href="/assets/img/icons/icon-192.png">
<link rel="apple-touch-icon" href="/assets/img/icons/icon-512.png">
<meta name="theme-color" content="<?= h($org['theme_color'] ?? '#4f46e5') ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  // Makes the whole app installable as a home-screen app (Android "Install app"
  // prompt) - see sw.js for why it deliberately caches nothing.
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {}));
  }
</script>
<style>
  [x-cloak] { display: none !important; }
  body { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
  /* 16px inputs stop iOS Safari zooming the page on focus - phones only, desktop keeps compact fields */
  @media (max-width: 767px) { input, select, textarea { font-size: 16px !important; } }
  /* Phones: list tables scroll sideways, so keep the Actions column (last cell) pinned to the right edge */
  @media (max-width: 767px) {
    table.sticky-actions th:last-child, table.sticky-actions td:last-child { position: sticky; right: 0; background: #fff; box-shadow: -8px 0 8px -8px rgba(15,23,42,.18); }
    table.sticky-actions thead th:last-child { background: #f8fafc; }
    table.sticky-actions td:last-child { white-space: normal !important; width: 8.5rem; min-width: 8.5rem; line-height: 1.9; }
  }
</style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
<?php
$base = $org ? '/' . h($org['slug']) : '';
// Built once, rendered twice (desktop sidebar + mobile off-canvas panel) so the
// two navs can never silently drift apart.
ob_start();
?>
    <div class="px-4 py-4 border-b border-white/5 flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg <?= $accentBg ?> text-white flex items-center justify-center font-bold"><?= h(strtoupper(mb_substr($org['name'] ?? APP_NAME, 0, 1))) ?></div>
      <div class="min-w-0 flex-1">
        <div class="text-sm font-semibold text-white truncate"><?= h($org['name'] ?? APP_NAME) ?></div>
        <div class="text-[11px] text-slate-400"><?= $isTrade ? 'TradeERP' : 'ServiceERP' ?> · <?= h(ucfirst($user['role'] ?? 'staff')) ?></div>
      </div>
      <button type="button" @click="mobileNavOpen = false" class="md:hidden p-1 text-slate-400 hover:text-white" aria-label="Close menu"><?= uiIcon('x', 'w-6 h-6') ?></button>
    </div>
    <nav class="flex-1 px-3 py-3 overflow-y-auto text-sm">
      <?php foreach ($navGroups as $navGroupLabel => $navGroupItems): ?>
        <div class="px-2 pt-4 pb-1.5 text-[10px] font-semibold uppercase tracking-widest text-slate-500"><?= h($navGroupLabel) ?></div>
        <?php foreach ($navGroupItems as $navItem):
          $isActive = in_array($currentModule, $navItem[3], true)
              && match ($navItem[4] ?? '') { 'new' => $seg2 === 'new', 'list' => $seg2 !== 'new', 'inv' => in_array($seg2, ['list', 'receipt'], true), 'notinv' => !in_array($seg2, ['list', 'receipt'], true), default => true }; ?>
          <a href="<?= $base . $navItem[1] ?>" class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 <?= $isActive ? 'bg-white/10 text-white font-medium' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
            <?php if ($isActive): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r <?= $accentBar ?>"></span><?php endif; ?>
            <?= uiIcon($navItem[2], 'w-5 h-5') ?><span><?= h($navItem[0]) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if (($org['business_type'] ?? 'both') === 'both'): ?>
        <a href="<?= $base ?>/choose" class="flex items-center gap-3 px-3 py-2.5 mt-4 rounded-lg border border-white/10 text-slate-300 hover:bg-white/5"><?= uiIcon('swap', 'w-5 h-5') ?><span>Switch to <?= $isTrade ? 'ServiceERP' : 'TradeERP' ?></span></a>
      <?php endif; ?>
    </nav>
    <div class="px-4 py-3 border-t border-white/5 flex items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-white/10 text-white text-sm font-semibold flex items-center justify-center"><?= h($userInitial) ?></div>
      <div class="min-w-0 flex-1 text-xs"><div class="text-slate-200 truncate"><?= h($user['name'] ?? '') ?></div><a href="/" class="text-slate-500 hover:text-slate-300">Home</a></div>
      <a href="<?= $base ?>/logout" class="p-2 text-slate-400 hover:text-white" aria-label="Logout" title="Logout"><?= uiIcon('logout', 'w-5 h-5') ?></a>
    </div>
<?php
$sidebarNav = ob_get_clean();
?>
<div class="min-h-screen flex" x-data="{ mobileNavOpen: false, userMenu: false }">
  <aside class="hidden md:flex md:w-64 md:flex-col md:sticky md:top-0 md:h-screen bg-slate-900 text-slate-200 shrink-0"><?= $sidebarNav ?></aside>

  <!-- Mobile off-canvas nav: same links as the desktop sidebar, just toggled by the hamburger / "More" button -->
  <div x-show="mobileNavOpen" x-cloak class="md:hidden fixed inset-0 z-40 bg-black/50" @click="mobileNavOpen = false"></div>
  <aside x-show="mobileNavOpen" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="md:hidden fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] flex flex-col bg-slate-900 text-slate-200"><?= $sidebarNav ?></aside>

  <main class="flex-1 min-w-0">
    <!-- Mobile top bar -->
    <header class="md:hidden sticky top-0 z-30 flex items-center gap-3 px-3 py-2.5 bg-white border-b border-slate-200" style="padding-top: max(0.625rem, env(safe-area-inset-top))">
      <button type="button" @click="mobileNavOpen = true" class="p-1.5 -ml-1 text-slate-700" aria-label="Open menu"><?= uiIcon('menu', 'w-6 h-6') ?></button>
      <div class="min-w-0 flex-1">
        <div class="text-sm font-semibold text-slate-900 truncate leading-tight"><?= h($pageTitle ?? $org['name'] ?? APP_NAME) ?></div>
        <div class="text-[11px] text-slate-500 truncate leading-tight"><?= h($org['name'] ?? APP_NAME) ?></div>
      </div>
      <span class="text-[11px] font-medium px-2 py-1 rounded-full <?= $accentSoft ?>"><?= $isTrade ? 'Trade' : 'Service' ?></span>
      <a href="<?= $base ?>/settings" aria-label="Account and password" class="w-8 h-8 rounded-full <?= $accentBg ?> text-white text-sm font-semibold flex items-center justify-center"><?= h($userInitial) ?></a>
    </header>

    <!-- Desktop top bar -->
    <header class="hidden md:flex sticky top-0 z-30 items-center justify-between px-6 py-3 bg-white/90 backdrop-blur border-b border-slate-200">
      <div class="text-sm text-slate-500"><span class="font-medium text-slate-800"><?= h($org["name"] ?? APP_NAME) ?></span> <span class="mx-1 text-slate-300">/</span> <?= $isTrade ? "TradeERP" : "ServiceERP" ?></div>
      <div class="flex items-center gap-3">
        <?php if (($org['business_type'] ?? 'both') === 'both'): ?>
          <a href="<?= $base ?>/choose" class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg px-3 py-1.5"><?= uiIcon('swap', 'w-4 h-4') ?>Switch workspace</a>
        <?php endif; ?>
        <div class="relative" @click.outside="userMenu = false">
          <button type="button" @click="userMenu = !userMenu" class="flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-slate-100">
            <span class="w-8 h-8 rounded-full <?= $accentBg ?> text-white text-sm font-semibold flex items-center justify-center"><?= h($userInitial) ?></span>
            <span class="text-sm font-medium text-slate-700"><?= h($user['name'] ?? '') ?></span>
            <?= uiIcon('chevron', 'w-4 h-4 text-slate-400') ?>
          </button>
          <div x-show="userMenu" x-cloak class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-slate-200 py-1 text-sm">
            <a href="<?= $base ?>/settings" class="block px-3 py-2 text-slate-700 hover:bg-slate-50">Account &amp; Password</a>
            <a href="/" class="block px-3 py-2 text-slate-700 hover:bg-slate-50">Home</a>
            <a href="<?= $base ?>/logout" class="block px-3 py-2 text-red-600 hover:bg-slate-50">Logout</a>
          </div>
        </div>
      </div>
    </header>
    <div class="p-4 md:p-6 pb-28 md:pb-8">
<?php foreach (['flash_success' => 'emerald', 'flash_error' => 'red'] as $flashKey => $flashTone): if (!empty($_SESSION[$flashKey])): ?>
  <div class="mb-4 text-sm rounded-lg px-4 py-3 border bg-<?= $flashTone ?>-50 border-<?= $flashTone ?>-200 text-<?= $flashTone ?>-800"><?= h($_SESSION[$flashKey]) ?></div>
<?php unset($_SESSION[$flashKey]); endif; endforeach; ?>
