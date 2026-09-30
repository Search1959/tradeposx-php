<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Expenses</h1>
  <div class="text-sm text-slate-500">Total: <span class="font-semibold text-slate-900"><?= formatCurrency($total) ?></span></div>
</div>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div class="flex gap-2 mb-4">
  <a href="?tab=expenses" class="px-4 py-2 rounded-lg text-sm font-medium <?= $tab === 'expenses' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' ?>">Expenses</a>
  <a href="?tab=heads" class="px-4 py-2 rounded-lg text-sm font-medium <?= $tab === 'heads' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' ?>">Expense Heads</a>
</div>

<?php if ($tab === 'heads'): ?>

  <form method="POST" action="<?= $base ?>/expenses/add-head" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4 flex items-end gap-3">
    <?= CsrfHelper::getField() ?>
    <div class="flex-1 max-w-xs">
      <label class="block text-xs font-medium text-slate-700 mb-1">New Expense Head</label>
      <input type="text" name="name" required placeholder="e.g. Vehicle Fuel" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Add Head</button>
  </form>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
        <tr><th class="text-left px-4 py-2">Name</th><th class="px-4 py-2"></th></tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($expenseHeads as $h): ?>
        <tr>
          <td class="px-4 py-2 text-slate-900"><?= h($h['name']) ?></td>
          <td class="px-4 py-2 text-right">
            <form method="POST" action="<?= $base ?>/expenses/delete-head/<?= (int)$h['id'] ?>" class="inline" onsubmit="return confirm('Delete this expense head? Existing expenses already logged under it are unaffected.');">
              <?= CsrfHelper::getField() ?>
              <button class="text-red-600 hover:underline text-xs">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($expenseHeads)): ?>
        <tr><td colspan="2" class="px-4 py-8 text-center text-slate-400">No expense heads yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>

  <form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4 flex items-end gap-3 flex-wrap">
    <?= CsrfHelper::getField() ?>
    <div>
      <label class="block text-xs font-medium text-slate-700 mb-1">Category</label>
      <select name="category" class="rounded border border-slate-300 px-3 py-2 text-sm">
        <?php foreach ($expenseHeads as $h): ?><option value="<?= h($h['name']) ?>"><?= h($h['name']) ?></option><?php endforeach; ?>
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
          <td class="px-4 py-2 text-right whitespace-nowrap">
            <a href="<?= $base ?>/expenses/edit/<?= (int)$e['id'] ?>" class="text-indigo-600 hover:underline text-xs">Edit</a>
            <form method="POST" action="<?= $base ?>/expenses/delete/<?= (int)$e['id'] ?>" class="inline" onsubmit="return confirm('Delete this expense?');">
              <?= CsrfHelper::getField() ?>
              <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($expenses)): ?>
        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No expenses yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?= renderPagination($page, $totalPages, $base . '/expenses') ?>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
