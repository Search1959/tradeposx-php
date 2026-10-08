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

<form method="POST" action="<?= $base ?>/products/bulk-delete" id="bulkDeleteForm"
      onsubmit="return confirm('Delete ' + document.querySelectorAll('.row-check:checked').length + ' selected product(s)? This cannot be undone.');">
  <?= CsrfHelper::getField() ?>
  <input type="hidden" name="q" value="<?= h($_GET['q'] ?? '') ?>">

  <!-- Bulk action bar — only meaningful once at least one row is checked.
       "Select zero-price on this page" exists specifically for cleaning up a
       bulk import whose column names didn't match (every row landing at
       ₹0.00/0 stock) without hunting through rows one by one. Scoped to the
       current page only, same as the checkboxes themselves — selecting
       across pages would need re-fetching ids that aren't currently
       rendered, which is more risk than this cleanup task warrants. -->
  <div class="flex flex-wrap items-center gap-3 mb-3 bg-white rounded-lg border border-slate-200 px-4 py-2.5 text-sm">
    <label class="flex items-center gap-2 font-medium text-slate-700">
      <input type="checkbox" id="selectAll" onchange="toggleAll(this)"> Select all on this page
    </label>
    <button type="button" onclick="selectZeroPrice()" class="text-indigo-600 hover:underline">Select all ₹0.00 items on this page</button>
    <span class="text-slate-400">·</span>
    <span id="selectedCount" class="text-slate-500">0 selected</span>
    <button type="submit" id="deleteSelectedBtn" disabled
            class="ml-auto bg-red-600 disabled:bg-slate-300 text-white text-xs font-medium px-3 py-1.5 rounded hover:bg-red-700">Delete Selected</button>
  </div>

  <!-- Mobile: light card stack (a table doesn't work on a phone screen) -->
  <div class="md:hidden space-y-2">
    <?php foreach ($products as $p): $low = (float)$p['current_stock'] <= (float)$p['min_stock']; ?>
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3">
        <div class="flex items-start gap-3">
          <input type="checkbox" name="ids[]" value="<?= (int)$p['id'] ?>" class="row-check mt-1" data-price="<?= (float)$p['selling_price'] ?>" onchange="updateCount()">
          <div class="flex-1 flex items-start justify-between gap-3 min-w-0">
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
        </div>
        <div class="flex items-center justify-between mt-2.5 pt-2.5 border-t border-slate-100">
          <span class="text-xs text-slate-400">MRP <?= formatCurrency((float)$p['mrp']) ?> · GST <?= h((string)$p['gst_percent']) ?>%</span>
          <div class="flex items-center gap-3 text-xs">
            <a href="<?= $base ?>/products/ledger/<?= (int)$p['id'] ?>" class="text-slate-500">Ledger</a>
            <a href="<?= $base ?>/products/edit/<?= (int)$p['id'] ?>" class="text-indigo-600 font-medium">Edit</a>
            <a href="#" onclick="deleteOne(<?= (int)$p['id'] ?>, <?= h(json_encode($p['name'])) ?>); return false;" class="text-red-600 font-medium">Delete</a>
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
    <table class="w-full text-sm sticky-actions">
      <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
        <tr>
          <th class="px-4 py-2 w-8"></th>
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
            <input type="checkbox" name="ids[]" value="<?= (int)$p['id'] ?>" class="row-check" data-price="<?= (float)$p['selling_price'] ?>" onchange="updateCount()">
          </td>
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
            <a href="#" onclick="deleteOne(<?= (int)$p['id'] ?>, <?= h(json_encode($p['name'])) ?>); return false;" class="text-red-600 hover:underline text-xs ml-2">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">No products yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</form>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 mt-3">
  <?= renderPagination($page, $totalPages, $base . '/products', ['q' => $_GET['q'] ?? '']) ?>
</div>

<form method="POST" action="<?= $base ?>/products/bulk-delete" id="oneDeleteForm" class="hidden">
  <?= CsrfHelper::getField() ?><input type="hidden" name="ids[]" id="oneDeleteId" value="">
</form>

<script>
function deleteOne(id, name) {
  if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
  document.getElementById('oneDeleteId').value = id;
  document.getElementById('oneDeleteForm').submit();
}
function updateCount() {
  const checked = document.querySelectorAll('.row-check:checked').length;
  document.getElementById('selectedCount').textContent = checked + ' selected';
  document.getElementById('deleteSelectedBtn').disabled = checked === 0;
  const all = document.querySelectorAll('.row-check').length;
  document.getElementById('selectAll').checked = all > 0 && checked === all;
}
function toggleAll(box) {
  document.querySelectorAll('.row-check').forEach(c => { c.checked = box.checked; });
  updateCount();
}
function selectZeroPrice() {
  document.querySelectorAll('.row-check').forEach(c => {
    if (parseFloat(c.dataset.price) === 0) c.checked = true;
  });
  updateCount();
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
