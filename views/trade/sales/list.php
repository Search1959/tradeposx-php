<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Invoices</h1>
  <a href="<?= $base ?>/sales/newSale" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Sale</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm sticky-actions">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Sale #</th>
        <th class="text-left px-4 py-2">Date</th>
        <th class="text-left px-4 py-2">Customer</th>
        <th class="text-right px-4 py-2">Amount</th>
        <th class="text-center px-4 py-2">Status</th>
        <th class="text-right px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($sales as $s): ?>
      <tr class="hover:bg-slate-50 cursor-pointer" onclick="location.href='<?= $base ?>/sales/receipt/<?= (int)$s['id'] ?>'">
        <td class="px-4 py-2 font-medium text-slate-900 whitespace-nowrap"><?= h($s['sale_number']) ?></td>
        <td class="px-4 py-2 text-slate-500"><?= formatDateTime($s['created_at']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($s['customer_name'] ?? 'Walk-in') ?></td>
        <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$s['grand_total']) ?></td>
        <td class="px-4 py-2 text-center">
          <?php if ($s['status'] === 'CANCELLED'): ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">Cancelled</span>
          <?php elseif ($s['payment_status'] === 'PAID'): ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Paid</span>
          <?php elseif ($s['payment_status'] === 'PARTIAL'): ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Partial</span>
          <?php else: ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Udhaar</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-2 text-right whitespace-nowrap" onclick="event.stopPropagation()">
          <a href="<?= $base ?>/sales/receipt/<?= (int)$s['id'] ?>" class="text-indigo-600 hover:underline text-xs">View</a>
          <?php if ($s['status'] !== 'CANCELLED'): ?>
            <a href="<?= $base ?>/sales/edit/<?= (int)$s['id'] ?>" class="text-slate-600 hover:underline text-xs ml-2">Edit</a>
            <?php if ($isAdmin): ?>
            <form method="POST" action="<?= $base ?>/sales/void/<?= (int)$s['id'] ?>" class="inline" onsubmit="return confirm('Void this sale? Stock and Udhaar will be reversed.') && (this.reason.value = prompt('Reason for voiding?') || 'No reason given');">
              <?= CsrfHelper::getField() ?><input type="hidden" name="reason" value="">
              <button class="text-red-600 hover:underline text-xs ml-2">Void</button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($sales)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No invoices yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?= renderPagination($page, $totalPages, $base . '/sales/list') ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
