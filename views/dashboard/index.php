<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4">TradeERP Dashboard</h1>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <a href="<?= $base ?>/sales" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-indigo-300">
    <div class="text-xs text-slate-500">Today's Sales</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= formatCurrency($todaySales) ?></div>
    <div class="text-xs text-slate-400 mt-1"><?= (int)$sales['today']['cnt'] ?> sale<?= (int)$sales['today']['cnt'] === 1 ? '' : 's' ?></div>
  </a>
  <a href="<?= $base ?>/customers" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-indigo-300">
    <div class="text-xs text-slate-500">Outstanding Udhaar</div>
    <div class="text-2xl font-semibold <?= $outstandingUdhaar > 0 ? 'text-amber-600' : 'text-slate-900' ?> mt-1"><?= formatCurrency($outstandingUdhaar) ?></div>
  </a>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">This Week's Sales</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= formatCurrency((float)$sales['week']['total']) ?></div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">This Month's Sales</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= formatCurrency((float)$sales['month']['total']) ?></div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <a href="<?= $base ?>/products" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-indigo-300">
    <div class="text-xs text-slate-500">Low Stock Items</div>
    <div class="text-xl font-semibold <?= $lowStockCount > 0 ? 'text-red-600' : 'text-slate-900' ?> mt-1"><?= (int)$lowStockCount ?></div>
  </a>
</div>

<div class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Quick Access</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
  <?php
  $quickLinks = [
      ['href' => '/pos-mobile', 'icon' => '📱', 'bg' => 'bg-indigo-100', 'title' => 'Quick Sale', 'sub' => 'Mobile POS'],
      ['href' => '/sales/newSale', 'icon' => '🛒', 'bg' => 'bg-blue-100', 'title' => 'New Sale', 'sub' => 'Desktop POS'],
      ['href' => '/products', 'icon' => '📦', 'bg' => 'bg-teal-100', 'title' => 'Products', 'sub' => 'Stock & pricing'],
      ['href' => '/customers', 'icon' => '👥', 'bg' => 'bg-amber-100', 'title' => 'Customers', 'sub' => 'Udhaar & reminders'],
      ['href' => '/purchases', 'icon' => '🧾', 'bg' => 'bg-purple-100', 'title' => 'Purchases', 'sub' => 'Suppliers & bills'],
      ['href' => '/reports', 'icon' => '📊', 'bg' => 'bg-green-100', 'title' => 'Reports', 'sub' => 'Sales & margins'],
      ['href' => '/outreach', 'icon' => '💬', 'bg' => 'bg-emerald-100', 'title' => 'Outreach', 'sub' => 'Reviews & promos'],
  ];
  if (in_array($user['role'] ?? '', ['owner', 'admin'], true)) {
      $quickLinks[] = ['href' => '/team', 'icon' => '👤', 'bg' => 'bg-pink-100', 'title' => 'Team', 'sub' => 'Manage staff'];
      $quickLinks[] = ['href' => '/activity-log', 'icon' => '📋', 'bg' => 'bg-slate-200', 'title' => 'Activity Log', 'sub' => 'Audit trail'];
  }
  ?>
  <?php foreach ($quickLinks as $q): ?>
    <a href="<?= $base . $q['href'] ?>" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:border-indigo-300 hover:shadow-md transition">
      <div class="w-10 h-10 rounded-lg <?= $q['bg'] ?> flex items-center justify-center text-xl mb-2"><?= $q['icon'] ?></div>
      <div class="text-sm font-semibold text-slate-900"><?= h($q['title']) ?></div>
      <div class="text-xs text-slate-400"><?= h($q['sub']) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
