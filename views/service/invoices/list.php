<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Service Invoices</h1>
  <div class="flex items-center gap-2">
    <?php if ($isAdmin): $exportUrl = $base . '/service-invoices/export'; require __DIR__ . '/../../shared/export-menu.php'; endif; ?>
    <a href="<?= $base ?>/service-invoices/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Invoice</a>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm sticky-actions">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Invoice #</th><th class="text-left px-4 py-2">Date</th><th class="text-left px-4 py-2">Customer</th><th class="text-right px-4 py-2">Amount</th><th class="text-center px-4 py-2">Status</th><th class="px-4 py-2"></th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($invoices as $i): $bal = (float)$i['balance_amount']; ?>
      <tr class="hover:bg-slate-50 cursor-pointer" onclick="location.href='<?= $base ?>/service-invoices/view/<?= (int)$i['id'] ?>'">
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($i['invoice_no']) ?></td>
        <td class="px-4 py-2 text-slate-500"><?= formatDate($i['invoice_date']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($i['customer_name'] ?? 'Walk-in') ?></td>
        <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$i['grand_total']) ?></td>
        <td class="px-4 py-2 text-center">
          <?php $badge = ['PAID'=>'bg-green-100 text-green-700','PARTIAL'=>'bg-amber-100 text-amber-700','UNPAID'=>'bg-slate-100 text-slate-600','CANCELLED'=>'bg-red-100 text-red-700']; ?>
          <span class="text-xs px-2 py-0.5 rounded-full <?= $badge[$i['status']] ?? '' ?>"><?= h($i['status']) ?></span>
        </td>
        <td class="px-4 py-2 text-right" onclick="event.stopPropagation()">
          <?php if ($bal > 0 && !empty($i['mobile'])): ?>
            <?php $msg = serviceBalanceReminderMessage($org['name'], $i['customer_name'] ?: 'Customer', $i['invoice_no'], $bal, $org['phone'] ?? null); ?>
            <a href="<?= h(whatsappLink($i['mobile'], $msg)) ?>" target="_blank" rel="noopener" class="text-green-600 hover:underline text-xs">💬 Remind</a>
          <?php endif; ?>
          <a href="<?= $base ?>/service-invoices/view/<?= (int)$i['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">View</a>
          <?php if ($i['status'] !== 'CANCELLED'): ?>
            <a href="<?= $base ?>/service-invoices/edit/<?= (int)$i['id'] ?>" class="text-slate-600 hover:underline text-xs ml-2">Edit</a>
          <?php endif; ?>
          <?php if ($isAdmin): ?>
          <form method="POST" action="<?= $base ?>/service-invoices/delete/<?= (int)$i['id'] ?>" class="inline" onsubmit="return confirm('Delete invoice <?= h($i['invoice_no']) ?> permanently?\n\nStock is put back. This cannot be undone.');">
            <?= CsrfHelper::getField() ?>
            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($invoices)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No invoices yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?= renderPagination($page, $totalPages, $base . '/service-invoices') ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
