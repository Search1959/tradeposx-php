<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; $c = $customer ?? []; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4"><?= h($pageTitle) ?></h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl space-y-4">
  <?= CsrfHelper::getField() ?>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
      <input type="text" name="name" required value="<?= h($c['name'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Mobile *</label>
      <input type="text" name="mobile" required value="<?= h($c['mobile'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Area</label>
      <input type="text" name="area" value="<?= h($c['area'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
      <input type="email" name="email" value="<?= h($c['email'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
    <textarea name="address" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"><?= h($c['address'] ?? '') ?></textarea>
  </div>
  <div class="grid grid-cols-2 gap-4">
    <?php if (empty($c)): // opening balance only settable at creation — later changes go through the Udhaar ledger ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Opening Udhaar Balance</label>
      <input type="number" step="0.01" name="opening_balance" value="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <?php endif; ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Credit Limit</label>
      <input type="number" step="0.01" name="credit_limit" value="<?= h((string)($c['credit_limit'] ?? '')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
    <textarea name="notes" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"><?= h($c['notes'] ?? '') ?></textarea>
  </div>
  <div class="flex gap-2 pt-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    <a href="<?= $base ?>/customers" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
