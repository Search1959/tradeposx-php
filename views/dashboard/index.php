<?php
$org = Tenant::current(); $base = '/' . $org['slug'];
$pageTitle = 'Dashboard';
require __DIR__ . '/../layouts/header.php';

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', trim($user['name'] ?? ''))[0] ?: 'there';
$outOfStock = (int) $stockStatus['out'];
$totalCustomersOwing = count($topUdhaar);
?>

<!-- Greeting + primary actions -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4">
  <div>
    <div class="text-xs text-slate-500"><?= date('l, j F Y') ?></div>
    <h1 class="text-xl sm:text-2xl font-bold text-slate-900"><?= h($greeting) ?>, <?= h($firstName) ?></h1>
  </div>
  <div class="grid grid-cols-2 sm:flex gap-2">
    <a href="<?= $base ?>/sales" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl px-4 py-3 sm:py-2.5"><?= uiIcon('cart', 'w-5 h-5') ?>New Sale</a>
    <a href="<?= $base ?>/pos-mobile" class="inline-flex items-center justify-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl px-4 py-3 sm:py-2.5"><?= uiIcon('phone', 'w-5 h-5') ?>Quick Sale</a>
  </div>
</div>

<!-- Needs attention -->
<?php if ($lowStockCount > 0 || $outOfStock > 0 || $outstandingUdhaar > 0): ?>
  <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 flex items-start gap-3">
    <span class="mt-0.5 text-amber-600"><?= uiIcon('alert', 'w-5 h-5') ?></span>
    <div class="flex flex-wrap gap-x-4 gap-y-1">
      <span class="font-semibold">Needs attention:</span>
      <?php if ($outOfStock > 0): ?><a href="<?= $base ?>/products" class="underline"><?= $outOfStock ?> item<?= $outOfStock === 1 ? '' : 's' ?> out of stock</a><?php endif; ?>
      <?php if ($lowStockCount > 0): ?><a href="<?= $base ?>/products" class="underline"><?= (int)$lowStockCount ?> low on stock</a><?php endif; ?>
      <?php if ($outstandingUdhaar > 0): ?><a href="<?= $base ?>/customers" class="underline"><?= formatCurrency($outstandingUdhaar) ?> Udhaar to collect</a><?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
  <?= uiKpi($base . '/sales/list', "Today's Sales", kpiMoney($todaySales), (int)$sales['today']['cnt'] . ' sale' . ((int)$sales['today']['cnt'] === 1 ? '' : 's'), 'rupee', 'indigo') ?>
  <?= uiKpi('', 'This Week', kpiMoney((float)$sales['week']['total']), (int)$sales['week']['cnt'] . ' sales', 'trend', 'sky') ?>
  <?= uiKpi('', 'This Month', kpiMoney((float)$sales['month']['total']), (int)$sales['month']['cnt'] . ' sales', 'chart', 'violet') ?>
  <?= uiKpi($base . '/customers', 'Udhaar Pending', kpiMoney($outstandingUdhaar), $totalCustomersOwing > 0 ? 'Top ' . $totalCustomersOwing . ' owing' : 'All clear', 'users', 'amber', $outstandingUdhaar > 0 ? 'text-amber-600' : 'text-slate-900') ?>
  <?= uiKpi($base . '/products', 'Low Stock', (string)(int)$lowStockCount, 'at or below minimum', 'box', 'rose', $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900') ?>
  <?= uiKpi($base . '/products', 'Out of Stock', (string)$outOfStock, 'need reordering', 'alert', 'slate', $outOfStock > 0 ? 'text-rose-600' : 'text-slate-900') ?>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-semibold text-slate-900">Sales</h2>
      <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-medium" x-data="{ r: 'week' }" x-init="$watch('r', v => window.setSalesRange && window.setSalesRange(v))">
        <button type="button" @click="r = 'week'" :class="r === 'week' ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="px-3 py-1 rounded-md">7 days</button>
        <button type="button" @click="r = 'months'" :class="r === 'months' ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="px-3 py-1 rounded-md">6 months</button>
      </div>
    </div>
    <div class="relative h-56"><canvas id="salesChart"></canvas></div>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <h2 class="text-sm font-semibold text-slate-900 mb-3">Stock health</h2>
    <?php if (array_sum($stockStatus) > 0): ?>
      <div class="relative h-44"><canvas id="stockChart"></canvas></div>
      <div class="mt-3 flex justify-center gap-4 text-xs text-slate-600">
        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-emerald-500"></i>In stock <?= (int)$stockStatus['ok'] ?></span>
        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-amber-500"></i>Low <?= (int)$stockStatus['low'] ?></span>
        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-rose-500"></i>Out <?= (int)$stockStatus['out'] ?></span>
      </div>
    <?php else: ?>
      <div class="h-44 flex items-center justify-center text-sm text-slate-400 text-center">Add products to see<br>your stock health.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Lists -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <div class="flex items-center justify-between mb-2"><h2 class="text-sm font-semibold text-slate-900">Recent sales</h2><a href="<?= $base ?>/sales/list" class="text-xs <?= $accentText ?>">View all</a></div>
    <?php foreach ($recentSales as $s): ?>
      <a href="<?= $base ?>/sales/receipt/<?= (int)$s['id'] ?>" class="flex items-center justify-between py-2.5 border-t border-slate-100 first:border-0">
        <div class="min-w-0"><div class="text-sm font-medium text-slate-800 truncate"><?= h($s['customer_name'] ?: 'Walk-in') ?></div>
          <div class="text-xs text-slate-500"><?= h($s['sale_number'] ?? '') ?> · <?= date('j M, g:i a', strtotime($s['created_at'])) ?></div></div>
        <div class="text-right shrink-0 pl-3"><div class="text-sm font-semibold text-slate-900"><?= formatCurrency((float)$s['grand_total']) ?></div>
          <?php if (($s['payment_status'] ?? 'PAID') !== 'PAID'): ?><div class="text-[11px] font-medium text-amber-600"><?= h(ucfirst(strtolower($s['payment_status']))) ?></div><?php endif; ?></div>
      </a>
    <?php endforeach; ?>
    <?php if (!$recentSales): ?><div class="py-6 text-center text-sm text-slate-400">No sales yet — make your first one.</div><?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <div class="flex items-center justify-between mb-2"><h2 class="text-sm font-semibold text-slate-900">Udhaar to collect</h2><a href="<?= $base ?>/customers" class="text-xs <?= $accentText ?>">View all</a></div>
    <?php foreach ($topUdhaar as $c): ?>
      <a href="<?= $base ?>/customers/statement/<?= (int)$c['id'] ?>" class="flex items-center justify-between py-2.5 border-t border-slate-100 first:border-0">
        <div class="flex items-center gap-3 min-w-0">
          <span class="w-8 h-8 rounded-full bg-amber-50 text-amber-700 text-sm font-semibold flex items-center justify-center shrink-0"><?= h(strtoupper(mb_substr($c['name'], 0, 1))) ?></span>
          <div class="min-w-0"><div class="text-sm font-medium text-slate-800 truncate"><?= h($c['name']) ?></div><div class="text-xs text-slate-500"><?= h($c['mobile']) ?></div></div>
        </div>
        <div class="text-sm font-semibold text-amber-600 shrink-0 pl-3"><?= formatCurrency((float)$c['current_balance']) ?></div>
      </a>
    <?php endforeach; ?>
    <?php if (!$topUdhaar): ?><div class="py-6 text-center text-sm text-slate-400">No pending Udhaar. Nice.</div><?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <div class="flex items-center justify-between mb-2"><h2 class="text-sm font-semibold text-slate-900">Top products · 30 days</h2><a href="<?= $base ?>/reports" class="text-xs <?= $accentText ?>">Reports</a></div>
    <?php $maxRev = max(1, (float)($topProducts[0]['revenue'] ?? 0)); foreach ($topProducts as $p): $pct = round((float)$p['revenue'] / $maxRev * 100); ?>
      <div class="py-2.5 border-t border-slate-100 first:border-0">
        <div class="flex items-center justify-between text-sm"><span class="font-medium text-slate-800 truncate pr-3"><?= h($p['product_name']) ?></span><span class="font-semibold text-slate-900 shrink-0"><?= formatCurrency((float)$p['revenue']) ?></span></div>
        <div class="mt-1.5 h-1.5 rounded-full bg-slate-100"><div class="h-1.5 rounded-full bg-indigo-500" style="width: <?= $pct ?>%"></div></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$topProducts): ?><div class="py-6 text-center text-sm text-slate-400">Sales will rank your best sellers here.</div><?php endif; ?>
  </div>
