<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4 gap-3">
  <h1 class="text-xl font-semibold text-slate-900">Service Customers</h1>
  <form method="GET" class="flex-1 max-w-xs">
    <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Search name / mobile" class="w-full rounded border border-slate-300 px-3 py-1.5 text-sm">
  </form>
  <a href="<?= $base ?>/service-customers/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700 whitespace-nowrap">+ New Customer</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Mobile</th><th class="text-left px-4 py-2">Sector</th><th class="text-right px-4 py-2">Total Spent</th><th class="px-4 py-2"></th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($customers as $c): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($c['name']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($c['mobile']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($c['sector'] ?? '') ?></td>
        <td class="px-4 py-2 text-right"><?= formatCurrency((float)$c['total_spent']) ?></td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <a href="<?= $base ?>/service-customers/edit/<?= (int)$c['id'] ?>" class="text-indigo-600 hover:underline text-xs">Edit</a>
          <form method="POST" action="<?= $base ?>/service-customers/delete/<?= (int)$c['id'] ?>" class="inline" onsubmit="return confirm('Delete this customer?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($customers)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No service customers yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
