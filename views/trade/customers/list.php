<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4 gap-3">
  <h1 class="text-xl font-semibold text-slate-900">Customers</h1>
  <form method="GET" class="flex-1 max-w-xs">
    <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Search name / mobile"
           class="w-full rounded border border-slate-300 px-3 py-1.5 text-sm">
  </form>
  <a href="<?= $base ?>/customers/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700 whitespace-nowrap">+ New Customer</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
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
            <a href="<?= h(whatsappLink($c['mobile'], udhaarReminderMessage($org['name'], $c['name'], $bal))) ?>" target="_blank" rel="noopener"
               class="text-green-600 hover:underline text-xs">Remind</a>
          <?php endif; ?>
          <a href="<?= $base ?>/customers/statement/<?= (int)$c['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Udhaar</a>
          <a href="<?= $base ?>/customers/edit/<?= (int)$c['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Edit</a>
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
