<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$isGst = $invoice['tax_type'] !== 'EXEMPT' && (float)$invoice['gst_amount'] > 0;
$docTitle = $isGst ? 'Tax Invoice' : 'Bill of Supply';
$gstBreakdown = $isGst ? gstBreakdownByRate($invoice['items'], $invoice['tax_type'] === 'INCLUSIVE', 'total') : [];
?>

<div class="max-w-2xl mx-auto">
  <div class="flex items-center justify-between mb-4 print:hidden">
    <a href="<?= $base ?>/service-invoices" class="text-sm text-slate-500">&larr; Back to Invoices</a>
    <div class="flex gap-2">
      <button onclick="window.print()" class="bg-slate-700 text-white text-sm px-3 py-1.5 rounded">Print</button>
      <?php if ($invoice['status'] !== 'CANCELLED'): ?><a href="<?= $base ?>/service-invoices/edit/<?= (int)$invoice['id'] ?>" class="bg-white border border-slate-300 text-slate-700 text-sm px-3 py-1.5 rounded hover:bg-slate-50">Edit</a><?php endif; ?>
      <?php $billMsg = serviceInvoiceWhatsAppMessage($org, $invoice); ?>
      <?php if (!empty($invoice['mobile'])): ?>
        <a href="<?= h(whatsappLink($invoice['mobile'], $billMsg)) ?>" target="_blank" rel="noopener"
           class="bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-700">💬 WhatsApp</a>
      <?php else: ?>
        <!-- Walk-in with no saved number: ask for one (or leave blank to pick the contact inside WhatsApp). -->
        <button type="button" data-msg="<?= h($billMsg) ?>" onclick="sendBillOnWhatsApp(this)"
                class="bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-700">💬 WhatsApp</button>
      <?php endif; ?>
      <button type="button" onclick="tpxShareBill('image')" class="bg-emerald-700 text-white text-sm px-3 py-1.5 rounded hover:bg-emerald-800" title="Send the bill as a picture on WhatsApp">🖼 Image</button>
      <button type="button" onclick="tpxShareBill('pdf')" class="bg-emerald-700 text-white text-sm px-3 py-1.5 rounded hover:bg-emerald-800" title="Send the bill as a PDF on WhatsApp">📄 PDF</button>

      <?php if ($isAdmin): ?>
      <form method="POST" action="<?= $base ?>/service-invoices/delete/<?= (int)$invoice['id'] ?>" onsubmit="return confirm('Delete invoice <?= h($invoice['invoice_no']) ?> permanently?\n\nStock is put back. This cannot be undone.');">
        <?= CsrfHelper::getField() ?>
        <button class="bg-red-600 text-white text-sm px-3 py-1.5 rounded">Delete Invoice</button>
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
        <?php if ($isGst && !empty($gstBreakdown)): ?>
          <?php foreach ($gstBreakdown as $g): ?>
          <div class="flex justify-between text-slate-600"><span>CGST @ <?= halfGstRate($g['rate']) ?>%</span><span><?= formatCurrency($g['tax'] / 2) ?></span></div>
          <div class="flex justify-between text-slate-600"><span>SGST @ <?= halfGstRate($g['rate']) ?>%</span><span><?= formatCurrency($g['tax'] / 2) ?></span></div>
          <?php endforeach; ?>
        <?php elseif ($isGst): ?>
        <?php
          // No per-item gst_rate to group by here (an older invoice, or one
          // created through a path that didn't record it per line) — back-derive
          // one effective rate from the invoice's own totals so CGST/SGST still
          // show a percentage instead of a bare, unexplained label.
          $effTaxable = (float) ($invoice['taxable_amount'] ?? 0);
          $effRate = $effTaxable > 0 ? round((float) $invoice['gst_amount'] / $effTaxable * 100, 2) : 0;
        ?>
        <div class="flex justify-between text-slate-600"><span>CGST @ <?= halfGstRate($effRate) ?>%</span><span><?= formatCurrency((float)($invoice['cgst_amount'] ?? $invoice['gst_amount'] / 2)) ?></span></div>
        <div class="flex justify-between text-slate-600"><span>SGST @ <?= halfGstRate($effRate) ?>%</span><span><?= formatCurrency((float)($invoice['sgst_amount'] ?? $invoice['gst_amount'] / 2)) ?></span></div>
        <?php endif; ?>
        <?php if ((float)$invoice['labour_charges'] > 0): ?>
        <div class="flex justify-between text-slate-600"><span>Labour</span><span><?= formatCurrency((float)$invoice['labour_charges']) ?></span></div>
        <?php endif; ?>
        <?php if ((float)$invoice['discount'] > 0): ?>
        <div class="flex justify-between text-slate-600">
          <span>Discount<?= $invoice['discount_percent'] !== null ? ' @ ' . rtrim(rtrim(number_format((float)$invoice['discount_percent'], 2), '0'), '.') . '%' : '' ?></span>
          <span>-<?= formatCurrency((float)$invoice['discount']) ?></span>
        </div>
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

<?php
require_once __DIR__ . '/../../shared/bill-share-data.php';
$billShareUpi = (string) ((new Organization())->find((int) $org['id'])['upi_id'] ?? '');
$billShare = billShareFromInvoice($org, $invoice, $isGst, $docTitle, $gstBreakdown, $billShareUpi);
require __DIR__ . '/../../shared/bill-share.php';
?>
<script>
function sendBillOnWhatsApp(btn) {
  const n = prompt("Customer's WhatsApp number (10 digits). Leave empty to choose the contact inside WhatsApp.");
  if (n === null) return;
  const d = n.replace(/\D/g, '');
  const num = d.length === 10 ? '91' + d : d;
  window.open('https://wa.me/' + num + '?text=' + encodeURIComponent(btn.dataset.msg), '_blank');
}
</script>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
