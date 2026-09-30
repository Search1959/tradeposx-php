<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Brokers</h1>
  <a href="<?= $base ?>/brokers/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Broker</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Mobile</th>
        <th class="text-right px-4 py-2">Default %</th>
        <th class="text-right px-4 py-2">Commission Payable</th>
        <th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($brokers as $b): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($b['name']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($b['mobile'] ?? '') ?></td>
        <td class="px-4 py-2 text-right text-slate-600"><?= h((string)$b['default_commission_percent']) ?>%</td>
        <td class="px-4 py-2 text-right <?= (float)$b['commission_payable'] > 0 ? 'text-amber-600 font-medium' : 'text-slate-500' ?>"><?= formatCurrency((float)$b['commission_payable']) ?></td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <?php if ((float)$b['commission_payable'] > 0): ?>
          <form method="POST" action="<?= $base ?>/brokers/pay-commission/<?= (int)$b['id'] ?>" class="inline" onsubmit="return confirm('Mark ' + <?= json_encode(formatCurrency((float)$b['commission_payable'])) ?> + ' as paid to <?= h(addslashes($b['name'])) ?>?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-emerald-600 hover:underline text-xs">Pay</button>
          </form>
          <?php endif; ?>
          <a href="<?= $base ?>/brokers/edit/<?= (int)$b['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Edit</a>
          <form method="POST" action="<?= $base ?>/brokers/delete/<?= (int)$b['id'] ?>" class="inline" onsubmit="return confirm('Delete this broker?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($brokers)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No brokers yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?= renderPagination($page, $totalPages, $base . '/brokers') ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
