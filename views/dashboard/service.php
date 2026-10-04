<?php
$org = Tenant::current(); $base = '/' . $org['slug'];
$pageTitle = 'Dashboard';
require __DIR__ . '/../layouts/header.php';

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', trim($user['name'] ?? ''))[0] ?: 'there';
$jobTotal = array_sum($jobStatus);
$apptTone = ['Scheduled' => 'bg-sky-50 text-sky-700', 'In-Progress' => 'bg-amber-50 text-amber-700', 'Completed' => 'bg-emerald-50 text-emerald-700', 'No-Show' => 'bg-rose-50 text-rose-700'];
?>

<!-- Greeting + primary actions -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4">
  <div>
    <div class="text-xs text-slate-500"><?= date('l, j F Y') ?></div>
    <h1 class="text-xl sm:text-2xl font-bold text-slate-900"><?= h($greeting) ?>, <?= h($firstName) ?></h1>
  </div>
  <div class="grid grid-cols-3 sm:flex gap-2">
    <a href="<?= $base ?>/service-invoices/new" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl px-2 sm:px-4 py-3 sm:py-2.5 text-xs sm:text-sm"><?= uiIcon('receipt', 'w-5 h-5') ?>New Bill</a>
    <a href="<?= $base ?>/job-cards/new" class="inline-flex items-center justify-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl px-2 sm:px-4 py-3 sm:py-2.5 text-xs sm:text-sm"><?= uiIcon('wrench', 'w-5 h-5') ?>Job Card</a>
    <a href="<?= $base ?>/appointments/new" class="inline-flex items-center justify-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl px-2 sm:px-4 py-3 sm:py-2.5 text-xs sm:text-sm"><?= uiIcon('calendar', 'w-5 h-5') ?>Booking</a>
  </div>
</div>

<!-- Needs attention -->
<?php if ($serviceOutstanding > 0 || $pendingJobCards > 0): ?>
  <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 flex items-start gap-3">
    <span class="mt-0.5 text-amber-600"><?= uiIcon('alert', 'w-5 h-5') ?></span>
    <div class="flex flex-wrap gap-x-4 gap-y-1">
      <span class="font-semibold">Needs attention:</span>
      <?php if ($pendingJobCards > 0): ?><a href="<?= $base ?>/job-cards" class="underline"><?= (int)$pendingJobCards ?> job card<?= $pendingJobCards === 1 ? '' : 's' ?> open</a><?php endif; ?>
      <?php if ($serviceOutstanding > 0): ?><a href="<?= $base ?>/service-invoices" class="underline"><?= formatCurrency($serviceOutstanding) ?> dues to collect</a><?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
  <?= uiKpi($base . '/service-invoices', "Today's Revenue", kpiMoney($todayRevenue), (int)$revenue['today']['cnt'] . ' invoice' . ((int)$revenue['today']['cnt'] === 1 ? '' : 's'), 'rupee', 'emerald') ?>
  <?= uiKpi('', 'This Week', kpiMoney((float)$revenue['week']['total']), (int)$revenue['week']['cnt'] . ' invoices', 'trend', 'sky') ?>
  <?= uiKpi('', 'This Month', kpiMoney((float)$revenue['month']['total']), (int)$revenue['month']['cnt'] . ' invoices', 'chart', 'violet') ?>
  <?= uiKpi($base . '/appointments', "Today's Bookings", (string)(int)$todayAppointments, (int)$upcomingAppointments . ' upcoming', 'calendar', 'indigo') ?>
  <?= uiKpi($base . '/job-cards', 'Open Job Cards', (string)(int)$pendingJobCards, 'pending or in progress', 'wrench', 'amber', $pendingJobCards > 0 ? 'text-amber-600' : 'text-slate-900') ?>
  <?= uiKpi($base . '/service-invoices', 'Dues Outstanding', kpiMoney($serviceOutstanding), 'jobs + invoices', 'alert', 'rose', $serviceOutstanding > 0 ? 'text-rose-600' : 'text-slate-900') ?>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-semibold text-slate-900">Revenue</h2>
      <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-medium" x-data="{ r: 'week' }" x-init="$watch('r', v => window.setRevenueRange && window.setRevenueRange(v))">
        <button type="button" @click="r = 'week'" :class="r === 'week' ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="px-3 py-1 rounded-md">7 days</button>
        <button type="button" @click="r = 'months'" :class="r === 'months' ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="px-3 py-1 rounded-md">6 months</button>
      </div>
    </div>
    <div class="relative h-56"><canvas id="revenueChart"></canvas></div>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <h2 class="text-sm font-semibold text-slate-900 mb-3">Job cards</h2>
    <?php if ($jobTotal > 0): ?>
      <div class="relative h-44"><canvas id="jobChart"></canvas></div>
      <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-slate-600">
        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-amber-500"></i>Pending <?= (int)$jobStatus['Pending'] ?></span>
        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-sky-500"></i>In progress <?= (int)$jobStatus['In-Progress'] ?></span>
        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-emerald-500"></i>Completed <?= (int)$jobStatus['Completed'] ?></span>
        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-slate-400"></i>Delivered <?= (int)$jobStatus['Delivered'] ?></span>
      </div>
    <?php else: ?>
      <div class="h-44 flex items-center justify-center text-sm text-slate-400 text-center">Create a job card to<br>track repairs and work.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Lists -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <div class="flex items-center justify-between mb-2"><h2 class="text-sm font-semibold text-slate-900">Today's bookings</h2><a href="<?= $base ?>/appointments" class="text-xs <?= $accentText ?>">View all</a></div>
    <?php foreach ($todaysAppts as $a): ?>
      <a href="<?= $base ?>/appointments" class="flex items-center justify-between py-2.5 border-t border-slate-100 first:border-0">
        <div class="flex items-center gap-3 min-w-0">
          <span class="w-12 shrink-0 text-center text-xs font-semibold text-slate-700"><?= date('g:i', strtotime($a['appt_time'])) ?><span class="block text-[10px] font-normal text-slate-400"><?= date('A', strtotime($a['appt_time'])) ?></span></span>
          <div class="min-w-0"><div class="text-sm font-medium text-slate-800 truncate"><?= h($a['customer_name'] ?: 'Walk-in') ?></div>
            <div class="text-xs text-slate-500 truncate"><?= h($a['service_name'] ?: 'Service') ?><?= $a['staff_name'] ? ' · ' . h($a['staff_name']) : '' ?></div></div>
        </div>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full shrink-0 <?= $apptTone[$a['status']] ?? 'bg-slate-100 text-slate-600' ?>"><?= h($a['status']) ?></span>
      </a>
    <?php endforeach; ?>
    <?php if (!$todaysAppts): ?><div class="py-6 text-center text-sm text-slate-400">Nothing booked for today.</div><?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
    <div class="flex items-center justify-between mb-2"><h2 class="text-sm font-semibold text-slate-900">Unpaid invoices</h2><a href="<?= $base ?>/service-invoices" class="text-xs <?= $accentText ?>">View all</a></div>
    <?php foreach ($unpaidInvoices as $i): ?>
      <a href="<?= $base ?>/service-invoices/view/<?= (int)$i['id'] ?>" class="flex items-center justify-between py-2.5 border-t border-slate-100 first:border-0">
        <div class="min-w-0"><div class="text-sm font-medium text-slate-800 truncate"><?= h($i['customer_name'] ?: 'Walk-in') ?></div><div class="text-xs text-slate-500"><?= h($i['invoice_no']) ?></div></div>
        <div class="text-sm font-semibold text-rose-600 shrink-0 pl-3"><?= formatCurrency((float)$i['balance_amount']) ?></div>
      </a>
    <?php endforeach; ?>
    <?php if (!$unpaidInvoices): ?><div class="py-6 text-center text-sm text-slate-400">All invoices are paid. Nice.</div><?php endif; ?>
  </div>
