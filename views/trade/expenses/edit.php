<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$categories = ['Rent','Electricity','Delivery/Transport','Staff Salary','Packaging','Maintenance','Tea & Snacks',
  'Wi-Fi & Telecom','Taxes & Licenses','Pest Control & Cleaning','Marketing & Signboard','Other'];
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Edit Expense</h1>
  <a href="<?= $base ?>/expenses" class="text-sm text-slate-500">&larr; Back to Expenses</a>
</div>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-md space-y-4">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
    <select name="category" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      <?php foreach ($categories as $c): ?><option value="<?= h($c) ?>" <?= $expense['category'] === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
    <input type="number" step="0.01" min="0.01" name="amount" required value="<?= h((string)$expense['amount']) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
    <input type="text" name="notes" value="<?= h($expense['notes'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <div class="flex gap-2 pt-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save Changes</button>
    <a href="<?= $base ?>/expenses" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
