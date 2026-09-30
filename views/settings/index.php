<?php $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4">Settings</h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded px-3 py-2"><?= h($success) ?></div>
<?php endif; ?>

<div class="grid gap-6 max-w-xl">
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <h2 class="font-semibold text-slate-900 mb-1">Your Account</h2>
    <p class="text-sm text-slate-500 mb-4"><?= h($currentUser['name']) ?> · <?= h($currentUser['email'] ?? $currentUser['username'] ?? '') ?> · <?= h(ucfirst($currentUser['role'])) ?></p>

    <form method="POST" class="space-y-3">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="form" value="password">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Current Password</label>
        <input type="password" name="current_password" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
          <input type="password" name="new_password" required minlength="6" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
          <input type="password" name="confirm_password" required minlength="6" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
      </div>
      <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Change Password</button>
    </form>
  </div>

  <?php if (in_array(Auth::role(), ['owner', 'admin'], true)): ?>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <h2 class="font-semibold text-slate-900 mb-4">Business Details</h2>
    <form method="POST" class="space-y-3">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="form" value="business">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Business Name</label>
        <input type="text" name="name" required value="<?= h($org['name']) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Business Address</label>
        <input type="text" name="address" value="<?= h($org['address'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div class="text-xs text-slate-400">URL: tradeposx.com/<?= h($org['slug']) ?>/ (slug can't be changed here)</div>
      <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    </form>
  </div>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <h2 class="font-semibold text-slate-900 mb-1">Invoice Details</h2>
    <p class="text-sm text-slate-500 mb-4">Shown on every sale receipt and service invoice — fill these in for a proper GST tax invoice.</p>
    <form method="POST" class="space-y-3">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="form" value="tax">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">GSTIN</label>
        <input type="text" name="gstin" value="<?= h($org['gstin'] ?? '') ?>" placeholder="e.g. 19ABCDE1234F1Z5" maxlength="15"
          class="w-full rounded border border-slate-300 px-3 py-2 text-sm font-mono uppercase">
        <p class="text-xs text-slate-400 mt-1">Leave blank if not GST-registered — receipts will show as a plain Bill of Supply instead of a Tax Invoice.</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
        <input type="text" name="phone" value="<?= h($org['phone'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    </form>
  </div>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <h2 class="font-semibold text-slate-900 mb-1">Backup Your Data</h2>
    <p class="text-sm text-slate-500 mb-4">
      Download every record belonging to your business — products, customers, sales, purchases, appointments,
      job cards, invoices, everything — as a single SQL file you can keep safe or restore from later.
      It never includes any other business's data.
    </p>
    <a href="<?= $base ?>/settings/exportData" class="inline-block bg-slate-700 text-white text-sm font-medium px-4 py-2 rounded hover:bg-slate-800">⬇ Download Full Backup</a>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
