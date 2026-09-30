<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
  <h1 class="text-xl font-semibold text-slate-900">Products</h1>
  <form method="GET" class="md:flex-1 md:max-w-xs">
    <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Search name / SKU / barcode"
           class="w-full rounded border border-slate-300 px-3 py-1.5 text-sm">
  </form>
  <div class="flex gap-2">
    <a href="<?= $base ?>/products/bulkImport" class="flex-1 md:flex-none text-center bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50 whitespace-nowrap">Bulk Import</a>
    <a href="<?= $base ?>/products/new" class="flex-1 md:flex-none text-center bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700 whitespace-nowrap">+ New Product</a>
  </div>
</div>

<!-- Mobile: light card stack (a table doesn't work on a phone screen) -->
<div class="md:hidden space-y-2">
  <?php foreach ($products as $p): $low = (float)$p['current_stock'] <= (float)$p['min_stock']; ?>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="font-medium text-slate-900 truncate"><?= h($p['name']) ?></div>
          <div class="text-xs text-slate-400 truncate"><?= h($p['sku'] ?? '') ?><?= $p['barcode'] ? ' · ' . h($p['barcode']) : '' ?></div>
          <div class="text-xs text-indigo-600 mt-0.5"><?= h($p['category']) ?></div>
        </div>
        <div class="text-right shrink-0">
          <div class="font-semibold text-slate-900"><?= formatCurrency((float)$p['selling_price']) ?></div>
          <div class="text-xs mt-0.5 <?= $low ? 'text-red-600 font-semibold bg-red-50 px-1.5 py-0.5 rounded-full inline-block' : 'text-slate-400' ?>">
            <?= rtrim(rtrim(number_format((float)$p['current_stock'], 2), '0'), '.') ?> <?= h($p['unit']) ?>
          </div>
        </div>
      </div>
      <div class="flex items-center justify-between mt-2.5 pt-2.5 border-t border-slate-100">
        <span class="text-xs text-slate-400">MRP <?= formatCurrency((float)$p['mrp']) ?> · GST <?= h((string)$p['gst_percent']) ?>%</span>
        <div class="flex items-center gap-3 text-xs">
          <a href="<?= $base ?>/products/ledger/<?= (int)$p['id'] ?>" class="text-slate-500">Ledger</a>
          <a href="<?= $base ?>/products/edit/<?= (int)$p['id'] ?>" class="text-indigo-600 font-medium">Edit</a>
          <form method="POST" action="<?= $base ?>/products/delete/<?= (int)$p['id'] ?>" onsubmit="return confirm('Delete this product?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600">Delete</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($products)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400 text-sm">No products yet.</div>
  <?php endif; ?>
</div>

<!-- Desktop: table -->
<div class="hidden md:block bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
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

<div class="bg-white rounded-lg shadow-sm border border-slate-200 mt-3">
  <?= renderPagination($page, $totalPages, $base . '/products', ['q' => $_GET['q'] ?? '']) ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
