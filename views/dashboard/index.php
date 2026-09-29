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

<div class="flex flex-wrap gap-3">
  <a href="<?= $base ?>/pos-mobile" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">📱 Quick Sale</a>
  <a href="<?= $base ?>/sales/newSale" class="bg-white border border-slate-200 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">New Sale (Desktop)</a>
  <a href="<?= $base ?>/products" class="bg-white border border-slate-200 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">Manage Products</a>
  <a href="<?= $base ?>/reports" class="bg-white border border-slate-200 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">View Reports</a>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
