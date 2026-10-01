<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Service Items</h1>
  <div class="flex gap-2">
    <a href="<?= $base ?>/service-items/bulkImport" class="bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50 whitespace-nowrap">Bulk Import</a>
    <a href="<?= $base ?>/service-items/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Service Item</a>
  </div>
</div>

<form method="POST" action="<?= $base ?>/service-items/bulk-delete" id="bulkDeleteForm">
  <?= CsrfHelper::getField() ?>

  <!-- "Select all ₹0.00 items" / "Select all 0% GST items" exist specifically for
       cleaning up a bulk import whose column names didn't match or whose GST%
       column was left blank (every such row landing at ₹0.00 or 0% GST) without
       hunting through rows one by one — same pattern as the TradeERP Products
       list. Scoped to the current page only. Two submit buttons share this one
       form (ids[] + CSRF) via formaction, routing to different controller actions. -->
  <div class="flex flex-wrap items-center gap-3 mb-3 bg-white rounded-lg border border-slate-200 px-4 py-2.5 text-sm">
    <label class="flex items-center gap-2 font-medium text-slate-700">
      <input type="checkbox" id="selectAll" onchange="toggleAll(this)"> Select all on this page
    </label>
    <button type="button" onclick="selectZeroPrice()" class="text-indigo-600 hover:underline">Select all ₹0.00 items</button>
    <button type="button" onclick="selectZeroGst()" class="text-indigo-600 hover:underline">Select all 0% GST items</button>
    <span class="text-slate-400">·</span>
    <span id="selectedCount" class="text-slate-500">0 selected</span>
    <div class="ml-auto flex items-center gap-1.5">
      <select name="gst_percent" class="text-xs border border-slate-300 rounded px-1.5 py-1.5">
        <option value="0">0%</option><option value="5">5%</option><option value="12">12%</option><option value="18">18%</option><option value="28">28%</option>
      </select>
      <button type="submit" formaction="<?= $base ?>/service-items/bulkSetGst" formnovalidate id="setGstBtn" disabled
              class="bg-indigo-600 disabled:bg-slate-300 text-white text-xs font-medium px-3 py-1.5 rounded hover:bg-indigo-700">Set GST% for Selected</button>
      <button type="submit" id="deleteSelectedBtn" disabled
              onclick="return confirm('Delete ' + document.querySelectorAll('.row-check:checked').length + ' selected service item(s)? This cannot be undone.');"
              class="bg-red-600 disabled:bg-slate-300 text-white text-xs font-medium px-3 py-1.5 rounded hover:bg-red-700">Delete Selected</button>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
        <tr><th class="px-4 py-2 w-8"></th><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Category</th><th class="text-right px-4 py-2">Price</th><th class="text-right px-4 py-2">Duration</th><th class="text-center px-4 py-2">GST%</th><th class="px-4 py-2"></th></tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($items as $i): ?>
        <tr>
          <td class="px-4 py-2">
            <input type="checkbox" name="ids[]" value="<?= (int)$i['id'] ?>" class="row-check" data-price="<?= (float)$i['price'] ?>" data-gst="<?= (float)$i['gst_percent'] ?>" onchange="updateCount()">
          </td>
          <td class="px-4 py-2 font-medium text-slate-900"><?= h($i['name']) ?> <?= $i['is_popular'] ? '<span class="text-xs text-amber-500">★</span>' : '' ?></td>
          <td class="px-4 py-2 text-slate-600"><?= h($i['category'] ?? '') ?></td>
          <td class="px-4 py-2 text-right"><?= formatCurrency((float)$i['price']) ?></td>
          <td class="px-4 py-2 text-right text-slate-500"><?= $i['duration_minutes'] ? $i['duration_minutes'].' min' : '—' ?></td>
          <td class="px-4 py-2 text-center text-slate-500"><?= h((string)$i['gst_percent']) ?>%</td>
          <td class="px-4 py-2 text-right whitespace-nowrap">
            <a href="<?= $base ?>/service-items/edit/<?= (int)$i['id'] ?>" class="text-indigo-600 hover:underline text-xs">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No service items yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?= renderPagination($page, $totalPages, $base . '/service-items') ?>
  </div>
</form>

<script>
function updateCount() {
  const checked = document.querySelectorAll('.row-check:checked').length;
  document.getElementById('selectedCount').textContent = checked + ' selected';
  document.getElementById('deleteSelectedBtn').disabled = checked === 0;
  document.getElementById('setGstBtn').disabled = checked === 0;
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
function selectZeroGst() {
  document.querySelectorAll('.row-check').forEach(c => {
    if (parseFloat(c.dataset.gst) === 0) c.checked = true;
  });
  updateCount();
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
