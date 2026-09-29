<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900"><?= h($purchase['purchase_number']) ?></h1>
  <div class="flex items-center gap-2">
    <a href="<?= $base ?>/purchases" class="text-sm text-slate-500">&larr; Back to Purchases</a>
    <?php if ($purchase['status'] !== 'CANCELLED'): ?>
      <form method="POST" action="<?= $base ?>/purchases/cancel/<?= (int)$purchase['id'] ?>" onsubmit="return confirm('Cancel this purchase? Stock and supplier balance will be reversed.') && (this.reason.value = prompt('Reason for cancelling?') || 'No reason given');">
        <?= CsrfHelper::getField() ?>
        <input type="hidden" name="reason" value="">
        <button class="bg-red-600 text-white text-sm px-3 py-1.5 rounded">Cancel Purchase</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($purchase['status'] === 'CANCELLED'): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">
    Cancelled: <?= h($purchase['cancel_reason']) ?> · <?= formatDateTime($purchase['cancelled_at']) ?>
  </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-4">
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
    <div><div class="text-xs text-slate-500">Supplier</div><div class="font-medium"><?= h($purchase['supplier_name'] ?? '—') ?></div></div>
    <div><div class="text-xs text-slate-500">Invoice #</div><div class="font-medium"><?= h($purchase['invoice_number'] ?? '—') ?></div></div>
    <div><div class="text-xs text-slate-500">Date</div><div class="font-medium"><?= formatDate($purchase['purchase_date']) ?></div></div>
    <div><div class="text-xs text-slate-500">Status</div><div class="font-medium"><?= h($purchase['payment_status']) ?></div></div>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto mb-4">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Product</th>
        <th class="text-right px-4 py-2">Qty</th>
        <th class="text-right px-4 py-2">Rate</th>
        <th class="text-right px-4 py-2">GST</th>
        <th class="text-right px-4 py-2">Total</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($purchase['items'] as $item): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($item['product_name']) ?></td>
        <td class="px-4 py-2 text-right"><?= rtrim(rtrim(number_format((float)$item['quantity'],3),'0'),'.') ?> <?= h($item['unit'] ?? '') ?></td>
        <td class="px-4 py-2 text-right"><?= formatCurrency((float)$item['purchase_price']) ?></td>
        <td class="px-4 py-2 text-right"><?= formatCurrency((float)$item['gst_amount']) ?></td>
        <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$item['total_price']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 max-w-sm ml-auto text-sm space-y-1">
  <div class="flex justify-between text-slate-600"><span>Subtotal</span><span><?= formatCurrency((float)$purchase['subtotal']) ?></span></div>
  <div class="flex justify-between text-slate-600"><span>Tax</span><span><?= formatCurrency((float)$purchase['tax_amount']) ?></span></div>
  <div class="flex justify-between text-slate-600"><span>Freight</span><span><?= formatCurrency((float)$purchase['freight_charges']) ?></span></div>
  <div class="flex justify-between text-slate-600"><span>Discount</span><span>-<?= formatCurrency((float)$purchase['discount_amount']) ?></span></div>
  <div class="flex justify-between font-semibold border-t border-slate-100 pt-1"><span>Grand Total</span><span><?= formatCurrency((float)$purchase['grand_total']) ?></span></div>
  <div class="flex justify-between text-slate-600"><span>Paid</span><span><?= formatCurrency((float)$purchase['paid_amount']) ?></span></div>
  <?php $bal = (float)$purchase['grand_total'] - (float)$purchase['paid_amount']; if ($bal > 0): ?>
  <div class="flex justify-between font-semibold text-amber-600"><span>Balance Due</span><span><?= formatCurrency($bal) ?></span></div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