</div>

<!-- Quick access -->
<div class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Quick access</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
  <?php
  $quickLinks = [
      ['/products', 'box', 'teal', 'Products', 'Stock & pricing'],
      ['/customers', 'users', 'amber', 'Customers', 'Udhaar & reminders'],
      ['/purchases', 'receipt', 'violet', 'Purchases', 'Suppliers & bills'],
      ['/expenses', 'cash', 'rose', 'Expenses', 'Track spending'],
      ['/reports', 'chart', 'emerald', 'Reports', 'Sales & margins'],
      ['/outreach', 'chat', 'sky', 'Outreach', 'Reviews & promos'],
  ];
  if ($isAdmin) { $quickLinks[] = ['/team', 'users', 'pink', 'Team', 'Manage staff']; $quickLinks[] = ['/activity-log', 'clipboard', 'slate', 'Activity Log', 'Audit trail']; }
  foreach ($quickLinks as $q): ?>
    <a href="<?= $base . $q[0] ?>" class="flex items-center gap-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-3 hover:border-<?= $q[2] ?>-300 hover:shadow-md transition">
      <span class="w-10 h-10 rounded-xl bg-<?= $q[2] ?>-50 text-<?= $q[2] ?>-600 flex items-center justify-center shrink-0"><?= uiIcon($q[1], 'w-5 h-5') ?></span>
      <span class="min-w-0"><span class="block text-sm font-semibold text-slate-900 truncate"><?= h($q[3]) ?></span><span class="block text-xs text-slate-500 truncate"><?= h($q[4]) ?></span></span>
    </a>
  <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  const week = <?= json_encode($salesWeek) ?>, months = <?= json_encode($salesMonths) ?>;
  const inr = v => '₹' + Number(v).toLocaleString('en-IN', { maximumFractionDigits: 0 });
  const el = document.getElementById('salesChart');
  if (el && window.Chart) {
    const chart = new Chart(el, {
      type: 'bar',
      data: { labels: week.labels, datasets: [{ data: week.values, backgroundColor: '#6366f1', borderRadius: 6, maxBarThickness: 36 }] },
      options: { responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => inr(c.parsed.y) } } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: v => v >= 1000 ? (v / 1000) + 'k' : v }, grid: { color: '#f1f5f9' } } } }
    });
    window.setSalesRange = r => { const d = r === 'week' ? week : months; chart.data.labels = d.labels; chart.data.datasets[0].data = d.values; chart.update(); };
  }
  const st = document.getElementById('stockChart');
  if (st && window.Chart) {
    new Chart(st, { type: 'doughnut',
      data: { labels: ['In stock', 'Low', 'Out'], datasets: [{ data: [<?= (int)$stockStatus['ok'] ?>, <?= (int)$stockStatus['low'] ?>, <?= (int)$stockStatus['out'] ?>], backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'], borderWidth: 0 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } } });
  }
})();
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
