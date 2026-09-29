<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$sampleSectors = SectorHelper::serviceSectors();
$orgSector = $org['trading_sector'] ?? '';
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Bulk Import Service Items</h1>
  <a href="<?= $base ?>/service-items" class="text-sm text-slate-500">&larr; Back to Service Items</a>
</div>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<?php if ($result): ?>
  <div class="mb-4 bg-green-50 border border-green-200 rounded px-4 py-3">
    <div class="text-sm text-green-800 font-medium">
      Import complete — <?= (int)$result['created'] ?> new item<?= $result['created'] === 1 ? '' : 's' ?> created,
      <?= (int)$result['updated'] ?> existing item<?= $result['updated'] === 1 ? '' : 's' ?> updated.
    </div>
    <?php if (!empty($result['skipped'])): ?>
      <div class="text-xs text-amber-700 mt-2">
        <?= count($result['skipped']) ?> row(s) skipped:
        <ul class="list-disc list-inside">
          <?php foreach ($result['skipped'] as $s): ?><li><?= h($s) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl mb-4">
  <p class="text-sm text-slate-600 mb-4">
    Upload a CSV to add new service items or update existing ones in bulk. A row matches an
    <strong>existing</strong> item by name (case-insensitive) — matched rows are
    <strong>updated</strong> (blank cells leave the existing value alone); unmatched rows
    <strong>create</strong> a new item.
  </p>
  <a href="<?= $base ?>/service-items/template" class="inline-block text-sm text-indigo-600 hover:underline mb-4">⬇ Download CSV template</a>

  <form method="POST" enctype="multipart/form-data" class="space-y-3">
    <?= CsrfHelper::getField() ?>
    <input type="file" name="csv_file" accept=".csv" required class="w-full text-sm border border-slate-300 rounded px-3 py-2">
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Upload &amp; Import</button>
  </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-3xl">
  <h2 class="font-semibold text-slate-900 mb-1">Sample Service Items by Sector</h2>
  <p class="text-sm text-slate-500 mb-4">
    Starting fresh? Download a ready-made list for your kind of business — edit prices/duration to match yours,
    then upload it above. Covers all <?= count($sampleSectors) ?> service sectors.
  </p>
  <?php if ($orgSector !== '' && isset($sampleSectors[$orgSector])): ?>
    <a href="<?= $base ?>/service-items/demo/<?= strtolower($orgSector) ?>"
       class="flex items-center justify-between text-sm border-2 border-indigo-400 bg-indigo-50 rounded-lg px-4 py-3 text-indigo-700 font-medium hover:bg-indigo-100 mb-4">
      <span>⭐ <?= h($sampleSectors[$orgSector]) ?> <span class="font-normal text-indigo-500">(your sector)</span></span>
      <span>⬇ Download</span>
    </a>
  <?php endif; ?>
  <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
    <?php foreach ($sampleSectors as $code => $label): if ($code === $orgSector) continue; ?>
      <a href="<?= $base ?>/service-items/demo/<?= strtolower($code) ?>"
         class="text-sm text-center border border-slate-200 rounded px-3 py-2 text-slate-700 hover:border-indigo-400 hover:bg-indigo-50">
        ⬇ <?= h($label) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
