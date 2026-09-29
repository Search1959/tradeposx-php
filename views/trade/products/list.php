<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4 gap-3">
  <h1 class="text-xl font-semibold text-slate-900">Products</h1>
  <form method="GET" class="flex-1 max-w-xs">
    <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Search name / SKU / barcode"
           class="w-full rounded border border-slate-300 px-3 py-1.5 text-sm">
  </form>
  <a href="<?= $base ?>/products/bulkImport" class="bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50 whitespace-nowrap">Bulk Import</a>
  <a href="<?= $base ?>/products/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700 whitespace-nowrap">+ New Product</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Category</th>
        <th class="text-right px-4 py-2">Stock</th>
        <th class="text-right px-4 py-2">Selling Price</th>
        <th class="text-right px-4 py-2">MRP</th>
        <th class="text-center px-4 py-2">GST%</th>
        <th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($products as $p): $low = (float)$p['current_stock'] <= (float)$p['min_stock']; ?>
      <tr>
        <td class="px-4 py-2">
          <div class="font-medium text-slate-900"><?= h($p['name']) ?></div>
          <div class="text-xs text-slate-400"><?= h($p['sku'] ?? '') ?><?= $p['barcode'] ? ' · ' . h($p['barcode']) : '' ?></div>
        </td>
        <td class="px-4 py-2 text-slate-600"><?= h($p['category']) ?></td>
        <td class="px-4 py-2 text-right <?= $low ? 'text-red-600 font-semibold' : 'text-slate-700' ?>">
          <?= rtrim(rtrim(number_format((float)$p['current_stock'], 2), '0'), '.') ?> <?= h($p['unit']) ?>
        </td>
        <td class="px-4 py-2 text-right text-slate-700"><?= formatCurrency((float)$p['selling_price']) ?></td>
        <td class="px-4 py-2 text-right text-slate-500"><?= formatCurrency((float)$p['mrp']) ?></td>
        <td class="px-4 py-2 text-center text-slate-500"><?= h((string)$p['gst_percent']) ?>%</td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <a href="<?= $base ?>/products/ledger/<?= (int)$p['id'] ?>" class="text-slate-500 hover:underline text-xs">Ledger</a>
          <a href="<?= $base ?>/products/edit/<?= (int)$p['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Edit</a>
          <form method="POST" action="<?= $base ?>/products/delete/<?= (int)$p['id'] ?>" class="inline" onsubmit="return confirm('Delete this product?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($products)): ?>
      <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No products yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
