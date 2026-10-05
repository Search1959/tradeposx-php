<?php
require_once __DIR__ . '/../layouts/icons.php';

/** Digits-only WhatsApp number; a bare 10-digit Indian mobile gets the 91 country code. */
function saWaNumber(?string $mobile): string {
    $d = preg_replace('/\D+/', '', (string) $mobile);
    return strlen($d) === 10 ? '91' . $d : $d;
}
function saAgo(string $ts): string {
    $days = (int) floor((time() - strtotime($ts)) / 86400);
    return $days <= 0 ? 'Today' : ($days === 1 ? 'Yesterday' : ($days < 31 ? "$days days ago" : date('j M Y', strtotime($ts))));
}
$typeLabel = ['trade' => 'TradeERP', 'service' => 'ServiceERP', 'both' => 'Trade + Service'];
$statusTone = ['active' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'demo' => 'bg-sky-500/15 text-sky-300 border-sky-500/30', 'suspended' => 'bg-rose-500/15 text-rose-300 border-rose-500/30', 'disabled' => 'bg-rose-500/15 text-rose-300 border-rose-500/30'];
$newOrgs = array_values(array_filter($organizations, fn($o) => strtotime($o['created_at']) >= strtotime('-30 days')));
$tabs = [
    ['firms', 'Registered Businesses', count($organizations)],
    ['users', 'All Users', count($users)],
    ['new', 'New Registrations', count($newOrgs)],
    ['ledger', 'Subscription Ledger', count($ledger)],
];
$tiles = [
    ['Total Businesses', $stats['orgs'], 'building', 'text-indigo-300'],
    ['Total Users', $stats['users'], 'users', 'text-sky-300'],
    ['New · 30 days', $stats['new30'], 'clock', 'text-amber-300'],
    ['Suspended', $stats['suspended'], 'alert', 'text-rose-300'],
];
$ledgerTiles = [
    ['Billable Accounts', (string) count($ledger), 'building', 'text-indigo-300'],
    ['Total Billed', '₹' . number_format($totals['billed']), 'rupee', 'text-sky-300'],
    ['Total Collected', '₹' . number_format($totals['collected']), 'check', 'text-emerald-300'],
    ['Outstanding', '₹' . number_format($totals['outstanding']), 'alert', 'text-rose-300'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>System Admin · <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  [x-cloak] { display: none !important; }
  body { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  @media (max-width: 767px) { input, select { font-size: 16px !important; } }
  .tabscroll { scrollbar-width: none; } .tabscroll::-webkit-scrollbar { display: none; }
</style>
</head>
<body class="bg-[#080e1f] text-slate-200 min-h-screen antialiased">
<div class="max-w-6xl mx-auto px-4 py-6 md:py-8"
     x-data="{ tab: '<?= h($tab) ?>', q: '', st: 'all',
       setTab(t) { this.tab = t; this.q = ''; this.st = 'all'; history.replaceState(null, '', '?tab=' + t); },
       vis(el) { return (this.q === '' || el.dataset.s.includes(this.q.toLowerCase())) && (this.st === 'all' || el.dataset.st === this.st); } }">

  <!-- Header -->
  <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">Platform Registry</h1>
      <p class="mt-1 text-sm text-slate-400">All registered businesses, accounts, new sign-ups and subscription billing</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-xl border border-indigo-400/30 bg-indigo-500/10 text-indigo-200"><?= uiIcon('shield', 'w-4 h-4') ?>System Admin Only</span>
      <a href="/" class="text-sm text-slate-400 hover:text-white px-2">Home</a>
      <a href="/logout" class="text-sm text-slate-400 hover:text-white px-2">Logout</a>
    </div>
  </div>

  <!-- Actions -->
  <div class="flex flex-wrap gap-2 mb-5">
    <a href="/superadmin/newOrg" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl"><?= uiIcon('plus', 'w-4 h-4') ?>New Organization</a>
    <form method="POST" action="/superadmin/seedDemo"><?= CsrfHelper::getField() ?>
      <button class="text-sm font-semibold px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-slate-200">Seed Demo Data</button>
    </form>
    <a href="/superadmin/backup" class="text-sm font-semibold px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-slate-200">Full Backup</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-5 text-sm text-emerald-200 bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3"><?= h($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <!-- Stat tiles -->
  <div :class="tab === 'ledger' ? 'hidden md:grid' : 'grid'" class="grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <?php foreach ($tiles as [$label, $val, $icon, $tone]): ?>
      <div class="rounded-2xl border border-indigo-400/20 bg-[#112549] p-3 md:p-4">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-400"><span class="<?= $tone ?>"><?= uiIcon($icon, 'w-4 h-4') ?></span><?= h($label) ?></div>
        <div class="mt-1.5 md:mt-2 text-2xl md:text-3xl font-extrabold text-white"><?= (int) $val ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Tab bar -->
  <div class="mb-4">
    <div class="grid grid-cols-2 md:flex gap-1 p-1 rounded-xl border border-indigo-400/20 bg-[#112549]">
      <?php foreach ($tabs as [$key, $label, $count]): ?>
        <button type="button" @click="setTab('<?= $key ?>')"
                :class="tab === '<?= $key ?>' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                class="md:flex-1 flex items-center justify-center gap-1.5 px-2 md:px-4 py-2.5 rounded-lg text-[11px] sm:text-xs font-bold transition">
          <?= h($label) ?>
          <span :class="tab === '<?= $key ?>' ? 'bg-white/25' : 'bg-indigo-400/20 text-indigo-200'" class="px-1.5 py-0.5 rounded-full text-[10px] font-black"><?= (int) $count ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Search + status filter (not shown on the ledger, which has its own totals) -->
  <div x-show="tab !== 'ledger'" class="flex flex-col sm:flex-row gap-3 mb-4">
    <div class="relative flex-1">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><?= uiIcon('search', 'w-4 h-4') ?></span>
      <input type="search" x-model="q" placeholder="Search by name, email, mobile, URL…"
             class="w-full pl-9 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 bg-black/30 border border-white/10 focus:outline-none focus:border-indigo-400">
    </div>
    <div class="flex items-center gap-1.5 flex-wrap">
      <span class="text-slate-500"><?= uiIconExtra('filter', 'w-4 h-4') ?></span>
      <template x-for="o in (tab === 'users' ? ['all','active','disabled'] : ['all','active','suspended','demo'])" :key="o">
        <button type="button" @click="st = o" :class="st === o ? 'bg-indigo-600 text-white' : 'bg-white/5 text-slate-400 border border-white/10'"
                class="px-3 py-1.5 rounded-lg text-xs font-bold capitalize" x-text="o"></button>
      </template>
    </div>
  </div>

  <!-- ── Registered businesses ── -->
  <div x-show="tab === 'firms'" x-cloak class="space-y-2.5">
    <?php foreach ($organizations as $o): ?>
      <div x-show="vis($el)" data-st="<?= h($o['status']) ?>" data-s="<?= h(strtolower($o['name'] . ' ' . $o['slug'] . ' ' . ($o['owner_name'] ?? '') . ' ' . ($o['owner_email'] ?? '') . ' ' . ($o['owner_mobile'] ?? ''))) ?>"
           class="rounded-2xl border border-white/10 bg-[#0f1e38] p-4 flex flex-col md:flex-row md:items-center gap-3">
        <div class="flex items-center gap-3 min-w-0 md:flex-1">
          <div class="w-11 h-11 rounded-xl bg-indigo-500/20 text-indigo-200 font-bold flex items-center justify-center shrink-0"><?= h(strtoupper(mb_substr($o['name'], 0, 1))) ?></div>
          <div class="min-w-0">
            <a href="/<?= h($o['slug']) ?>/dashboard" class="block font-bold text-white truncate hover:underline"><?= h($o['name']) ?></a>
            <div class="text-xs text-slate-400 truncate">/<?= h($o['slug']) ?> · <?= h($typeLabel[$o['business_type']] ?? $o['business_type']) ?><?= $o['trading_sector'] ? ' · ' . h($o['trading_sector']) : '' ?></div>
            <div class="text-xs text-slate-500 truncate"><?= h($o['owner_name'] ?? '—') ?><?= !empty($o['owner_email']) ? ' · ' . h($o['owner_email']) : '' ?></div>
          </div>
        </div>
        <div class="flex items-center gap-4 text-xs text-slate-400 md:w-56">
          <span><span class="font-bold text-slate-200"><?= (int) $o['user_count'] ?></span> user<?= (int) $o['user_count'] === 1 ? '' : 's' ?></span>
          <span>Joined <?= date('j M Y', strtotime($o['created_at'])) ?></span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border capitalize <?= $statusTone[$o['status']] ?? 'bg-slate-500/15 text-slate-300 border-slate-500/30' ?>"><?= h($o['status']) ?></span>
          <a href="/<?= h($o['slug']) ?>/dashboard" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10">Open</a>
          <a href="/superadmin/backupOrg/<?= (int) $o['id'] ?>" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10">Backup</a>
          <form method="POST" action="/superadmin/<?= $o['status'] === 'suspended' ? 'activate' : 'suspend' ?>/<?= (int) $o['id'] ?>" class="inline"
                <?php if ($o['status'] !== 'suspended'): ?>onsubmit="return confirm('Suspend <?= h(addslashes($o['name'])) ?>? Their team will be locked out until reactivated.');"<?php endif; ?>>
            <?= CsrfHelper::getField() ?>
            <button class="text-xs font-semibold px-3 py-1.5 rounded-lg border <?= $o['status'] === 'suspended' ? 'border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/10' : 'border-rose-500/40 text-rose-300 hover:bg-rose-500/10' ?>"><?= $o['status'] === 'suspended' ? 'Activate' : 'Suspend' ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$organizations): ?><div class="rounded-2xl border border-white/10 bg-[#0f1e38] py-14 text-center text-slate-400">No businesses registered yet.</div><?php endif; ?>
  </div>

  <!-- ── All users ── -->
  <div x-show="tab === 'users'" x-cloak class="space-y-2.5">
    <?php foreach ($users as $u): ?>
      <div x-show="vis($el)" data-st="<?= h($u['status']) ?>" data-s="<?= h(strtolower($u['name'] . ' ' . ($u['email'] ?? '') . ' ' . ($u['mobile'] ?? '') . ' ' . ($u['org_name'] ?? '') . ' ' . $u['role'])) ?>"
           class="rounded-2xl border border-white/10 bg-[#0f1e38] p-4 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
        <div class="flex items-center gap-3 min-w-0 md:flex-1">
          <div class="w-10 h-10 rounded-full bg-sky-500/20 text-sky-200 font-bold flex items-center justify-center shrink-0"><?= h(strtoupper(mb_substr($u['name'], 0, 1))) ?></div>
          <div class="min-w-0"><div class="font-semibold text-white truncate"><?= h($u['name']) ?></div><div class="text-xs text-slate-400 truncate"><?= h($u['email'] ?? '—') ?><?= !empty($u['mobile']) ? ' · ' . h($u['mobile']) : '' ?></div></div>
        </div>
        <div class="md:w-48 text-xs text-slate-400 truncate">
          <?php if ($u['org_slug']): ?><a href="/<?= h($u['org_slug']) ?>/dashboard" class="hover:underline text-slate-300"><?= h($u['org_name']) ?></a><?php else: ?>Platform<?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-indigo-500/15 text-indigo-200 border border-indigo-400/30 capitalize"><?= h($u['role']) ?></span>
          <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border capitalize <?= $statusTone[$u['status']] ?? '' ?>"><?= h($u['status']) ?></span>
          <span class="text-[11px] text-slate-500 hidden sm:inline"><?= date('j M Y', strtotime($u['created_at'])) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$users): ?><div class="rounded-2xl border border-white/10 bg-[#0f1e38] py-14 text-center text-slate-400">No users yet.</div><?php endif; ?>
  </div>

  <!-- ── New registrations (last 30 days) ── -->
  <div x-show="tab === 'new'" x-cloak class="space-y-2.5">
    <p class="text-xs text-slate-500 mb-1">Businesses that signed up in the last 30 days. Say hello on WhatsApp to help them get started.</p>
    <?php foreach ($newOrgs as $o): $wa = saWaNumber($o['owner_mobile'] ?? ''); ?>
      <div x-show="vis($el)" data-st="<?= h($o['status']) ?>" data-s="<?= h(strtolower($o['name'] . ' ' . $o['slug'] . ' ' . ($o['owner_name'] ?? '') . ' ' . ($o['owner_email'] ?? '') . ' ' . ($o['owner_mobile'] ?? ''))) ?>"
           class="rounded-2xl border border-white/10 bg-[#0f1e38] p-4 flex flex-col md:flex-row md:items-center gap-3">
        <div class="min-w-0 md:flex-1">
          <a href="/<?= h($o['slug']) ?>/dashboard" class="font-bold text-white hover:underline"><?= h($o['name']) ?></a>
          <div class="text-xs text-slate-400"><?= h($typeLabel[$o['business_type']] ?? '') ?><?= $o['trading_sector'] ? ' · ' . h($o['trading_sector']) : '' ?> · <?= h(saAgo($o['created_at'])) ?></div>
          <div class="text-xs text-slate-500"><?= h($o['owner_name'] ?? '—') ?><?= !empty($o['owner_email']) ? ' · ' . h($o['owner_email']) : '' ?><?= !empty($o['owner_mobile']) ? ' · ' . h($o['owner_mobile']) : '' ?></div>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border capitalize <?= $statusTone[$o['status']] ?? '' ?>"><?= h($o['status']) ?></span>
          <?php if ($wa !== ''): ?>
            <a target="_blank" rel="noopener" href="https://wa.me/<?= h($wa) ?>?text=<?= rawurlencode('Hi ' . ($o['owner_name'] ?? 'there') . ', welcome to ' . APP_NAME . '! Let us know if you need help setting up ' . $o['name'] . '.') ?>"
               class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/10"><?= uiIcon('chat', 'w-4 h-4') ?>WhatsApp</a>
          <?php endif; ?>
          <a href="/<?= h($o['slug']) ?>/dashboard" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10">Open</a>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$newOrgs): ?><div class="rounded-2xl border border-white/10 bg-[#0f1e38] py-14 text-center text-slate-400">No new sign-ups in the last 30 days.</div><?php endif; ?>
  </div>

  <!-- ── Subscription ledger ── -->
  <div x-show="tab === 'ledger'" x-cloak class="space-y-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <?php foreach ($ledgerTiles as [$label, $val, $icon, $tone]): ?>
        <div class="rounded-2xl border border-indigo-400/20 bg-[#112549] p-4">
          <div class="flex items-center gap-2 text-xs font-semibold text-slate-400"><span class="<?= $tone ?>"><?= uiIcon($icon, 'w-4 h-4') ?></span><?= h($label) ?></div>
          <div class="mt-2 text-2xl font-extrabold <?= $tone ?>"><?= h($val) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="rounded-2xl border border-indigo-400/20 overflow-hidden">
      <div class="hidden md:grid grid-cols-12 gap-2 px-4 py-3 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 bg-[#0a1628] border-b border-indigo-400/20">
        <div class="col-span-4">Account</div><div class="col-span-2 text-right">Monthly Fee</div><div class="col-span-1 text-center">Months</div>
        <div class="col-span-1 text-right">Total Due</div><div class="col-span-1 text-right">Paid</div><div class="col-span-1 text-right">Balance</div><div class="col-span-2 text-center">Actions</div>
      </div>
      <?php foreach ($ledger as $i => $r): $wa = saWaNumber($r['owner_mobile'] ?? ''); ?>
        <div x-data="{ editFee: false, pay: false }" class="grid grid-cols-2 md:grid-cols-12 gap-x-3 gap-y-2 px-4 py-3.5 items-center text-xs border-b border-indigo-400/10 last:border-b-0 <?= $i % 2 ? 'bg-[#0a1628]' : 'bg-[#0f1e38]' ?>">
          <div class="col-span-2 md:col-span-4 min-w-0">
            <a href="/<?= h($r['slug']) ?>/dashboard" class="block font-bold text-white truncate hover:underline"><?= h($r['name']) ?></a>
            <div class="text-[11px] text-slate-400 truncate"><?= h($r['owner_name'] ?? '—') ?> · /<?= h($r['slug']) ?></div>
          </div>

          <div class="md:col-span-2 md:text-right">
            <span class="md:hidden text-[10px] uppercase tracking-wider text-slate-500 block">Monthly fee</span>
            <button type="button" x-show="!editFee" @click="editFee = true; $nextTick(() => $refs.fee.focus())" class="inline-flex items-center gap-1 font-bold text-white hover:text-indigo-300">
              <?= $r['fee'] > 0 ? '₹' . number_format($r['fee']) : '<span class="text-amber-300">Set fee</span>' ?><?= uiIconExtra('pencil', 'w-3 h-3 text-slate-500') ?>
            </button>
            <form x-show="editFee" x-cloak method="POST" action="/superadmin/setFee/<?= (int) $r['id'] ?>" class="inline-flex items-center gap-1">
              <?= CsrfHelper::getField() ?>
              <span class="text-slate-400">₹</span>
              <input x-ref="fee" type="number" name="fee" min="0" step="1" value="<?= (float) $r['fee'] ?>" class="w-20 px-2 py-1 rounded-lg bg-black/40 border border-indigo-400 text-white text-right">
              <button class="px-2 py-1 rounded bg-indigo-600 text-white font-bold">✓</button>
              <button type="button" @click="editFee = false" class="text-slate-400">✕</button>
            </form>
          </div>

          <div class="md:col-span-1 md:text-center"><span class="md:hidden text-[10px] uppercase tracking-wider text-slate-500 block">Months</span><span class="font-bold text-slate-300"><?= (int) $r['months'] ?></span></div>
          <div class="md:col-span-1 md:text-right"><span class="md:hidden text-[10px] uppercase tracking-wider text-slate-500 block">Total due</span><span class="font-bold text-slate-200">₹<?= number_format($r['due']) ?></span></div>
          <div class="md:col-span-1 md:text-right"><span class="md:hidden text-[10px] uppercase tracking-wider text-slate-500 block">Paid</span><span class="font-bold text-emerald-400">₹<?= number_format($r['paid']) ?></span></div>
          <div class="md:col-span-1 md:text-right"><span class="md:hidden text-[10px] uppercase tracking-wider text-slate-500 block">Balance</span><span class="font-extrabold <?= $r['balance'] > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">₹<?= number_format($r['balance']) ?></span></div>

          <div class="col-span-2 md:col-span-2 flex items-center md:justify-center gap-2">
            <button type="button" x-show="!pay" @click="pay = true; $nextTick(() => $refs.amt.focus())" title="Record payment"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-emerald-500/40 bg-emerald-500/10 text-emerald-300 font-semibold"><?= uiIcon('receipt', 'w-4 h-4') ?><span class="md:hidden">Record payment</span></button>
            <?php if ($wa !== '' && $r['balance'] > 0): ?>
              <a target="_blank" rel="noopener" x-show="!pay" title="WhatsApp reminder"
                 href="https://wa.me/<?= h($wa) ?>?text=<?= rawurlencode('Dear ' . ($r['owner_name'] ?? 'team') . ', your ' . APP_NAME . ' subscription balance of ₹' . number_format($r['balance']) . ' for ' . $r['name'] . ' is due. Kindly arrange payment at your earliest convenience. Thank you.') ?>"
                 class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-green-500/30 bg-green-500/10 text-green-300 font-semibold"><?= uiIcon('chat', 'w-4 h-4') ?><span class="md:hidden">Remind</span></a>
            <?php endif; ?>
            <form x-show="pay" x-cloak method="POST" action="/superadmin/recordPayment/<?= (int) $r['id'] ?>" class="flex items-center gap-1 flex-wrap">
              <?= CsrfHelper::getField() ?>
              <span class="text-slate-400">₹</span>
              <input x-ref="amt" type="number" name="amount" min="1" step="1" value="<?= $r['balance'] > 0 ? (float) $r['balance'] : (float) $r['fee'] ?>" class="w-20 px-2 py-1 rounded-lg bg-black/40 border border-emerald-500/50 text-white text-right">
              <input type="text" name="note" maxlength="150" placeholder="Note (UPI ref…)" class="w-28 px-2 py-1 rounded-lg bg-black/40 border border-white/10 text-white">
              <button class="px-2 py-1 rounded bg-emerald-600 text-white font-bold">✓</button>
              <button type="button" @click="pay = false" class="text-slate-400">✕</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$ledger): ?><div class="py-14 text-center text-slate-400 bg-[#0f1e38]">No billable accounts — active businesses appear here (demo and suspended are excluded).</div><?php endif; ?>
    </div>
    <p class="text-center text-[11px] text-slate-500">Months are counted from each business's sign-up month · demo and suspended accounts are excluded · click a fee to edit it · each recorded payment adds to Paid</p>
  </div>

</div>
</body>
</html>
