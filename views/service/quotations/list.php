<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$statusColors = ['Draft'=>'bg-slate-100 text-slate-600','Sent'=>'bg-blue-100 text-blue-700','Approved'=>'bg-green-100 text-green-700','ConvertedToJob'=>'bg-purple-100 text-purple-700','ConvertedToInvoice'=>'bg-emerald-100 text-emerald-700'];
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Service Quotations</h1>
  <a href="<?= $base ?>/service-quotations/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Quotation</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Quote #</th><th class="text-left px-4 py-2">Date</th><th class="text-left px-4 py-2">Customer</th><th class="text-right px-4 py-2">Amount</th><th class="text-center px-4 py-2">Status</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($quotations as $q): ?>
      <tr class="hover:bg-slate-50 cursor-pointer" onclick="location.href='<?= $base ?>/service-quotations/view/<?= (int)$q['id'] ?>'">
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($q['quote_no']) ?></td>
        <td class="px-4 py-2 text-slate-500"><?= formatDate($q['quote_date']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($q['customer_name'] ?? 'Walk-in') ?></td>
        <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$q['grand_total']) ?></td>
        <td class="px-4 py-2 text-center"><span class="text-xs px-2 py-0.5 rounded-full <?= $statusColors[$q['status']] ?? '' ?>"><?= h(str_replace(['ConvertedToJob','ConvertedToInvoice'], ['Converted (Job)','Converted (Invoice)'], $q['status'])) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($quotations)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No quotations yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
