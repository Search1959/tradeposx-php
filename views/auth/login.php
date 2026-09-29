<?php $org = Tenant::current(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · <?= h($org['name'] ?? APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm bg-white rounded-xl shadow p-8">
    <h1 class="text-xl font-semibold text-slate-900 mb-1"><?= h($org['name'] ?? APP_NAME) ?></h1>
    <p class="text-sm text-slate-500 mb-6">Sign in to continue</p>
    <?php if (!empty($error)): ?>
      <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
      <?= CsrfHelper::getField() ?>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= $org ? 'Username or Email' : 'Email' ?></label>
        <input type="text" name="email" required autocapitalize="off" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input type="password" name="password" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>
      <button type="submit" class="w-full bg-indigo-600 text-white rounded py-2 text-sm font-medium hover:bg-indigo-700">Sign in</button>
    </form>
    <p class="text-center text-xs text-slate-400 mt-4"><a href="/" class="underline hover:text-slate-600">&larr; Back to home</a></p>
  </div>
</body>
</html>
