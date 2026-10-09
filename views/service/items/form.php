<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; $i = $item ?? []; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4"><?= h($pageTitle) ?></h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl space-y-4">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
    <input type="text" name="name" required value="<?= h($i['name'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
      <input type="text" name="category" value="<?= h($i['category'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Sector</label>
      <input type="text" name="sector" value="<?= h($i['sector'] ?? '') ?>" placeholder="e.g. Salon, Auto Repair, Electronics" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div class="grid grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Price</label>
      <input type="number" step="0.01" name="price" value="<?= h((string)($i['price'] ?? '0')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Duration (min)</label>
      <input type="number" name="duration_minutes" value="<?= h((string)($i['duration_minutes'] ?? '')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">GST %</label>
      <!-- No silent 0% default for a new item — 0% used to be pre-selected
           automatically, so an owner quickly adding a menu item could save it
           GST-exempt without ever touching this field, and then not understand
           why no GST showed up on an invoice later. Editing an existing item
           still correctly pre-selects whatever rate (even 0%) it already has. -->
      <select name="gst_percent" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <?php if (!isset($i['gst_percent'])): ?>
          <option value="" disabled selected>— Select GST % —</option>
        <?php endif; ?>
        <?php foreach ([0,5,12,18,28] as $g): ?>
          <option value="<?= $g ?>" <?= isset($i['gst_percent']) && (float)$i['gst_percent'] == $g ? 'selected' : '' ?>><?= $g ?>%</option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Service Warranty (days)</label>
    <input type="number" name="warranty_days" value="<?= h((string)($i['warranty_days'] ?? '0')) ?>" placeholder="0 = no warranty" class="w-full rounded border border-slate-300 px-3 py-2 text-sm max-w-[10rem]">
    <p class="text-xs text-slate-400 mt-1">A completed job of this type is automatically marked "under warranty" for this many days — a repeat visit for the same issue within that window is easy to spot on the job card.</p>
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
    <textarea name="description" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"><?= h($i['description'] ?? '') ?></textarea>
  </div>
  <!-- Goods (chargers, spares…) sold on a service bill are tracked as stock items: the quantity drops with every bill
       and comes back if an invoice is cancelled. Plain services leave this off. -->
  <div x-data="{ stock: <?= ($i['item_type'] ?? 'SERVICE') === 'STOCK' ? 'true' : 'false' ?> }" class="rounded border border-slate-200 bg-slate-50 p-3">
    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
      <input type="checkbox" name="is_stock" value="1" x-model="stock"> This is a stock item (goods I sell and count)
    </label>
    <div x-show="stock" x-cloak class="mt-2">
      <label class="block text-sm font-medium text-slate-700 mb-1">Quantity in stock</label>
      <input type="number" step="1" name="stock_qty" value="<?= h((string)($i['stock_qty'] ?? '0')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm max-w-[10rem]">
    </div>
  </div>
  <label class="flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="is_popular" value="1" <?= !empty($i['is_popular']) ? 'checked' : '' ?>> Mark as popular
  </label>
  <div class="flex gap-2 pt-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    <a href="<?= $base ?>/service-items" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
