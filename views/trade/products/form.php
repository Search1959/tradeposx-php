<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; $p = $product ?? [];
$categories = ['Rice & Grains','Atta & Flours','Dals & Pulses','Edible Oils & Ghee','Spices & Masalas','Biscuits & Cookies',
  'Snacks & Namkeen','Beverages & Tea/Coffee','Dairy & Bakery','Personal Care & Soap','Cleaning & Household',
  'Chocolates & Sweets','Pooja Essentials','Other'];
$units = ['pcs','kg','g','liter','ml','pkt','box','bottle','pouch','bag','tin','jar'];
?>

<h1 class="text-xl font-semibold text-slate-900 mb-4"><?= h($pageTitle) ?></h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-2xl space-y-4">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
    <input type="text" name="name" required value="<?= h($p['name'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <div class="grid grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">SKU</label>
      <input type="text" name="sku" value="<?= h($p['sku'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Barcode</label>
      <input type="text" name="barcode" value="<?= h($p['barcode'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Brand</label>
      <input type="text" name="brand" value="<?= h($p['brand'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
      <select name="category" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <?php foreach ($categories as $c): ?>
          <option value="<?= h($c) ?>" <?= ($p['category'] ?? '') === $c ? 'selected' : '' ?>><?= h($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Unit</label>
      <select name="unit" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <?php foreach ($units as $u): ?>
          <option value="<?= h($u) ?>" <?= ($p['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= h($u) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="grid grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Purchase Price</label>
      <input type="number" step="0.01" name="purchase_price" value="<?= h((string)($p['purchase_price'] ?? '0')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Selling Price</label>
      <input type="number" step="0.01" name="selling_price" value="<?= h((string)($p['selling_price'] ?? '0')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">MRP</label>
      <input type="number" step="0.01" name="mrp" value="<?= h((string)($p['mrp'] ?? '0')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div class="grid grid-cols-3 gap-4">
    <?php if (empty($p)): // opening stock only settable at creation; edits go through Adjust Stock ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Opening Stock</label>
      <input type="number" step="0.001" name="current_stock" value="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <?php endif; ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Min Stock (low-stock alert)</label>
      <input type="number" step="0.001" name="min_stock" value="<?= h((string)($p['min_stock'] ?? '0')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">GST %</label>
      <select name="gst_percent" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <?php foreach ([0,5,12,18,28] as $g): ?>
          <option value="<?= $g ?>" <?= (float)($p['gst_percent'] ?? 0) == $g ? 'selected' : '' ?>><?= $g ?>%</option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="grid grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Supplier</label>
      <select name="supplier_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">—</option>
        <?php foreach ($suppliers as $sup): ?>
          <option value="<?= (int)$sup['id'] ?>" <?= (int)($p['supplier_id'] ?? 0) === (int)$sup['id'] ? 'selected' : '' ?>><?= h($sup['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">HSN Code</label>
      <input type="text" name="hsn" value="<?= h($p['hsn'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Expiry Date</label>
      <input type="date" name="expiry_date" value="<?= h($p['expiry_date'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
    <textarea name="description" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"><?= h($p['description'] ?? '') ?></textarea>
  </div>
  <div class="flex gap-2 pt-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    <a href="<?= $base ?>/products" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
