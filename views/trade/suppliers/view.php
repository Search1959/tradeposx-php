<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h1 class="text-xl font-semibold text-slate-900"><?= h($supplier['name']) ?></h1>
  <div class="flex items-center gap-2">
    <a href="<?= $base ?>/suppliers" class="text-sm text-slate-500">&larr; Suppliers</a>
    <a href="<?= $base ?>/suppliers/edit/<?= (int)$supplier['id'] ?>" class="bg-white border border-slate-300 text-slate-700 text-sm px-3 py-1.5 rounded hover:bg-slate-50">Edit</a>
    <a href="<?= $base ?>/purchases/new" class="bg-indigo-600 text-white text-sm px-3 py-1.5 rounded hover:bg-indigo-700">+ New Purchase</a>
  </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
  <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-xs text-slate-500">Total purchased</div><div class="text-xl font-bold text-slate-900 mt-1"><?= formatCurrency($totalBought) ?></div></div>
  <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-xs text-slate-500">We owe them</div><div class="text-xl font-bold mt-1 <?= (float)$supplier['outstanding_balance'] > 0 ? 'text-amber-600' : 'text-slate-900' ?>"><?= formatCurrency((float)$supplier['outstanding_balance']) ?></div></div>
  <div class="bg-white rounded-xl border border-slate-200 p-4 col-span-2 md:col-span-1"><div class="text-xs text-slate-500">Bills</div><div class="text-xl font-bold text-slate-900 mt-1"><?= count($purchases) ?></div></div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4 text-sm grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
  <?php $rows = ['Contact person' => $supplier['contact_person'], 'Company' => $supplier['company_name'], 'Category' => $supplier['category'], 'GSTIN' => $supplier['gstin'], 'City' => $supplier['city'], 'Address' => $supplier['address'], 'Notes' => $supplier['notes']];
  foreach ($rows as $label => $val): if (trim((string)$val) === '') continue; ?>
    <div><span class="text-slate-500"><?= h($label) ?>:</span> <span class="text-slate-900"><?= h($val) ?></span></div>
  <?php endforeach; ?>
  <?php if (!empty($supplier['mobile'])): ?>
    <div><span class="text-slate-500">Mobile:</span> <span class="text-slate-900"><?= h($supplier['mobile']) ?></span>
      <a href="<?= h(whatsappLink($supplier['mobile'], 'Hello ' . $supplier['name'] . ',')) ?>" target="_blank" rel="noopener" class="text-green-600 hover:underline text-xs ml-2">💬 WhatsApp</a></div>
  <?php endif; ?>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
  <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900">Purchases from this supplier</div>
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase"><tr><th class="text-left px-4 py-2">Purchase #</th><th class="text-left px-4 py-2">Date</th><th class="text-left px-4 py-2">Bill #</th><th class="text-right px-4 py-2">Amount</th><th class="text-center px-4 py-2">Status</th><th class="px-4 py-2"></th></tr></thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($purchases as $p): ?>
        <tr>
          <td class="px-4 py-2 font-medium text-slate-900"><?= h($p['purchase_number']) ?></td>
          <td class="px-4 py-2 text-slate-500"><?= formatDate($p['purchase_date']) ?></td>
          <td class="px-4 py-2 text-slate-500"><?= h($p['invoice_number'] ?? '') ?></td>
          <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$p['grand_total']) ?></td>
          <td class="px-4 py-2 text-center text-xs"><?= $p['status'] === 'CANCELLED' ? '<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700">Cancelled</span>' : h($p['payment_status']) ?></td>
          <td class="px-4 py-2 text-right"><a href="<?= $base ?>/purchases/view/<?= (int)$p['id'] ?>" class="text-indigo-600 hover:underline text-xs">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$purchases): ?><tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No purchases from this supplier yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
