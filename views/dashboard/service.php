<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4">ServiceERP Dashboard</h1>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <a href="<?= $base ?>/service-invoices" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-indigo-300">
    <div class="text-xs text-slate-500">Today's Service Revenue</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= formatCurrency($todayRevenue) ?></div>
    <div class="text-xs text-slate-400 mt-1"><?= (int)$revenue['today']['cnt'] ?> invoice<?= (int)$revenue['today']['cnt'] === 1 ? '' : 's' ?></div>
  </a>
  <a href="<?= $base ?>/appointments" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-indigo-300">
    <div class="text-xs text-slate-500">Today's Appointments</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= (int)$todayAppointments ?></div>
    <div class="text-xs text-slate-400 mt-1"><?= (int)$upcomingAppointments ?> upcoming</div>
  </a>
  <a href="<?= $base ?>/job-cards" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-indigo-300">
    <div class="text-xs text-slate-500">Pending Job Cards</div>
    <div class="text-2xl font-semibold <?= $pendingJobCards > 0 ? 'text-amber-600' : 'text-slate-900' ?> mt-1"><?= (int)$pendingJobCards ?></div>
  </a>
  <a href="<?= $base ?>/service-customers" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-indigo-300">
    <div class="text-xs text-slate-500">Outstanding (Jobs + Invoices)</div>
    <div class="text-2xl font-semibold <?= $serviceOutstanding > 0 ? 'text-amber-600' : 'text-slate-900' ?> mt-1"><?= formatCurrency($serviceOutstanding) ?></div>
  </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">This Week's Service Revenue</div>
    <div class="text-xl font-semibold text-slate-900 mt-1"><?= formatCurrency((float)$revenue['week']['total']) ?></div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">This Month's Service Revenue</div>
    <div class="text-xl font-semibold text-slate-900 mt-1"><?= formatCurrency((float)$revenue['month']['total']) ?></div>
  </div>
</div>

<div class="flex flex-wrap gap-3">
  <a href="<?= $base ?>/appointments/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Appointment</a>
  <a href="<?= $base ?>/job-cards/new" class="bg-white border border-slate-200 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">+ New Job Card</a>
  <a href="<?= $base ?>/service-invoices/new" class="bg-white border border-slate-200 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">New Service Invoice</a>
  <a href="<?= $base ?>/reports" class="bg-white border border-slate-200 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">View Reports</a>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
