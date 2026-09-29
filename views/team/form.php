<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php';
$m = $member ?? [];
$isEdit = !empty($m['id']);
$canGrantAdmin = Auth::role() === 'owner';
?>

<h1 class="text-xl font-semibold text-slate-900 mb-4"><?= h($pageTitle) ?></h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-md space-y-4">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
    <input type="text" name="name" required value="<?= h($m['name'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
    <input type="email" name="email" required value="<?= h($m['email'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <?php if (!$isEdit): ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Password * (min 6 characters)</label>
    <input type="password" name="password" required minlength="6" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <?php endif; ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
    <select name="role" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      <option value="staff" <?= ($m['role'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
      <option value="admin" <?= ($m['role'] ?? '') === 'admin' ? 'selected' : '' ?> <?= $canGrantAdmin ? '' : 'disabled' ?>>Admin</option>
    </select>
    <?php if (!$canGrantAdmin): ?>
      <p class="text-xs text-slate-400 mt-1">Only the business owner can grant Admin access.</p>
    <?php endif; ?>
  </div>
  <div class="flex gap-2 pt-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    <a href="<?= $base ?>/team" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
  </div>
</form>

<?php if ($isEdit): ?>
<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-md mt-4">
  <h2 class="font-semibold text-slate-900 mb-1">Reset Password</h2>
  <p class="text-sm text-slate-500 mb-3">Sets a new password for this account immediately — useful if they've forgotten it.</p>
  <form method="POST" action="<?= $base ?>/team/resetPassword/<?= (int)$m['id'] ?>" class="flex items-end gap-3">
    <?= CsrfHelper::getField() ?>
    <div class="flex-1">
      <label class="block text-xs font-medium text-slate-700 mb-1">New Password (min 6 characters)</label>
      <input type="password" name="new_password" required minlength="6" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-slate-700 text-white text-sm font-medium px-4 py-2 rounded hover:bg-slate-800">Reset</button>
  </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
