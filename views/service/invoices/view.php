<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="max-w-md mx-auto">
  <div class="flex items-center justify-between mb-4 print:hidden">
    <a href="<?= $base ?>/service-invoices" class="text-sm text-slate-500">&larr; Back to Invoices</a>
    <div class="flex gap-2">
      <button onclick="window.print()" class="bg-slate-700 text-white text-sm px-3 py-1.5 rounded">Print</button>
      <?php if ($invoice['status'] !== 'CANCELLED'): ?>
      <form method="POST" action="<?= $base ?>/service-invoices/cancel/<?= (int)$invoice['id'] ?>" onsubmit="return confirm('Cancel this invoice?') && (this.reason.value = prompt('Reason?') || 'No reason given');">
        <?= CsrfHelper::getField() ?><input type="hidden" name="reason" value="">
        <button class="bg-red-600 text-white text-sm px-3 py-1.5 rounded">Cancel Invoice</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($invoice['status'] === 'CANCELLED'): ?>
    <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2 print:hidden">
      Cancelled: <?= h($invoice['cancel_reason']) ?> · <?= formatDateTime($invoice['cancelled_at']) ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 font-mono text-sm mb-4">
    <div class="text-center mb-4">
      <div class="font-bold text-base"><?= h($org['name']) ?></div>
      <div class="text-xs text-slate-500">Service Invoice</div>
    </div>
    <div class="flex justify-between text-xs text-slate-600 mb-3">
      <span><?= h($invoice['invoice_no']) ?></span><span><?= formatDate($invoice['invoice_date']) ?></span>
    </div>
    <?php if ($invoice['customer_name']): ?>
    <div class="text-xs text-slate-600 mb-3">Customer: <?= h($invoice['customer_name']) ?> <?= $invoice['mobile'] ? '· ' . h($invoice['mobile']) : '' ?></div>
    <?php endif; ?>

    <div class="border-t border-b border-dashed border-slate-300 py-2 space-y-1">
      <?php foreach ($invoice['items'] as $item): ?>
        <div class="flex justify-between"><span class="truncate pr-2"><?= h($item['name']) ?> x<?= rtrim(rtrim(number_format((float)$item['quantity'],2),'0'),'.') ?></span><span><?= formatCurrency((float)$item['total']) ?></span></div>
      <?php endforeach; ?>
    </div>

    <div class="pt-2 space-y-1">
      <?php if ((float)$invoice['labour_charges'] > 0): ?>
      <div class="flex justify-between text-slate-600"><span>Labour</span><span><?= formatCurrency((float)$invoice['labour_charges']) ?></span></div>
      <?php endif; ?>
      <?php if ((float)$invoice['discount'] > 0): ?>
      <div class="flex justify-between text-slate-600"><span>Discount</span><span>-<?= formatCurrency((float)$invoice['discount']) ?></span></div>
      <?php endif; ?>
      <div class="flex justify-between font-bold text-base border-t border-slate-300 mt-1 pt-1"><span>Grand Total</span><span><?= formatCurrency((float)$invoice['grand_total']) ?></span></div>
      <div class="flex justify-between text-slate-600"><span>Paid</span><span><?= formatCurrency((float)$invoice['paid_amount']) ?></span></div>
      <?php if ((float)$invoice['balance_amount'] > 0): ?>
      <div class="flex justify-between font-semibold text-amber-600"><span>Balance Due</span><span><?= formatCurrency((float)$invoice['balance_amount']) ?></span></div>
      <?php endif; ?>
    </div>
    <div class="text-center text-xs text-slate-400 mt-4">Thank you!</div>
  </div>

  <?php if ((float)$invoice['balance_amount'] > 0 && $invoice['status'] !== 'CANCELLED'): ?>
  <form method="POST" action="<?= $base ?>/service-invoices/recordPayment/<?= (int)$invoice['id'] ?>" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-end gap-3 print:hidden">
    <?= CsrfHelper::getField() ?>
    <div><label class="block text-xs font-medium text-slate-700 mb-1">Record Payment</label>
      <input type="number" step="0.01" min="0.01" max="<?= (float)$invoice['balance_amount'] ?>" name="amount" required class="rounded border border-slate-300 px-3 py-2 text-sm w-40"></div>
    <button type="submit" class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-green-700">Record</button>
  </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
