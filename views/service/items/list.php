<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Service Items</h1>
  <a href="<?= $base ?>/service-items/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Service Item</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Category</th><th class="text-right px-4 py-2">Price</th><th class="text-right px-4 py-2">Duration</th><th class="text-center px-4 py-2">GST%</th><th class="px-4 py-2"></th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($items as $i): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($i['name']) ?> <?= $i['is_popular'] ? '<span class="text-xs text-amber-500">★</span>' : '' ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($i['category'] ?? '') ?></td>
        <td class="px-4 py-2 text-right"><?= formatCurrency((float)$i['price']) ?></td>
        <td class="px-4 py-2 text-right text-slate-500"><?= $i['duration_minutes'] ? $i['duration_minutes'].' min' : '—' ?></td>
        <td class="px-4 py-2 text-center text-slate-500"><?= h((string)$i['gst_percent']) ?>%</td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <a href="<?= $base ?>/service-items/edit/<?= (int)$i['id'] ?>" class="text-indigo-600 hover:underline text-xs">Edit</a>
          <form method="POST" action="<?= $base ?>/service-items/delete/<?= (int)$i['id'] ?>" class="inline" onsubmit="return confirm('Delete?');">
            <?= CsrfHelper::getField() ?><button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No service items yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
