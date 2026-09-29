<?php require __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4">Dashboard</h1>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Today's Sales</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1">₹0</div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Outstanding Udhaar</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1">₹0</div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Today's Appointments</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1">0</div>
  </div>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Pending Job Cards</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1">0</div>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 text-sm text-slate-500">
  Foundation (P0) is live — auth, multi-tenancy and both module shells are wired up.
  TradeERP and ServiceERP screens are being built next (P1/P2).
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
