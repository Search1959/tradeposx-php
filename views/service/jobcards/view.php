<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$statusColors = ['Pending'=>'bg-slate-100 text-slate-600','In-Progress'=>'bg-blue-100 text-blue-700','Completed'=>'bg-green-100 text-green-700','Delivered'=>'bg-emerald-100 text-emerald-700','Cancelled'=>'bg-red-100 text-red-700'];
?>

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-xl font-semibold text-slate-900"><?= h($job['job_no']) ?></h1>
    <div class="text-sm text-slate-500"><?= h($job['customer_name'] ?? 'Walk-in') ?> <?= $job['mobile'] ? '· ' . h($job['mobile']) : '' ?></div>
  </div>
  <form method="POST" action="<?= $base ?>/job-cards/status/<?= (int)$job['id'] ?>">
    <?= CsrfHelper::getField() ?>
    <select name="status" onchange="this.form.submit()" class="text-sm px-3 py-1.5 rounded-full border-0 <?= $statusColors[$job['status']] ?? '' ?>">
      <?php foreach (['Pending','In-Progress','Completed','Delivered','Cancelled'] as $st): ?>
        <option value="<?= $st ?>" <?= $job['status']===$st?'selected':'' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-4">
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
    <div><div class="text-xs text-slate-500">Assigned Staff</div><div class="font-medium"><?= h($job['assigned_staff_name'] ?? '—') ?></div></div>
    <div><div class="text-xs text-slate-500">Priority</div><div class="font-medium"><?= h($job['priority']) ?></div></div>
    <div><div class="text-xs text-slate-500">Device/Vehicle</div><div class="font-medium"><?= h($job['device_or_vehicle_info'] ?? '—') ?></div></div>
    <div><div class="text-xs text-slate-500">Est. Completion</div><div class="font-medium"><?= formatDate($job['estimated_completion_date']) ?></div></div>
  </div>
  <div class="text-sm"><div class="text-xs text-slate-500 mb-1">Issue</div><?= nl2br(h($job['issue_description'])) ?></div>
</div>

<?php if (!empty($job['parts'])): ?>
<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto mb-4">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase"><tr><th class="text-left px-4 py-2">Part</th><th class="text-right px-4 py-2">Qty</th><th class="text-right px-4 py-2">Unit Price</th><th class="text-right px-4 py-2">Total</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($job['parts'] as $p): ?>
      <tr><td class="px-4 py-2"><?= h($p['name']) ?></td><td class="px-4 py-2 text-right"><?= rtrim(rtrim(number_format((float)$p['quantity'],2),'0'),'.') ?></td><td class="px-4 py-2 text-right"><?= formatCurrency((float)$p['unit_price']) ?></td><td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$p['total_price']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 gap-4">
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 text-sm space-y-1">
    <div class="flex justify-between text-slate-600"><span>Labour Charges</span><span><?= formatCurrency((float)$job['labour_charges']) ?></span></div>
    <div class="flex justify-between text-slate-600"><span>Material Charges</span><span><?= formatCurrency((float)$job['material_charges']) ?></span></div>
    <div class="flex justify-between font-semibold border-t border-slate-100 pt-1"><span>Total</span><span><?= formatCurrency((float)$job['total_amount']) ?></span></div>
    <div class="flex justify-between text-slate-600"><span>Paid</span><span><?= formatCurrency((float)$job['paid_amount']) ?></span></div>
    <?php if ((float)$job['balance_amount'] > 0): ?>
    <div class="flex justify-between font-semibold text-amber-600"><span>Balance Due</span><span><?= formatCurrency((float)$job['balance_amount']) ?></span></div>
    <?php endif; ?>
  </div>

  <?php if ((float)$job['balance_amount'] > 0): ?>
  <form method="POST" action="<?= $base ?>/job-cards/recordPayment/<?= (int)$job['id'] ?>" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-end gap-3">
    <?= CsrfHelper::getField() ?>
    <div>
      <label class="block text-xs font-medium text-slate-700 mb-1">Record Payment</label>
      <input type="number" step="0.01" min="0.01" max="<?= (float)$job['balance_amount'] ?>" name="amount" required class="rounded border border-slate-300 px-3 py-2 text-sm w-40">
    </div>
    <button type="submit" class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-green-700">Record</button>
  </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
