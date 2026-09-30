<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Suppliers</h1>
  <a href="<?= $base ?>/suppliers/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Supplier</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Contact</th>
        <th class="text-left px-4 py-2">Mobile</th>
        <th class="text-left px-4 py-2">City</th>
        <th class="text-right px-4 py-2">Outstanding</th>
        <th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($suppliers as $s): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($s['name']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($s['contact_person'] ?? '') ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($s['mobile'] ?? '') ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($s['city'] ?? '') ?></td>
        <td class="px-4 py-2 text-right <?= (float)$s['outstanding_balance'] > 0 ? 'text-amber-600 font-medium' : 'text-slate-500' ?>"><?= formatCurrency((float)$s['outstanding_balance']) ?></td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <a href="<?= $base ?>/suppliers/edit/<?= (int)$s['id'] ?>" class="text-indigo-600 hover:underline text-xs">Edit</a>
          <form method="POST" action="<?= $base ?>/suppliers/delete/<?= (int)$s['id'] ?>" class="inline" onsubmit="return confirm('Delete this supplier?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($suppliers)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No suppliers yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?= renderPagination($page, $totalPages, $base . '/suppliers') ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
