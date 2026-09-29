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

<div class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Quick Access</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
  <?php
  $quickLinks = [
      ['href' => '/appointments/new', 'icon' => '📅', 'bg' => 'bg-amber-100', 'title' => 'Appointment', 'sub' => 'Book new'],
      ['href' => '/job-cards/new', 'icon' => '🛠️', 'bg' => 'bg-blue-100', 'title' => 'Job Card', 'sub' => 'Start new'],
      ['href' => '/service-invoices/new', 'icon' => '🧾', 'bg' => 'bg-indigo-100', 'title' => 'New Bill', 'sub' => 'Service invoice'],
      ['href' => '/job-cards', 'icon' => '📋', 'bg' => 'bg-teal-100', 'title' => 'Job Cards', 'sub' => 'All jobs'],
      ['href' => '/service-customers', 'icon' => '👥', 'bg' => 'bg-purple-100', 'title' => 'Customers', 'sub' => 'Contacts'],
      ['href' => '/reports', 'icon' => '📊', 'bg' => 'bg-green-100', 'title' => 'Reports', 'sub' => 'Revenue & staff'],
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
