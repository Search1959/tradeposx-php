<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Staff</h1>
  <a href="<?= $base ?>/service-staff/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Staff Member</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Role</th><th class="text-left px-4 py-2">Mobile</th>
        <th class="text-center px-4 py-2">Commission %</th><th class="text-right px-4 py-2">Commission Payable</th>
        <th class="text-center px-4 py-2">Status</th><th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($staff as $s): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($s['name']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($s['role'] ?? '') ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($s['mobile'] ?? '') ?></td>
        <td class="px-4 py-2 text-center text-slate-600"><?= h((string)$s['commission_percent']) ?>%</td>
        <td class="px-4 py-2 text-right <?= (float)$s['commission_payable'] > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400' ?>"><?= formatCurrency((float)$s['commission_payable']) ?></td>
        <td class="px-4 py-2 text-center">
          <span class="text-xs px-2 py-0.5 rounded-full <?= $s['status']==='Active'?'bg-green-100 text-green-700':'bg-slate-100 text-slate-600' ?>"><?= h($s['status']) ?></span>
        </td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <?php if ((float)$s['commission_payable'] > 0): ?>
          <form method="POST" action="<?= $base ?>/service-staff/payCommission/<?= (int)$s['id'] ?>" class="inline" onsubmit="return !!(this.amount.value = prompt('Amount to pay out (of ₹<?= number_format((float)$s['commission_payable'],2) ?>)?'));">
            <?= CsrfHelper::getField() ?>
            <input type="hidden" name="amount" value="">
            <button class="text-green-600 hover:underline text-xs">Pay</button>
          </form>
          <?php endif; ?>
          <a href="<?= $base ?>/service-staff/edit/<?= (int)$s['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Edit</a>
          <form method="POST" action="<?= $base ?>/service-staff/delete/<?= (int)$s['id'] ?>" class="inline" onsubmit="return confirm('Delete this staff member?');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($staff)): ?>
      <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No staff yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
