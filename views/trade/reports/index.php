<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Reports</h1>
  <div class="flex gap-1 text-sm">
    <?php foreach ([7,30,90] as $d): ?>
      <a href="?days=<?= $d ?>" class="px-3 py-1.5 rounded <?= $days===$d ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>"><?= $d ?>d</a>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Revenue (<?= $days ?>d)</div>
    <div class="text-xl font-semibold text-slate-900 mt-1"><?= formatCurrency($margin['revenue']) ?></div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Margin (<?= $days ?>d)</div>
    <div class="text-xl font-semibold text-green-600 mt-1"><?= formatCurrency($margin['margin']) ?></div>
    <div class="text-xs text-slate-400 mt-1"><?= $margin['marginPercent'] ?>% margin ratio</div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Total Expenses (all-time)</div>
    <div class="text-xl font-semibold text-slate-900 mt-1"><?= formatCurrency($expensesTotal) ?></div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Net Profit (<?= $days ?>d margin − all-time expenses)</div>
    <div class="text-xl font-semibold <?= $netProfit >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1"><?= formatCurrency($netProfit) ?></div>
  </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Today's Sales</div>
    <div class="text-lg font-semibold text-slate-900 mt-1"><?= formatCurrency((float)$sales['today']['total']) ?></div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">This Week</div>
    <div class="text-lg font-semibold text-slate-900 mt-1"><?= formatCurrency((float)$sales['week']['total']) ?></div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Trade Udhaar Outstanding</div>
    <div class="text-lg font-semibold text-amber-600 mt-1"><?= formatCurrency($outstandingUdhaar) ?></div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Service Balance Outstanding</div>
    <div class="text-lg font-semibold text-amber-600 mt-1"><?= formatCurrency($serviceOutstanding) ?></div>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <div class="px-4 py-3 border-b border-slate-100 font-medium text-sm text-slate-900">Top-Selling Products (<?= $days ?> days)</div>
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Product</th><th class="text-right px-4 py-2">Qty Sold</th><th class="text-right px-4 py-2">Revenue</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($topProducts as $p): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($p['product_name']) ?></td>
        <td class="px-4 py-2 text-right"><?= rtrim(rtrim(number_format((float)$p['qty_sold'],2),'0'),'.') ?></td>
        <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$p['revenue']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($topProducts)): ?>
      <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">No sales in this period yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
