<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Bulk Import Products</h1>
  <a href="<?= $base ?>/products" class="text-sm text-slate-500">&larr; Back to Products</a>
</div>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<?php if ($result): ?>
  <div class="mb-4 bg-green-50 border border-green-200 rounded px-4 py-3">
    <div class="text-sm text-green-800 font-medium">
      Import complete — <?= (int)$result['created'] ?> new product<?= $result['created'] === 1 ? '' : 's' ?> created,
      <?= (int)$result['updated'] ?> existing product<?= $result['updated'] === 1 ? '' : 's' ?> updated.
    </div>
    <?php if (!empty($result['skipped'])): ?>
      <div class="text-xs text-amber-700 mt-2">
        <?= count($result['skipped']) ?> row(s) skipped:
        <ul class="list-disc list-inside">
          <?php foreach ($result['skipped'] as $s): ?><li><?= h($s) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <?php if (!empty($result['note'])): ?>
      <div class="text-xs text-amber-700 mt-2 bg-amber-50 border border-amber-200 rounded px-2 py-1.5"><?= h($result['note']) ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl">
  <p class="text-sm text-slate-600 mb-4">
    Upload a CSV to add new products or update existing ones in bulk. A row matches an
    <strong>existing</strong> product by barcode (checked first) or SKU — matched rows are
    <strong>updated</strong> (blank cells leave the existing value alone); unmatched rows
    <strong>create</strong> a new product, with <code>current_stock</code> becoming its opening stock.
  </p>
  <a href="<?= $base ?>/products/template" class="inline-block text-sm text-indigo-600 hover:underline mb-4">⬇ Download CSV template</a>

  <form method="POST" enctype="multipart/form-data" class="space-y-3">
    <?= CsrfHelper::getField() ?>
    <input type="file" name="csv_file" accept=".csv" required class="w-full text-sm border border-slate-300 rounded px-3 py-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Upload &amp; Import</button>
  </form>
</div>

<?php $demoDir = ROOT_PATH . '/demodata/tradeerp/'; ?>
<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl mt-4" x-data="{ sector: '<?= h(is_file($demoDir . ($org['trading_sector'] ?? '') . '.csv') ? $org['trading_sector'] : '') ?>' }">
  <h2 class="text-sm font-semibold text-slate-900 mb-1">Start with a ready-made list</h2>
  <p class="text-sm text-slate-600 mb-3">Download a sample product list for your type of trade, change the prices and stock in Excel, then upload it above. Prices, stock and GST in the samples are placeholders — set your own.</p>
  <div class="flex gap-2">
    <select x-model="sector" class="flex-1 rounded border border-slate-300 px-3 py-2 text-sm">
      <option value="">— Choose your trade —</option>
      <?php foreach (SectorHelper::tradeSectors() as $code => $label): if (!is_file($demoDir . $code . '.csv')) continue; ?><option value="<?= h($code) ?>"><?= h($label) ?></option><?php endforeach; ?>
    </select>
    <a :href="sector ? '<?= $base ?>/products/demo/' + sector : '#'" :class="sector ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-400 pointer-events-none'"
       class="text-sm font-medium px-4 py-2 rounded whitespace-nowrap">⬇ Download</a>
  </div>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
