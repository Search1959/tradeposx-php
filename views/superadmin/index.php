<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin · <?= h(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
  <div class="max-w-4xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold">Organizations</h1>
      <a href="/logout" class="text-sm text-slate-500">Logout</a>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 divide-y">
      <?php foreach ($organizations as $org): ?>
        <a href="/<?= h($org['slug']) ?>/dashboard" class="flex items-center justify-between px-4 py-3 hover:bg-slate-50">
          <div>
            <div class="font-medium text-slate-900"><?= h($org['name']) ?></div>
            <div class="text-xs text-slate-500"><?= h($org['slug']) ?> · <?= h($org['business_type']) ?></div>
          </div>
          <span class="text-xs px-2 py-1 rounded-full <?= $org['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' ?>"><?= h($org['status']) ?></span>
        </a>
      <?php endforeach; ?>
      <?php if (empty($organizations)): ?>
        <div class="px-4 py-8 text-center text-sm text-slate-400">No organizations yet.</div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
