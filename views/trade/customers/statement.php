<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-xl font-semibold text-slate-900"><?= h($customer['name']) ?></h1>
    <div class="text-sm text-slate-500"><?= h($customer['mobile']) ?></div>
  </div>
  <div class="text-right">
    <div class="text-xs text-slate-500">Current Udhaar Balance</div>
    <div class="text-2xl font-semibold <?= (float)$customer['current_balance'] > 0 ? 'text-amber-600' : 'text-slate-900' ?>">
      <?= formatCurrency((float)$customer['current_balance']) ?>
    </div>
  </div>
</div>

<form method="POST" action="<?= $base ?>/customers/record-payment/<?= (int)$customer['id'] ?>"
      class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6 flex items-end gap-3">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-xs font-medium text-slate-700 mb-1">Record Payment Received</label>
    <input type="number" step="0.01" name="amount" min="0.01" required class="rounded border border-slate-300 px-3 py-2 text-sm w-40">
  </div>
  <div>
    <label class="block text-xs font-medium text-slate-700 mb-1">Method</label>
    <select name="payment_method" class="rounded border border-slate-300 px-3 py-2 text-sm">
      <option value="CASH">Cash</option>
      <option value="UPI">UPI</option>
      <option value="BANK">Bank</option>
      <option value="OTHER">Other</option>
    </select>
  </div>
  <div class="flex-1">
    <label class="block text-xs font-medium text-slate-700 mb-1">Notes</label>
    <input type="text" name="notes" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <button type="submit" class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-green-700">Record</button>
</form>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Date</th>
        <th class="text-left px-4 py-2">Type</th>
        <th class="text-right px-4 py-2">Amount</th>
        <th class="text-right px-4 py-2">Balance After</th>
        <th class="text-left px-4 py-2">Notes</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($transactions as $t): $isCredit = $t['type'] === 'CREDIT_SALE' || $t['type'] === 'OPENING_BALANCE'; ?>
      <tr>
        <td class="px-4 py-2 text-slate-500"><?= formatDateTime($t['created_at']) ?></td>
        <td class="px-4 py-2">
          <span class="text-xs px-2 py-0.5 rounded-full <?= $isCredit ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' ?>">
            <?= h(str_replace('_', ' ', $t['type'])) ?>
          </span>
        </td>
        <td class="px-4 py-2 text-right <?= $isCredit ? 'text-amber-600' : 'text-green-600' ?>">
          <?= $isCredit ? '+' : '−' ?><?= formatCurrency((float)$t['amount']) ?>
        </td>
        <td class="px-4 py-2 text-right font-medium text-slate-900"><?= formatCurrency((float)$t['balance_after']) ?></td>
        <td class="px-4 py-2 text-slate-500"><?= h($t['notes'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($transactions)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No transactions yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
