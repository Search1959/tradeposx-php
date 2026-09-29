<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$categories = ['Rent','Electricity','Delivery/Transport','Staff Salary','Packaging','Maintenance','Tea & Snacks',
  'Wi-Fi & Telecom','Taxes & Licenses','Pest Control & Cleaning','Marketing & Signboard','Other'];
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Expenses</h1>
  <div class="text-sm text-slate-500">Total: <span class="font-semibold text-slate-900"><?= formatCurrency($total) ?></span></div>
</div>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4 flex items-end gap-3 flex-wrap">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-xs font-medium text-slate-700 mb-1">Category</label>
    <select name="category" class="rounded border border-slate-300 px-3 py-2 text-sm">
      <?php foreach ($categories as $c): ?><option value="<?= h($c) ?>"><?= h($c) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="block text-xs font-medium text-slate-700 mb-1">Amount</label>
    <input type="number" step="0.01" min="0.01" name="amount" required class="rounded border border-slate-300 px-3 py-2 text-sm w-36">
  </div>
  <div class="flex-1 min-w-[10rem]">
    <label class="block text-xs font-medium text-slate-700 mb-1">Notes</label>
    <input type="text" name="notes" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Add Expense</button>
</form>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Date</th><th class="text-left px-4 py-2">Category</th><th class="text-left px-4 py-2">Notes</th><th class="text-right px-4 py-2">Amount</th><th class="px-4 py-2"></th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($expenses as $e): ?>
      <tr>
        <td class="px-4 py-2 text-slate-500"><?= formatDateTime($e['created_at']) ?></td>
        <td class="px-4 py-2 text-slate-900"><?= h($e['category']) ?></td>
        <td class="px-4 py-2 text-slate-500"><?= h($e['notes'] ?? '') ?></td>
        <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$e['amount']) ?></td>
        <td class="px-4 py-2 text-right">
          <form method="POST" action="<?= $base ?>/expenses/delete/<?= (int)$e['id'] ?>" onsubmit="return confirm('Delete this expense?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($expenses)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No expenses yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
