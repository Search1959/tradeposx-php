<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$appointment = $appointment ?? null;
$isEdit = !empty($appointment['id']);
?>

<h1 class="text-xl font-semibold text-slate-900 mb-4"><?= $isEdit ? 'Edit Appointment' : 'New Appointment' ?></h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl space-y-4">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
    <select name="customer_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      <option value="">— Walk-in —</option>
      <?php foreach ($customers as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (int)($appointment['customer_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?> · <?= h($c['mobile']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Service</label>
      <select name="service_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">—</option>
        <?php foreach ($services as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= (int)($appointment['service_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= h($s['name']) ?> (₹<?= number_format((float)$s['price'],0) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Staff</label>
      <select name="staff_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">—</option>
        <?php foreach ($staff as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= (int)($appointment['staff_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Date *</label>
      <input type="date" name="appt_date" required value="<?= h($appointment['appt_date'] ?? date('Y-m-d')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Time *</label>
      <input type="time" name="appt_time" required value="<?= h($appointment['appt_time'] ?? '10:00') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
    <textarea name="notes" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"><?= h($appointment['notes'] ?? '') ?></textarea>
  </div>
  <div class="flex gap-2 pt-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    <a href="<?= $base ?>/appointments" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
