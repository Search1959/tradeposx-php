<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="max-w-md mx-auto">
  <div class="flex items-center justify-between mb-4 print:hidden">
    <a href="<?= $base ?>/sales" class="text-sm text-slate-500">&larr; Back to Sales</a>
    <div class="flex gap-2">
      <button onclick="window.print()" class="bg-slate-700 text-white text-sm px-3 py-1.5 rounded">Print</button>
      <?php if (!$sale['status']): ?>
      <form method="POST" action="<?= $base ?>/sales/void/<?= (int)$sale['id'] ?>" onsubmit="return confirm('Void this sale? Stock and Udhaar will be reversed.') && (this.reason.value = prompt('Reason for voiding?') || 'No reason given');">
        <?= CsrfHelper::getField() ?>
        <input type="hidden" name="reason" value="">
        <button class="bg-red-600 text-white text-sm px-3 py-1.5 rounded">Void Sale</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($sale['status'] === 'CANCELLED'): ?>
    <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2 print:hidden">
      Cancelled: <?= h($sale['cancel_reason']) ?> · <?= formatDateTime($sale['cancelled_at']) ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 font-mono text-sm">
    <div class="text-center mb-4">
      <div class="font-bold text-base"><?= h($org['name']) ?></div>
      <div class="text-xs text-slate-500">Sale Receipt</div>
    </div>
    <div class="flex justify-between text-xs text-slate-600 mb-3">
      <span><?= h($sale['sale_number']) ?></span>
      <span><?= formatDateTime($sale['created_at']) ?></span>
    </div>
    <?php if ($sale['customer_name']): ?>
    <div class="text-xs text-slate-600 mb-3">Customer: <?= h($sale['customer_name']) ?> <?= $sale['customer_mobile'] ? '· ' . h($sale['customer_mobile']) : '' ?></div>
    <?php endif; ?>

    <div class="border-t border-b border-dashed border-slate-300 py-2 space-y-1">
      <?php foreach ($sale['items'] as $item): ?>
        <div class="flex justify-between">
          <span class="truncate pr-2"><?= h($item['product_name']) ?> x<?= rtrim(rtrim(number_format((float)$item['quantity'],2),'0'),'.') ?></span>
          <span><?= formatCurrency((float)$item['total_price']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="pt-2 space-y-1">
      <div class="flex justify-between text-slate-600"><span>Subtotal</span><span><?= formatCurrency((float)$sale['subtotal']) ?></span></div>
      <?php if ((float)$sale['discount'] > 0): ?>
      <div class="flex justify-between text-slate-600"><span>Discount</span><span>-<?= formatCurrency((float)$sale['discount']) ?></span></div>
      <?php endif; ?>
      <?php if ((float)$sale['total_tax_amount'] > 0): ?>
      <div class="flex justify-between text-slate-600"><span>GST</span><span><?= formatCurrency((float)$sale['total_tax_amount']) ?></span></div>
      <?php endif; ?>
      <div class="flex justify-between font-bold text-base border-t border-slate-300 mt-1 pt-1">
        <span>Grand Total</span><span><?= formatCurrency((float)$sale['grand_total']) ?></span>
      </div>
      <div class="flex justify-between text-slate-600"><span>Received (<?= h($sale['payment_method']) ?>)</span><span><?= formatCurrency((float)$sale['received_amount']) ?></span></div>
      <?php $owed = max(0, (float)$sale['grand_total'] - (float)$sale['received_amount']); if ($owed > 0): ?>
      <div class="flex justify-between font-semibold text-amber-600"><span>Udhaar (Balance Due)</span><span><?= formatCurrency($owed) ?></span></div>
      <?php endif; ?>
    </div>

    <div class="text-center text-xs text-slate-400 mt-4">Thank you!</div>
  </div>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
