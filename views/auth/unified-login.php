<?php
$activeTab = $activeTab ?? ($_GET['tab'] ?? 'new');
$error = $error ?? null;
$form = $form ?? [];
$initialBizType = in_array($form['business_type'] ?? '', ['trade', 'service'], true) ? $form['business_type'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in · <?= h(APP_NAME) ?></title>
<link rel="manifest" href="/assets/manifest.webmanifest">
<link rel="icon" href="/assets/img/icons/icon-192.png">
<meta name="theme-color" content="#0b1020">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {}));
  }
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
  [x-cloak] { display: none !important; }
  body { background: radial-gradient(circle at 50% -10%, #1e2547 0%, #0b1020 55%, #070a16 100%); }
  ::placeholder { color: #64748b; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 text-slate-200">
  <div class="w-full max-w-lg" x-data="{ tab: '<?= h($activeTab) ?>', bizType: '<?= h($initialBizType) ?>' }">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-white tracking-tight"><?= h(APP_NAME) ?></h1>
      <p class="text-sm text-slate-400">POS &amp; Business Management for Every Trade</p>
    </div>

    <!-- Pill tab bar -->
    <div class="flex justify-center mb-5">
      <div class="inline-flex bg-slate-900/70 border border-slate-800 rounded-full p-1 gap-1">
        <button type="button" @click="tab='new'"
          :class="tab==='new' ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-slate-200'"
          class="flex items-center gap-1.5 text-xs font-medium px-4 py-2 rounded-full transition">
          <span>➕</span> Register Business
        </button>
        <button type="button" @click="tab='login'"
          :class="tab==='login' ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-slate-200'"
          class="flex items-center gap-1.5 text-xs font-medium px-4 py-2 rounded-full transition">
          <span>→</span> Sign In
        </button>
        <button type="button" @click="tab='demo'"
          :class="tab==='demo' ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-slate-200'"
          class="flex items-center gap-1.5 text-xs font-medium px-4 py-2 rounded-full transition">
          <span>🎯</span> Demo / Evaluate
        </button>
      </div>
    </div>

    <div class="bg-slate-900/60 backdrop-blur border border-slate-800 rounded-2xl shadow-2xl p-6">
      <?php if (!empty($error)): ?>
        <div class="mb-4 text-sm text-red-300 bg-red-500/10 border border-red-500/30 rounded-lg px-3 py-2"><?= h($error) ?></div>
      <?php endif; ?>

      <!-- ═══ Register Business (New Client) ═══ -->
      <form x-show="tab==='new'" x-cloak method="POST" action="/login/register" class="space-y-4">
        <?= CsrfHelper::getField() ?>
        <input type="hidden" name="business_type" x-bind:value="bizType">

        <div>
          <div class="text-sm font-medium text-slate-300 mb-2">What kind of business is this? *</div>
          <div class="grid grid-cols-2 gap-3">
            <button type="button" @click="bizType='trade'"
              :class="bizType==='trade' ? 'border-indigo-400 bg-indigo-500/10' : 'border-slate-700 hover:border-slate-600'"
              class="border rounded-xl py-4 px-3 text-center transition">
              <div class="text-2xl mb-1">🛒</div>
              <div class="text-sm font-semibold" :class="bizType==='trade' ? 'text-indigo-300' : 'text-slate-300'">TradeERP</div>
              <div class="text-[11px] text-slate-500 mt-0.5">Products, stock &amp; billing</div>
            </button>
            <button type="button" @click="bizType='service'"
              :class="bizType==='service' ? 'border-indigo-400 bg-indigo-500/10' : 'border-slate-700 hover:border-slate-600'"
              class="border rounded-xl py-4 px-3 text-center transition">
              <div class="text-2xl mb-1">🛠️</div>
              <div class="text-sm font-semibold" :class="bizType==='service' ? 'text-indigo-300' : 'text-slate-300'">ServiceERP</div>
              <div class="text-[11px] text-slate-500 mt-0.5">Appointments &amp; job cards</div>
            </button>
          </div>
        </div>

        <div x-show="bizType !== ''" x-cloak class="space-y-3 border-t border-slate-800 pt-4">
          <div>
            <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">🏢 Business Name *</label>
            <input type="text" name="name" value="<?= h($form['name'] ?? '') ?>" placeholder="e.g. Sharma Iron &amp; Steel" required
              class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">👤 Your Name *</label>
              <input type="text" name="owner_name" value="<?= h($form['owner_name'] ?? '') ?>" placeholder="e.g. Rakesh Sharma" required
                class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
              <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">📞 Mobile</label>
              <input type="text" name="mobile" value="<?= h($form['mobile'] ?? '') ?>" placeholder="98765 43210"
                class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">✉️ Email *</label>
              <input type="email" name="owner_email" value="<?= h($form['owner_email'] ?? '') ?>" placeholder="you@business.com" required
                class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
              <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">🏷️ Sector *</label>
              <?php $sel = $form['trading_sector'] ?? ''; ?>
              <select name="trading_sector" x-show="bizType==='trade'" x-cloak :disabled="bizType!=='trade'" required
                class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">— Select —</option>
                <?php foreach (SectorHelper::tradeSectors() as $code => $label): ?>
                  <option value="<?= h($code) ?>" <?= $sel === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="trading_sector" x-show="bizType==='service'" x-cloak :disabled="bizType!=='service'" required
                class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">— Select —</option>
                <?php foreach (SectorHelper::serviceSectors() as $code => $label): ?>
                  <option value="<?= h($code) ?>" <?= $sel === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div x-data="{ show: false }">
            <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">🔒 Create Password * (min 6 characters)</label>
            <div class="relative">
              <input :type="show ? 'text' : 'password'" name="owner_password" minlength="6" required placeholder="••••••••"
                class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-slate-300 text-sm">👁</button>
            </div>
          </div>

          <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-400 text-white rounded-lg py-2.5 text-sm font-semibold transition">Create My Account</button>
          <p class="text-center text-[11px] text-slate-500">Start free. No credit card required.</p>
        </div>
      </form>

      <!-- ═══ Sign In (existing client) ═══ -->
      <form x-show="tab==='login'" x-cloak method="POST" action="/login" class="space-y-4">
        <?= CsrfHelper::getField() ?>
        <input type="hidden" name="login_as" value="tenant">
        <div>
          <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">✉️ Email</label>
          <input type="email" name="email" value="<?= h($activeTab === 'login' ? ($form['email'] ?? '') : '') ?>" required autocapitalize="off"
            class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div x-data="{ show: false }">
          <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">🔒 Password</label>
          <div class="relative">
            <input :type="show ? 'text' : 'password'" name="password" required
              class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-slate-300 text-sm">👁</button>
          </div>
        </div>
        <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-400 text-white rounded-lg py-2.5 text-sm font-semibold transition">Sign In</button>
      </form>

      <!-- ═══ Demo / Evaluate ═══ -->
      <div x-show="tab==='demo'" x-cloak class="text-center space-y-4 py-3">
        <div class="text-3xl">🎯</div>
        <p class="text-sm text-slate-400">Explore a fully populated demo business — sales, repairs, invoices, and reports already filled in. No signup needed.</p>
        <a href="/login/demo" class="inline-block w-full bg-emerald-500 hover:bg-emerald-400 text-white rounded-lg py-2.5 text-sm font-semibold transition">Enter Demo Workspace</a>
      </div>

      <!-- ═══ Admin (superadmin) ═══ -->
      <form x-show="tab==='admin'" x-cloak method="POST" action="/login" class="space-y-4">
        <?= CsrfHelper::getField() ?>
        <input type="hidden" name="login_as" value="admin">
        <div>
          <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">🛡️ Admin Email</label>
          <input type="email" name="email" value="<?= h($activeTab === 'admin' ? ($form['email'] ?? '') : '') ?>" required autocapitalize="off"
            class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1">🔒 Password</label>
          <input type="password" name="password" required
            class="w-full rounded-lg bg-slate-800/60 border border-slate-700 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white rounded-lg py-2.5 text-sm font-semibold transition">Admin Sign In</button>
      </form>
    </div>

    <div class="text-center mt-5 space-y-1">
      <button type="button" @click="tab='admin'" x-show="tab!=='admin'" x-cloak class="text-[11px] text-slate-600 hover:text-slate-400 underline">Platform admin sign in</button>
      <p class="text-xs text-slate-600"><a href="/" class="underline hover:text-slate-400">&larr; Back to home</a></p>
    </div>
  </div>
<?php require __DIR__ . '/../shared/password-toggle.php'; ?>
</body>
</html>
