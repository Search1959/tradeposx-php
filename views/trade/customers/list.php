<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
  <h1 class="text-xl font-semibold text-slate-900">Customers</h1>
  <form method="GET" class="md:flex-1 md:max-w-xs">
    <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Search name / mobile"
           class="w-full rounded border border-slate-300 px-3 py-1.5 text-sm">
  </form>
  <a href="<?= $base ?>/customers/new" class="text-center bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700 whitespace-nowrap">+ New Customer</a>
</div>

<!-- Mobile: light card stack -->
<div class="md:hidden space-y-2">
  <?php foreach ($customers as $c): $bal = (float)$c['current_balance']; ?>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="font-medium text-slate-900 truncate"><?= h($c['name']) ?></div>
          <div class="text-xs text-slate-500"><?= h($c['mobile']) ?></div>
          <?php if (!empty($c['area'])): ?><div class="text-xs text-slate-400"><?= h($c['area']) ?></div><?php endif; ?>
        </div>
        <div class="text-right shrink-0">
          <div class="text-xs text-slate-400">Udhaar</div>
          <div class="font-semibold <?= $bal > 0 ? 'text-amber-600' : 'text-slate-300' ?>"><?= formatCurrency($bal) ?></div>
        </div>
      </div>
      <div class="flex items-center justify-between mt-2.5 pt-2.5 border-t border-slate-100 text-xs">
        <?php if ($bal > 0 && !empty($c['mobile'])): ?>
          <?php $msg = udhaarReminderMessage($org['name'], $c['name'], $bal, $recentCredits[$c['id']] ?? [], $org['phone'] ?? null); ?>
          <a href="<?= h(whatsappLink($c['mobile'], $msg)) ?>" target="_blank" rel="noopener"
             class="text-green-600 font-medium bg-green-50 px-2.5 py-1 rounded-full">💬 Remind</a>
        <?php else: ?>
          <span></span>
        <?php endif; ?>
        <div class="flex items-center gap-3">
          <a href="<?= $base ?>/customers/statement/<?= (int)$c['id'] ?>" class="text-indigo-600 font-medium">Udhaar</a>
          <a href="<?= $base ?>/customers/edit/<?= (int)$c['id'] ?>" class="text-slate-500">Edit</a>
          <form method="POST" action="<?= $base ?>/customers/delete/<?= (int)$c['id'] ?>" onsubmit="return confirm('Delete this customer? Their Udhaar history will also be deleted.');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600">Delete</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($customers)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400 text-sm">No customers yet.</div>
  <?php endif; ?>
</div>

<!-- Desktop: table -->
<div class="hidden md:block bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Mobile</th>
        <th class="text-left px-4 py-2">Area</th>
        <th class="text-right px-4 py-2">Udhaar Balance</th>
        <th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($customers as $c): $bal = (float)$c['current_balance']; ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($c['name']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($c['mobile']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($c['area'] ?? '') ?></td>
        <td class="px-4 py-2 text-right <?= $bal > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400' ?>"><?= formatCurrency($bal) ?></td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <?php if ($bal > 0 && !empty($c['mobile'])): ?>
            <?php $msg = udhaarReminderMessage($org['name'], $c['name'], $bal, $recentCredits[$c['id']] ?? [], $org['phone'] ?? null); ?>
            <a href="<?= h(whatsappLink($c['mobile'], $msg)) ?>" target="_blank" rel="noopener"
               class="text-green-600 hover:underline text-xs">Remind</a>
          <?php endif; ?>
          <a href="<?= $base ?>/customers/statement/<?= (int)$c['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Udhaar</a>
          <a href="<?= $base ?>/customers/edit/<?= (int)$c['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Edit</a>
          <form method="POST" action="<?= $base ?>/customers/delete/<?= (int)$c['id'] ?>" class="inline" onsubmit="return confirm('Delete this customer? Their Udhaar history will also be deleted.');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($customers)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No customers yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
