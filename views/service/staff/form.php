<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; $m = $member ?? []; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4"><?= h($pageTitle) ?></h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl space-y-4">
  <?= CsrfHelper::getField() ?>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
      <input type="text" name="name" required value="<?= h($m['name'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
      <input type="text" name="role" value="<?= h($m['role'] ?? '') ?>" placeholder="e.g. Technician, Stylist" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Mobile</label>
      <input type="text" name="mobile" value="<?= h($m['mobile'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Specialty</label>
      <input type="text" name="specialty" value="<?= h($m['specialty'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Commission %</label>
      <input type="number" step="0.01" name="commission_percent" value="<?= h((string)($m['commission_percent'] ?? '0')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
      <select name="status" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <?php foreach (['Active','On-Leave','Busy'] as $st): ?>
          <option value="<?= $st ?>" <?= ($m['status'] ?? 'Active') === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="flex gap-2 pt-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    <a href="<?= $base ?>/service-staff" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
