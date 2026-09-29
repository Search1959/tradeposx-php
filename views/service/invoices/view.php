<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$isGst = $invoice['tax_type'] !== 'EXEMPT' && (float)$invoice['gst_amount'] > 0;
$docTitle = $isGst ? 'Tax Invoice' : 'Bill of Supply';
?>

<div class="max-w-2xl mx-auto">
  <div class="flex items-center justify-between mb-4 print:hidden">
    <a href="<?= $base ?>/service-invoices" class="text-sm text-slate-500">&larr; Back to Invoices</a>
    <div class="flex gap-2">
      <button onclick="window.print()" class="bg-slate-700 text-white text-sm px-3 py-1.5 rounded">Print</button>
      <?php if (!empty($invoice['mobile'])): ?>
        <?php $billMsg = serviceInvoiceWhatsAppMessage($org['name'], $invoice['invoice_no'], $invoice['customer_name'] ?: 'Customer', $invoice['items'], (float)$invoice['grand_total'], (float)$invoice['paid_amount'], (float)$invoice['balance_amount'], $org['phone'] ?? null); ?>
        <a href="<?= h(whatsappLink($invoice['mobile'], $billMsg)) ?>" target="_blank" rel="noopener"
           class="bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-700">💬 Send Bill</a>
      <?php endif; ?>
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
        <?php if ($invoice['customer_name']): ?>
          <div class="font-medium text-slate-900"><?= h($invoice['customer_name']) ?></div>
          <?php if ($invoice['mobile']): ?><div class="text-xs text-slate-500"><?= h($invoice['mobile']) ?></div><?php endif; ?>
        <?php else: ?>
          <div class="text-slate-500">Walk-in Customer</div>
        <?php endif; ?>
      </div>
      <div class="text-right">
        <div class="text-xs uppercase tracking-wide text-slate-400 mb-1">Invoice No.</div>
        <div class="font-mono font-medium text-slate-900"><?= h($invoice['invoice_no']) ?></div>
        <div class="text-xs text-slate-500 mt-1"><?= formatDate($invoice['invoice_date']) ?></div>
      </div>
    </div>

    <!-- Line items -->
    <div class="px-6 py-4">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-xs uppercase tracking-wide text-slate-400 border-b border-slate-200">
            <th class="text-left pb-2 font-medium w-8">#</th>
            <th class="text-left pb-2 font-medium">Service</th>
            <th class="text-right pb-2 font-medium">Qty</th>
            <th class="text-right pb-2 font-medium">Rate</th>
            <?php if ($isGst): ?><th class="text-right pb-2 font-medium">GST%</th><?php endif; ?>
            <th class="text-right pb-2 font-medium">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($invoice['items'] as $i => $item): ?>
          <tr>
            <td class="py-1.5 text-slate-400"><?= $i + 1 ?></td>
            <td class="py-1.5 text-slate-900"><?= h($item['name']) ?></td>
            <td class="py-1.5 text-right text-slate-600"><?= rtrim(rtrim(number_format((float)$item['quantity'], 2), '0'), '.') ?></td>
            <td class="py-1.5 text-right text-slate-600"><?= formatCurrency((float)$item['price']) ?></td>
            <?php if ($isGst): ?><td class="py-1.5 text-right text-slate-500"><?= (float)$item['gst_rate'] ?: '—' ?><?= (float)$item['gst_rate'] ? '%' : '' ?></td><?php endif; ?>
            <td class="py-1.5 text-right font-medium text-slate-900"><?= formatCurrency((float)$item['total']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Totals -->
    <div class="px-6 pb-4">
      <div class="ml-auto max-w-xs space-y-1 text-sm">
        <div class="flex justify-between text-slate-600">
          <span><?= $isGst ? 'Taxable Amount' : 'Service Total' ?></span>
          <span><?= formatCurrency($isGst && $invoice['taxable_amount'] !== null ? (float)$invoice['taxable_amount'] : array_sum(array_map(fn($i) => (float)$i['total'], $invoice['items']))) ?></span>
        </div>
        <?php if ($isGst): ?>
        <div class="flex justify-between text-slate-600"><span>CGST</span><span><?= formatCurrency((float)($invoice['cgst_amount'] ?? $invoice['gst_amount'] / 2)) ?></span></div>
        <div class="flex justify-between text-slate-600"><span>SGST</span><span><?= formatCurrency((float)($invoice['sgst_amount'] ?? $invoice['gst_amount'] / 2)) ?></span></div>
        <?php endif; ?>
        <?php if ((float)$invoice['labour_charges'] > 0): ?>
        <div class="flex justify-between text-slate-600"><span>Labour</span><span><?= formatCurrency((float)$invoice['labour_charges']) ?></span></div>
        <?php endif; ?>
        <?php if ((float)$invoice['discount'] > 0): ?>
        <div class="flex justify-between text-slate-600"><span>Discount</span><span>-<?= formatCurrency((float)$invoice['discount']) ?></span></div>
        <?php endif; ?>
        <div class="flex justify-between font-bold text-base border-t border-slate-800 mt-1.5 pt-1.5">
          <span>Grand Total</span><span><?= formatCurrency((float)$invoice['grand_total']) ?></span>
        </div>
      </div>
      <div class="text-xs text-slate-400 mt-2 italic"><?= h(amountInWordsINR((float)$invoice['grand_total'])) ?></div>
    </div>

    <!-- Payment -->
    <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-between text-sm">
      <span class="text-slate-500">Paid<?= $invoice['payment_method'] ? ' (' . h($invoice['payment_method']) . ')' : '' ?></span>
      <span class="font-medium text-slate-900"><?= formatCurrency((float)$invoice['paid_amount']) ?></span>
    </div>
    <?php if ((float)$invoice['balance_amount'] > 0): ?>
    <div class="px-6 py-3 bg-amber-50 border-t border-amber-100 flex items-center justify-between text-sm">
      <span class="font-semibold text-amber-700">Balance Due</span>
      <div class="flex items-center gap-3">
        <span class="font-semibold text-amber-700"><?= formatCurrency((float)$invoice['balance_amount']) ?></span>
        <?php if (!empty($invoice['mobile'])): ?>
          <?php $msg = serviceBalanceReminderMessage($org['name'], $invoice['customer_name'] ?: 'Customer', $invoice['invoice_no'], (float)$invoice['balance_amount'], $org['phone'] ?? null); ?>
          <a href="<?= h(whatsappLink($invoice['mobile'], $msg)) ?>" target="_blank" rel="noopener"
             class="print:hidden text-xs bg-green-600 text-white px-2.5 py-1 rounded-full hover:bg-green-700">💬 Remind</a>
        <?php endif; ?>
      </div>
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

  <?php if ((float)$invoice['balance_amount'] > 0 && $invoice['status'] !== 'CANCELLED'): ?>
  <form method="POST" action="<?= $base ?>/service-invoices/recordPayment/<?= (int)$invoice['id'] ?>" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mt-4 flex items-end gap-3 print:hidden">
    <?= CsrfHelper::getField() ?>
    <div><label class="block text-xs font-medium text-slate-700 mb-1">Record Payment</label>
      <input type="number" step="0.01" min="0.01" max="<?= (float)$invoice['balance_amount'] ?>" name="amount" required class="rounded border border-slate-300 px-3 py-2 text-sm w-40"></div>
    <button type="submit" class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-green-700">Record</button>
  </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
