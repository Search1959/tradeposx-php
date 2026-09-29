<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$isGst = $sale['tax_type'] !== 'EXEMPT' && (float)$sale['total_tax_amount'] > 0;
$docTitle = $isGst ? 'Tax Invoice' : 'Bill of Supply';
$owed = max(0, (float)$sale['grand_total'] - (float)$sale['received_amount']);
// Sales made before CGST/SGST tracking existed have these columns NULL —
// fall back to the pre-existing aggregates so old receipts still read correctly.
$taxableDisplay = $sale['taxable_amount'] !== null ? (float)$sale['taxable_amount'] : (float)$sale['subtotal'];
$cgstDisplay = $sale['cgst_amount'] !== null ? (float)$sale['cgst_amount'] : round((float)$sale['total_tax_amount'] / 2, 2);
$sgstDisplay = $sale['sgst_amount'] !== null ? (float)$sale['sgst_amount'] : round((float)$sale['total_tax_amount'] - $cgstDisplay, 2);
?>

<div class="max-w-2xl mx-auto">
  <div class="flex items-center justify-between mb-4 print:hidden">
    <a href="<?= $base ?>/sales" class="text-sm text-slate-500">&larr; Back to Sales</a>
    <div class="flex gap-2">
      <button onclick="window.print()" class="bg-slate-700 text-white text-sm px-3 py-1.5 rounded">Print</button>
      <?php if (!empty($sale['customer_mobile'])): ?>
        <?php $billMsg = saleBillWhatsAppMessage($org['name'], $sale['sale_number'], $sale['customer_name'] ?: 'Customer', $sale['items'], (float)$sale['grand_total'], (float)$sale['received_amount'], $owed, $org['phone'] ?? null); ?>
        <a href="<?= h(whatsappLink($sale['customer_mobile'], $billMsg)) ?>" target="_blank" rel="noopener"
           class="bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-700">💬 Send Bill</a>
      <?php endif; ?>
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

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 print:border-0 print:shadow-none overflow-hidden">

    <!-- Letterhead -->
    <div class="px-6 pt-6 pb-4 border-b-2 border-slate-800">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-xl font-bold text-slate-900 tracking-tight"><?= h($org['name']) ?></div>
          <?php if (!empty($org['address'])): ?><div class="text-xs text-slate-500 mt-0.5"><?= h($org['address']) ?></div><?php endif; ?>
          <?php if (!empty($org['phone'])): ?><div class="text-xs text-slate-500"><?= h($org['phone']) ?></div><?php endif; ?>
        </div>
        <div class="text-right">
          <div class="inline-block text-xs font-semibold uppercase tracking-wide bg-slate-900 text-white px-3 py-1 rounded"><?= h($docTitle) ?></div>
          <?php if (!empty($org['gstin'])): ?><div class="text-xs text-slate-500 mt-1.5">GSTIN: <span class="font-mono"><?= h($org['gstin']) ?></span></div><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Invoice meta / bill-to -->
    <div class="px-6 py-4 grid grid-cols-2 gap-4 text-sm border-b border-slate-100">
      <div>
        <div class="text-xs uppercase tracking-wide text-slate-400 mb-1">Billed To</div>
        <?php if ($sale['customer_name']): ?>
          <div class="font-medium text-slate-900"><?= h($sale['customer_name']) ?></div>
          <?php if ($sale['customer_mobile']): ?><div class="text-xs text-slate-500"><?= h($sale['customer_mobile']) ?></div><?php endif; ?>
          <?php if (!empty($sale['customer_gstin'])): ?><div class="text-xs text-slate-500">GSTIN: <span class="font-mono"><?= h($sale['customer_gstin']) ?></span></div><?php endif; ?>
        <?php else: ?>
          <div class="text-slate-500">Walk-in Customer</div>
        <?php endif; ?>
      </div>
      <div class="text-right">
        <div class="text-xs uppercase tracking-wide text-slate-400 mb-1">Invoice No.</div>
        <div class="font-mono font-medium text-slate-900"><?= h($sale['sale_number']) ?></div>
        <div class="text-xs text-slate-500 mt-1"><?= formatDateTime($sale['created_at']) ?></div>
      </div>
    </div>

    <!-- Line items -->
    <div class="px-6 py-4">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-xs uppercase tracking-wide text-slate-400 border-b border-slate-200">
            <th class="text-left pb-2 font-medium w-8">#</th>
            <th class="text-left pb-2 font-medium">Item</th>
            <?php if ($isGst): ?><th class="text-left pb-2 font-medium">HSN</th><?php endif; ?>
            <th class="text-right pb-2 font-medium">Qty</th>
            <th class="text-right pb-2 font-medium">Rate</th>
            <?php if ($isGst): ?><th class="text-right pb-2 font-medium">GST%</th><?php endif; ?>
            <th class="text-right pb-2 font-medium">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($sale['items'] as $i => $item): ?>
          <tr>
            <td class="py-1.5 text-slate-400"><?= $i + 1 ?></td>
            <td class="py-1.5 text-slate-900"><?= h($item['product_name']) ?></td>
            <?php if ($isGst): ?><td class="py-1.5 text-slate-500 font-mono text-xs"><?= h($item['hsn'] ?? '—') ?></td><?php endif; ?>
            <td class="py-1.5 text-right text-slate-600"><?= rtrim(rtrim(number_format((float)$item['quantity'], 2), '0'), '.') ?></td>
            <td class="py-1.5 text-right text-slate-600"><?= formatCurrency((float)$item['unit_price']) ?></td>
            <?php if ($isGst): ?><td class="py-1.5 text-right text-slate-500"><?= (float)$item['gst_rate'] ?: '—' ?><?= (float)$item['gst_rate'] ? '%' : '' ?></td><?php endif; ?>
            <td class="py-1.5 text-right font-medium text-slate-900"><?= formatCurrency((float)$item['total_price']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Totals -->
    <div class="px-6 pb-4">
      <div class="ml-auto max-w-xs space-y-1 text-sm">
        <div class="flex justify-between text-slate-600">
          <span><?= $isGst ? 'Taxable Amount' : 'Subtotal' ?></span>
          <span><?= formatCurrency($isGst ? $taxableDisplay : (float)$sale['subtotal']) ?></span>
        </div>
        <?php if ($isGst): ?>
        <div class="flex justify-between text-slate-600"><span>CGST</span><span><?= formatCurrency($cgstDisplay) ?></span></div>
        <div class="flex justify-between text-slate-600"><span>SGST</span><span><?= formatCurrency($sgstDisplay) ?></span></div>
        <?php endif; ?>
        <?php if ((float)$sale['discount'] > 0): ?>
        <div class="flex justify-between text-slate-600"><span>Discount</span><span>-<?= formatCurrency((float)$sale['discount']) ?></span></div>
        <?php endif; ?>
        <div class="flex justify-between font-bold text-base border-t border-slate-800 mt-1.5 pt-1.5">
          <span>Grand Total</span><span><?= formatCurrency((float)$sale['grand_total']) ?></span>
        </div>
      </div>
      <div class="text-xs text-slate-400 mt-2 italic"><?= h(amountInWordsINR((float)$sale['grand_total'])) ?></div>
    </div>

    <!-- Payment -->
    <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-between text-sm">
      <span class="text-slate-500">Payment (<?= h($sale['payment_method']) ?>)</span>
      <span class="font-medium text-slate-900"><?= formatCurrency((float)$sale['received_amount']) ?></span>
    </div>
    <?php if ($owed > 0): ?>
    <div class="px-6 py-3 bg-amber-50 border-t border-amber-100 flex justify-between text-sm">
      <span class="font-semibold text-amber-700">Udhaar (Balance Due)</span>
      <span class="font-semibold text-amber-700"><?= formatCurrency($owed) ?></span>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="px-6 py-5 flex items-end justify-between">
      <p class="text-xs text-slate-400">Thank you for your business!</p>
      <div class="text-center">
        <div class="text-xs text-slate-500 border-t border-slate-300 pt-1 px-4">Authorized Signatory</div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