</div>

<!-- Quick access -->
<div class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Quick access</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
  <?php
  $quickLinks = [
      ['/service-invoices', 'document', 'indigo', 'Invoices', 'All service bills'],
      ['/service-quotations', 'clipboard', 'violet', 'Quotations', 'Estimates'],
      ['/service-items', 'tag', 'teal', 'Service Items', 'Menu & pricing'],
      ['/service-customers', 'users', 'amber', 'Customers', 'Contacts'],
      ['/service-staff', 'briefcase', 'sky', 'Staff', 'Commission'],
      ['/expenses', 'cash', 'rose', 'Expenses', 'Track spending'],
      ['/reports', 'chart', 'emerald', 'Reports', 'Revenue & staff'],
      ['/outreach', 'chat', 'pink', 'Outreach', 'Reviews & promos'],
  ];
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
  const week = <?= json_encode($revenueWeek) ?>, months = <?= json_encode($revenueMonths) ?>;
  const inr = v => '₹' + Number(v).toLocaleString('en-IN', { maximumFractionDigits: 0 });
  const el = document.getElementById('revenueChart');
  if (el && window.Chart) {
    const chart = new Chart(el, {
      type: 'bar',
      data: { labels: week.labels, datasets: [{ data: week.values, backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 36 }] },
      options: { responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => inr(c.parsed.y) } } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: v => v >= 1000 ? (v / 1000) + 'k' : v }, grid: { color: '#f1f5f9' } } } }
    });
    window.setRevenueRange = r => { const d = r === 'week' ? week : months; chart.data.labels = d.labels; chart.data.datasets[0].data = d.values; chart.update(); };
  }
  const jc = document.getElementById('jobChart');
  if (jc && window.Chart) {
    new Chart(jc, { type: 'doughnut',
      data: { labels: ['Pending', 'In progress', 'Completed', 'Delivered'], datasets: [{ data: [<?= (int)$jobStatus['Pending'] ?>, <?= (int)$jobStatus['In-Progress'] ?>, <?= (int)$jobStatus['Completed'] ?>, <?= (int)$jobStatus['Delivered'] ?>], backgroundColor: ['#f59e0b', '#0ea5e9', '#10b981', '#94a3b8'], borderWidth: 0 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } } });
  }
})();
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
