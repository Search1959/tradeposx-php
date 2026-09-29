<?php
$activeTab = $activeTab ?? ($_GET['tab'] ?? 'login');
$error = $error ?? null;
$form = $form ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in · <?= h(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-md" x-data="{ tab: '<?= h($activeTab) ?>' }">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-slate-900"><?= h(APP_NAME) ?></h1>
      <p class="text-sm text-slate-500">POS &amp; Business Management for Every Trade</p>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <div class="grid grid-cols-4 border-b border-slate-200 text-xs font-medium">
        <button type="button" @click="tab='login'" :class="tab==='login' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent'" class="py-3 border-b-2 hover:text-indigo-600">Login</button>
        <button type="button" @click="tab='new'" :class="tab==='new' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent'" class="py-3 border-b-2 hover:text-indigo-600">New Client</button>
        <button type="button" @click="tab='demo'" :class="tab==='demo' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent'" class="py-3 border-b-2 hover:text-indigo-600">Demo</button>
        <button type="button" @click="tab='admin'" :class="tab==='admin' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent'" class="py-3 border-b-2 hover:text-indigo-600">Admin</button>
      </div>

      <div class="p-6">
        <?php if (!empty($error)): ?>
          <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
        <?php endif; ?>

        <!-- Login: existing client -->
        <form x-show="tab==='login'" x-cloak method="POST" action="/login" class="space-y-4">
          <?= CsrfHelper::getField() ?>
          <input type="hidden" name="login_as" value="tenant">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="<?= h($form['email'] ?? '') ?>" required autocapitalize="off" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
          <button type="submit" class="w-full bg-indigo-600 text-white rounded py-2 text-sm font-medium hover:bg-indigo-700">Sign in</button>
        </form>

        <!-- New Client: self-service signup -->
        <form x-show="tab==='new'" x-cloak method="POST" action="/login/register" class="space-y-3">
          <?= CsrfHelper::getField() ?>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Business Name *</label>
            <input type="text" name="name" value="<?= h($form['name'] ?? '') ?>" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Business Type</label>
              <select name="business_type" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <?php $bt = $form['business_type'] ?? 'both'; ?>
                <option value="both" <?= $bt === 'both' ? 'selected' : '' ?>>Trade + Service</option>
                <option value="trade" <?= $bt === 'trade' ? 'selected' : '' ?>>Trade only</option>
                <option value="service" <?= $bt === 'service' ? 'selected' : '' ?>>Service only</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Trading Sector</label>
              <select name="trading_sector" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Select —</option>
                <?php $sel = $form['trading_sector'] ?? ''; ?>
                <optgroup label="Trade">
                  <?php foreach (SectorHelper::tradeSectors() as $code => $label): ?>
                    <option value="<?= h($code) ?>" <?= $sel === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="Service">
                  <?php foreach (SectorHelper::serviceSectors() as $code => $label): ?>
                    <option value="<?= h($code) ?>" <?= $sel === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Your Name *</label>
            <input type="text" name="owner_name" value="<?= h($form['owner_name'] ?? '') ?>" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Your Email *</label>
            <input type="email" name="owner_email" value="<?= h($form['owner_email'] ?? '') ?>" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password * (min 6 characters)</label>
            <input type="password" name="owner_password" required minlength="6" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
          <button type="submit" class="w-full bg-indigo-600 text-white rounded py-2 text-sm font-medium hover:bg-indigo-700">Create My Account</button>
        </form>

        <!-- Demo: instant showcase login -->
        <div x-show="tab==='demo'" x-cloak class="text-center space-y-4 py-4">
          <p class="text-sm text-slate-600">Explore a fully populated demo business — sales, repairs, invoices, and reports already filled in. No signup needed.</p>
          <a href="/login/demo" class="inline-block w-full bg-emerald-600 text-white rounded py-2 text-sm font-medium hover:bg-emerald-700">Enter Demo Workspace</a>
        </div>

        <!-- Admin: platform superadmin -->
        <form x-show="tab==='admin'" x-cloak method="POST" action="/login" class="space-y-4">
          <?= CsrfHelper::getField() ?>
          <input type="hidden" name="login_as" value="admin">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Admin Email</label>
            <input type="email" name="email" value="<?= h($form['email'] ?? '') ?>" required autocapitalize="off" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          </div>
          <button type="submit" class="w-full bg-slate-800 text-white rounded py-2 text-sm font-medium hover:bg-slate-900">Admin Sign in</button>
        </form>
      </div>
    </div>
    <p class="text-center text-xs text-slate-400 mt-6"><a href="/" class="underline">&larr; Back to home</a></p>
  </div>
</body>
</html>
