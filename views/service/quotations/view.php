<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$statusColors = ['Draft'=>'bg-slate-100 text-slate-600','Sent'=>'bg-blue-100 text-blue-700','Approved'=>'bg-green-100 text-green-700','ConvertedToJob'=>'bg-purple-100 text-purple-700','ConvertedToInvoice'=>'bg-emerald-100 text-emerald-700'];
$converted = in_array($quotation['status'], ['ConvertedToJob', 'ConvertedToInvoice'], true);
?>

<div class="max-w-md mx-auto">
  <div class="flex items-center justify-between mb-4 print:hidden">
    <a href="<?= $base ?>/service-quotations" class="text-sm text-slate-500">&larr; Back to Quotations</a>
    <button onclick="window.print()" class="bg-slate-700 text-white text-sm px-3 py-1.5 rounded">Print</button>
  </div>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 font-mono text-sm mb-4">
    <div class="text-center mb-4">
      <div class="font-bold text-base"><?= h($org['name']) ?></div>
      <div class="text-xs text-slate-500">Quotation</div>
    </div>
    <div class="flex justify-between text-xs text-slate-600 mb-3">
      <span><?= h($quotation['quote_no']) ?></span><span><?= formatDate($quotation['quote_date']) ?></span>
    </div>
    <?php if ($quotation['customer_name']): ?>
    <div class="text-xs text-slate-600 mb-1">Customer: <?= h($quotation['customer_name']) ?></div>
    <?php endif; ?>
    <div class="text-xs text-slate-600 mb-3">Valid Until: <?= formatDate($quotation['valid_until']) ?></div>

    <div class="border-t border-b border-dashed border-slate-300 py-2 space-y-1">
      <?php foreach ($quotation['items'] as $item): ?>
        <div class="flex justify-between"><span class="truncate pr-2"><?= h($item['name']) ?> x<?= rtrim(rtrim(number_format((float)$item['quantity'],2),'0'),'.') ?></span><span><?= formatCurrency((float)$item['total']) ?></span></div>
      <?php endforeach; ?>
    </div>

    <div class="pt-2 space-y-1">
      <?php if ((float)$quotation['labour_charges'] > 0): ?>
      <div class="flex justify-between text-slate-600"><span>Labour</span><span><?= formatCurrency((float)$quotation['labour_charges']) ?></span></div>
      <?php endif; ?>
      <?php if ((float)$quotation['discount'] > 0): ?>
      <div class="flex justify-between text-slate-600"><span>Discount</span><span>-<?= formatCurrency((float)$quotation['discount']) ?></span></div>
      <?php endif; ?>
      <div class="flex justify-between font-bold text-base border-t border-slate-300 mt-1 pt-1"><span>Grand Total</span><span><?= formatCurrency((float)$quotation['grand_total']) ?></span></div>
    </div>
    <?php if ($quotation['notes']): ?>
    <div class="text-xs text-slate-500 mt-3"><?= h($quotation['notes']) ?></div>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 print:hidden space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-sm text-slate-500">Status</span>
      <span class="text-xs px-2 py-0.5 rounded-full <?= $statusColors[$quotation['status']] ?? '' ?>"><?= h(str_replace(['ConvertedToJob','ConvertedToInvoice'], ['Converted (Job)','Converted (Invoice)'], $quotation['status'])) ?></span>
    </div>

    <?php if (!$converted): ?>
    <form method="POST" action="<?= $base ?>/service-quotations/status/<?= (int)$quotation['id'] ?>" class="flex gap-2">
      <?= CsrfHelper::getField() ?>
      <select name="status" class="flex-1 rounded border border-slate-300 px-3 py-2 text-sm">
        <?php foreach (['Draft','Sent','Approved'] as $st): ?>
          <option value="<?= $st ?>" <?= $quotation['status']===$st?'selected':'' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="bg-slate-700 text-white text-sm px-4 py-2 rounded">Update</button>
    </form>

    <?php if ($quotation['status'] === 'Approved'): ?>
    <form method="POST" action="<?= $base ?>/service-quotations/convertToInvoice/<?= (int)$quotation['id'] ?>">
      <?= CsrfHelper::getField() ?>
      <button type="submit" class="w-full bg-green-600 text-white text-sm font-medium py-2.5 rounded hover:bg-green-700">Convert to Invoice</button>
    </form>
    <?php endif; ?>
    <?php else: ?>
    <p class="text-sm text-slate-500">This quotation has already been converted.</p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
