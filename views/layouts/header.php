<?php
/** Included by every tenant-scoped view. Expects $pageTitle to be set by the caller. */
$org = Tenant::current();
$user = Auth::user();

// Which side of the app is currently active, so the sidebar shows one menu,
// not both stacked together. A single-type org has no choice - it's always
// its own type. A "both" org's active side is whichever module the visitor
// most recently opened, tracked in session (defaults to trade until they
// pick, or visit a service-side page, or use "Switch Workspace" below).
$serviceModules = ['service-dashboard', 'appointments', 'job-cards', 'service-invoices', 'service-quotations', 'service-customers', 'service-staff', 'service-items'];
$tradeModules = ['dashboard', 'sales', 'pos', 'products', 'customers', 'suppliers', 'purchases', 'reports', 'expenses'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h($pageTitle ?? 'Dashboard') ?> · <?= h($org['name'] ?? APP_NAME) ?></title>
<link rel="manifest" href="/assets/manifest.webmanifest">
<meta name="theme-color" content="<?= h($org['theme_color'] ?? '#4f46e5') ?>">
<script src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  [x-cloak] { display: none !important; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
</style>
</head>
<body class="bg-slate-50 text-slate-900">
<?php
$base = $org ? '/' . h($org['slug']) : '';
// Built once, rendered twice (desktop sidebar + mobile off-canvas panel) so the
// two navs can never silently drift apart.
ob_start();
?>
    <div class="px-4 py-4 text-lg font-semibold text-white border-b border-slate-800 flex items-center justify-between">
      <?= h($org['name'] ?? APP_NAME) ?>
      <button type="button" @click="mobileNavOpen = false" class="md:hidden text-slate-400 hover:text-white text-xl leading-none">✕</button>
    </div>
    <?php if (($org['business_type'] ?? 'both') === 'both'): ?>
      <div class="px-3 pt-3 pb-1">
        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded <?= $workspace === 'trade' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-emerald-500/20 text-emerald-300' ?>">
          <?= $workspace === 'trade' ? '🛒 TradeERP' : '🛠️ ServiceERP' ?>
        </span>
      </div>
    <?php endif; ?>
    <nav class="flex-1 px-2 py-3 space-y-1 text-sm overflow-y-auto">
      <a href="<?= $base . ($workspace === 'service' ? '/service-dashboard' : '/dashboard') ?>" class="block px-3 py-2 rounded hover:bg-slate-800">Dashboard</a>
      <a href="<?= $base ?>/pos-mobile" class="block px-3 py-2 rounded hover:bg-slate-800">📱 Quick Sale (Mobile)</a>
      <?php if ($workspace === 'trade'): ?>
        <a href="<?= $base ?>/sales" class="block px-3 py-2 rounded hover:bg-slate-800">POS / Sales</a>
        <a href="<?= $base ?>/products" class="block px-3 py-2 rounded hover:bg-slate-800">Products</a>
        <a href="<?= $base ?>/customers" class="block px-3 py-2 rounded hover:bg-slate-800">Customers / Udhaar</a>
        <a href="<?= $base ?>/purchases" class="block px-3 py-2 rounded hover:bg-slate-800">Purchases</a>
        <a href="<?= $base ?>/suppliers" class="block px-3 py-2 rounded hover:bg-slate-800">Suppliers</a>
        <a href="<?= $base ?>/reports" class="block px-3 py-2 rounded hover:bg-slate-800">Reports</a>
      <?php else: ?>
        <a href="<?= $base ?>/service-invoices/new" class="block px-3 py-2 rounded hover:bg-slate-800">🧾 New Bill (POS)</a>
        <a href="<?= $base ?>/appointments" class="block px-3 py-2 rounded hover:bg-slate-800">Appointments</a>
        <a href="<?= $base ?>/job-cards" class="block px-3 py-2 rounded hover:bg-slate-800">Job Cards</a>
        <a href="<?= $base ?>/service-invoices" class="block px-3 py-2 rounded hover:bg-slate-800">Service Invoices</a>
        <a href="<?= $base ?>/service-quotations" class="block px-3 py-2 rounded hover:bg-slate-800">Quotations</a>
        <a href="<?= $base ?>/service-customers" class="block px-3 py-2 rounded hover:bg-slate-800">Service Customers</a>
        <a href="<?= $base ?>/service-items" class="block px-3 py-2 rounded hover:bg-slate-800">Service Items</a>
        <a href="<?= $base ?>/service-staff" class="block px-3 py-2 rounded hover:bg-slate-800">Staff</a>
      <?php endif; ?>
      <?php if (($org['business_type'] ?? 'both') === 'both'): ?>
        <a href="<?= $base ?>/choose" class="block px-3 py-2 mt-2 rounded border border-slate-800 text-slate-400 hover:bg-slate-800 hover:text-slate-200">⇄ Switch Workspace</a>
      <?php endif; ?>
      <?php if (in_array($user['role'] ?? '', ['owner', 'admin'], true)): ?>
        <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-wide text-slate-500">System Admin</div>
        <a href="<?= $base ?>/team" class="block px-3 py-2 rounded hover:bg-slate-800">👥 Team</a>
        <a href="<?= $base ?>/activity-log" class="block px-3 py-2 rounded hover:bg-slate-800">📋 Activity Log</a>
      <?php endif; ?>
    </nav>
    <div class="px-3 py-3 border-t border-slate-800 text-xs space-x-2">
      <div class="text-slate-300 mb-1"><?= h($user['name'] ?? '') ?></div>
      <a href="<?= $base ?>/settings" class="text-slate-500 hover:text-slate-300">Settings</a>
      <span class="text-slate-700">·</span>
      <a href="/" class="text-slate-500 hover:text-slate-300">Home</a>
      <span class="text-slate-700">·</span>
      <a href="<?= $base ?>/logout" class="text-slate-500 hover:text-slate-300">Logout</a>
    </div>
<?php
$sidebarNav = ob_get_clean();
?>
<div class="min-h-screen flex" x-data="{ mobileNavOpen: false }">
  <aside class="hidden md:flex md:w-56 md:flex-col bg-slate-900 text-slate-200"><?= $sidebarNav ?></aside>

  <!-- Mobile off-canvas nav: same links as the desktop sidebar, just toggled by the hamburger button below -->
  <div x-show="mobileNavOpen" x-cloak class="md:hidden fixed inset-0 z-40 bg-black/50" @click="mobileNavOpen = false"></div>
  <aside x-show="mobileNavOpen" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="md:hidden fixed inset-y-0 left-0 z-50 w-72 max-w-[80vw] flex flex-col bg-slate-900 text-slate-200"><?= $sidebarNav ?></aside>

  <main class="flex-1 min-w-0">
    <header class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900 text-white">
      <button type="button" @click="mobileNavOpen = true" class="text-2xl leading-none px-1" aria-label="Open menu">☰</button>
      <span class="font-semibold truncate px-2"><?= h($org['name'] ?? APP_NAME) ?></span>
      <a href="<?= $base ?>/logout" class="text-xs text-slate-300 whitespace-nowrap">Logout</a>
    </header>
    <div class="p-4 md:p-6">
