<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$statusColors = ['Scheduled'=>'bg-slate-100 text-slate-600','In-Progress'=>'bg-blue-100 text-blue-700','Completed'=>'bg-green-100 text-green-700','Cancelled'=>'bg-red-100 text-red-700','No-Show'=>'bg-amber-100 text-amber-700'];
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Appointments</h1>
  <a href="<?= $base ?>/appointments/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Appointment</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Date/Time</th><th class="text-left px-4 py-2">Customer</th><th class="text-left px-4 py-2">Service</th><th class="text-left px-4 py-2">Staff</th><th class="text-center px-4 py-2">Status</th><th class="px-4 py-2"></th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($appointments as $a): ?>
      <tr>
        <td class="px-4 py-2 text-slate-600"><?= formatDate($a['appt_date']) ?> <?= h(substr($a['appt_time'],0,5)) ?></td>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($a['customer_name'] ?? '') ?><div class="text-xs text-slate-400"><?= h($a['mobile'] ?? '') ?></div></td>
        <td class="px-4 py-2 text-slate-600"><?= h($a['service_name'] ?? '') ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($a['staff_name'] ?? '—') ?></td>
        <td class="px-4 py-2 text-center">
          <form method="POST" action="<?= $base ?>/appointments/status/<?= (int)$a['id'] ?>" class="inline">
            <?= CsrfHelper::getField() ?>
            <select name="status" onchange="this.form.submit()" class="text-xs px-2 py-0.5 rounded-full border-0 <?= $statusColors[$a['status']] ?? '' ?>">
              <?php foreach (['Scheduled','In-Progress','Completed','Cancelled','No-Show'] as $st): ?>
                <option value="<?= $st ?>" <?= $a['status']===$st?'selected':'' ?>><?= $st ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td class="px-4 py-2 text-right">
          <form method="POST" action="<?= $base ?>/appointments/delete/<?= (int)$a['id'] ?>" onsubmit="return confirm('Delete?');">
            <?= CsrfHelper::getField() ?><button class="text-red-600 hover:underline text-xs">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($appointments)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No appointments yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
