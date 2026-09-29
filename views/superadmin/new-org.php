<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Organization · <?= h(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
  <div class="max-w-xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold">New Organization</h1>
      <div class="flex items-center gap-3">
        <a href="/" class="text-sm text-slate-500 hover:underline">Home</a>
        <a href="/superadmin" class="text-sm text-slate-500">&larr; Back</a>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-4">
      <?= CsrfHelper::getField() ?>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Business Name *</label>
        <input type="text" name="name" required value="<?= h($form['name'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">URL Slug (optional — auto-generated from name if blank)</label>
        <input type="text" name="slug" value="<?= h($form['slug'] ?? '') ?>" placeholder="e.g. sharma-kirana" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <div class="text-xs text-slate-400 mt-1">tradeposx.com/<span class="font-mono">&lt;slug&gt;</span>/dashboard</div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Business Type</label>
          <select name="business_type" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="both">Trade + Service</option>
            <option value="trade">Trade only</option>
            <option value="service">Service only</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Trading Sector</label>
          <select name="trading_sector" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Select —</option>
            <?php $selectedSector = $form['trading_sector'] ?? ''; ?>
            <optgroup label="Trade">
              <?php foreach (SectorHelper::tradeSectors() as $code => $label): ?>
                <option value="<?= h($code) ?>" <?= $selectedSector === $code ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Service">
              <?php foreach (SectorHelper::serviceSectors() as $code => $label): ?>
                <option value="<?= h($code) ?>" <?= $selectedSector === $code ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>
      </div>

      <div class="border-t border-slate-100 pt-4">
        <div class="text-sm font-medium text-slate-700 mb-3">First Owner Account</div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Owner Name *</label>
            <input type="text" name="owner_name" required value="<?= h($form['owner_name'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Owner Email *</label>
            <input type="email" name="owner_email" required value="<?= h($form['owner_email'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Owner Password * (min 6 characters)</label>
          <input type="password" name="owner_password" required minlength="6" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
      </div>

      <div class="flex gap-2 pt-2">
        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Create Organization</button>
        <a href="/superadmin" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
