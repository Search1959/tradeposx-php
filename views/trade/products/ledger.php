<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$typeLabels = [
  'STOCK_IN_PURCHASE' => ['+ Purchase', 'text-green-600'], 'STOCK_OUT_SALE' => ['− Sale', 'text-red-600'],
  'MANUAL_ADD' => ['+ Manual', 'text-green-600'], 'MANUAL_REDUCE' => ['− Manual', 'text-red-600'],
  'DAMAGE_EXPIRED' => ['− Damage/Expired', 'text-red-600'], 'INITIAL_STOCK' => ['+ Opening', 'text-slate-500'],
  'SALE_CANCELLED' => ['+ Sale Voided', 'text-green-600'],
];
?>

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-xl font-semibold text-slate-900"><?= h($product['name']) ?> — Stock Ledger</h1>
    <div class="text-sm text-slate-500">Current stock: <span class="font-semibold text-slate-900"><?= rtrim(rtrim(number_format((float)$product['current_stock'],3),'0'),'.') ?> <?= h($product['unit']) ?></span></div>
  </div>
  <a href="<?= $base ?>/products" class="text-sm text-slate-500">&larr; Back to Products</a>
</div>

<form method="POST" action="<?= $base ?>/products/adjustStock/<?= (int)$product['id'] ?>" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4 flex items-end gap-3">
  <?= CsrfHelper::getField() ?>
  <div>
    <label class="block text-xs font-medium text-slate-700 mb-1">Adjust Stock (+/-)</label>
    <input type="number" step="0.001" name="delta" required class="rounded border border-slate-300 px-3 py-2 text-sm w-32">
  </div>
  <div class="flex-1">
    <label class="block text-xs font-medium text-slate-700 mb-1">Reason</label>
    <input type="text" name="reason" placeholder="e.g. Damaged, Stock count correction" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
  </div>
  <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Apply</button>
</form>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Date</th><th class="text-left px-4 py-2">Type</th><th class="text-right px-4 py-2">Qty</th><th class="text-left px-4 py-2">Reference</th><th class="text-left px-4 py-2">Notes</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($transactions as $t): $label = $typeLabels[$t['type']] ?? [$t['type'], 'text-slate-500']; ?>
      <tr>
        <td class="px-4 py-2 text-slate-500"><?= formatDateTime($t['created_at']) ?></td>
        <td class="px-4 py-2 font-medium <?= $label[1] ?>"><?= h($label[0]) ?></td>
        <td class="px-4 py-2 text-right"><?= rtrim(rtrim(number_format((float)$t['quantity'],3),'0'),'.') ?></td>
        <td class="px-4 py-2 text-slate-500"><?= h($t['reference_id'] ?? '') ?></td>
        <td class="px-4 py-2 text-slate-500"><?= h($t['notes'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($transactions)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No stock movements yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
