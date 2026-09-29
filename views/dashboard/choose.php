<?php $org = Tenant::current(); $base = '/' . $org['slug']; $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Choose Workspace · <?= h($org['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <div class="max-w-3xl mx-auto px-4 py-12">
    <div class="text-center mb-10">
      <div class="text-sm text-slate-500 mb-1">Welcome back, <?= h($user['name'] ?? '') ?></div>
      <h1 class="text-2xl font-semibold text-slate-900"><?= h($org['name']) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Where would you like to work today?</p>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
      <a href="<?= $base ?>/dashboard" class="group bg-white rounded-2xl border-2 border-slate-200 hover:border-indigo-500 p-8 transition shadow-sm hover:shadow-md">
        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl mb-4">🛒</div>
        <h2 class="text-lg font-semibold text-slate-900 mb-1 group-hover:text-indigo-600">TradeERP</h2>
        <p class="text-sm text-slate-500 mb-4">Sales, products, stock, purchases &amp; Udhaar.</p>
        <div class="text-xs text-slate-400">Today's Sales</div>
        <div class="text-xl font-semibold text-slate-900"><?= formatCurrency($todaySales) ?></div>
      </a>

      <a href="<?= $base ?>/appointments" class="group bg-white rounded-2xl border-2 border-slate-200 hover:border-indigo-500 p-8 transition shadow-sm hover:shadow-md">
        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mb-4">🛠️</div>
        <h2 class="text-lg font-semibold text-slate-900 mb-1 group-hover:text-indigo-600">ServiceERP</h2>
        <p class="text-sm text-slate-500 mb-4">Appointments, job cards, staff &amp; service invoices.</p>
        <div class="text-xs text-slate-400">Today's Appointments</div>
        <div class="text-xl font-semibold text-slate-900"><?= (int)$todayAppointments ?> <span class="text-sm font-normal text-slate-400">· <?= (int)$pendingJobCards ?> pending job cards</span></div>
      </a>
    </div>

    <div class="text-center mt-8 space-x-3">
      <a href="/" class="text-sm text-slate-500 hover:text-slate-700">Home</a>
      <span class="text-slate-300">·</span>
      <a href="<?= $base ?>/logout" class="text-sm text-slate-500 hover:text-slate-700">Logout</a>
    </div>
  </div>
</body>
</html>
