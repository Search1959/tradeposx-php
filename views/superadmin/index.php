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
      <div class="flex items-center gap-3">
        <form method="POST" action="/superadmin/seedDemo">
          <?= CsrfHelper::getField() ?>
          <button class="bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">🎬 Seed Demo Data</button>
        </form>
        <a href="/superadmin/newOrg" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Organization</a>
        <a href="/superadmin/backup" class="bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">⬇ Full Backup</a>
        <a href="/logout" class="text-sm text-slate-500">Logout</a>
      </div>
    </div>
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="mb-4 text-sm text-green-800 bg-green-50 border border-green-200 rounded px-4 py-3"><?= h($_SESSION['flash_success']) ?></div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 divide-y">
      <?php foreach ($organizations as $org): ?>
        <div class="flex items-center justify-between px-4 py-3 hover:bg-slate-50">
          <a href="/<?= h($org['slug']) ?>/dashboard" class="flex-1 min-w-0">
            <div class="font-medium text-slate-900"><?= h($org['name']) ?></div>
            <div class="text-xs text-slate-500"><?= h($org['slug']) ?> · <?= h($org['business_type']) ?></div>
          </a>
          <div class="flex items-center gap-3">
            <span class="text-xs px-2 py-1 rounded-full <?= $org['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' ?>"><?= h($org['status']) ?></span>
            <a href="/superadmin/backupOrg/<?= (int)$org['id'] ?>" class="text-xs text-slate-500 hover:underline">Backup</a>
            <form method="POST" action="/superadmin/<?= $org['status'] === 'active' ? 'suspend' : 'activate' ?>/<?= (int)$org['id'] ?>">
              <?= CsrfHelper::getField() ?>
              <button class="text-xs text-slate-500 hover:underline"><?= $org['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($organizations)): ?>
        <div class="px-4 py-8 text-center text-sm text-slate-400">No organizations yet.</div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
