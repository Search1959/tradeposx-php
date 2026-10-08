<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Purchases</h1>
  <div class="flex gap-2">
    <a href="<?= $base ?>/purchases/scan" class="bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50">📷 Scan Bill (AI)</a>
    <a href="<?= $base ?>/purchases/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Purchase</a>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm sticky-actions">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Purchase #</th>
        <th class="text-left px-4 py-2">Supplier</th>
        <th class="text-left px-4 py-2">Invoice #</th>
        <th class="text-right px-4 py-2">Amount</th>
        <th class="text-center px-4 py-2">Status</th>
        <th class="text-right px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($purchases as $p): ?>
      <tr class="hover:bg-slate-50 cursor-pointer" onclick="location.href='<?= $base ?>/purchases/view/<?= (int)$p['id'] ?>'">
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($p['purchase_number']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($p['supplier_name'] ?? '—') ?></td>
        <td class="px-4 py-2 text-slate-500"><?= h($p['invoice_number'] ?? '') ?></td>
        <td class="px-4 py-2 text-right font-medium"><?= formatCurrency((float)$p['grand_total']) ?></td>
        <td class="px-4 py-2 text-center">
          <?php if ($p['status'] === 'CANCELLED'): ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">Cancelled</span>
          <?php else: ?>
            <?php $badge = ['PAID'=>'bg-green-100 text-green-700','PARTIAL'=>'bg-amber-100 text-amber-700','PENDING'=>'bg-slate-100 text-slate-600']; ?>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $badge[$p['payment_status']] ?? '' ?>"><?= h($p['payment_status']) ?></span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-2 text-right whitespace-nowrap" onclick="event.stopPropagation()">
          <a href="<?= $base ?>/purchases/view/<?= (int)$p['id'] ?>" class="text-indigo-600 hover:underline text-xs">View</a>
          <?php if ($p['status'] !== 'CANCELLED'): ?>
            <a href="<?= $base ?>/purchases/edit/<?= (int)$p['id'] ?>" class="text-slate-600 hover:underline text-xs ml-2">Edit</a>
            <?php if ($isAdmin): ?>
            <form method="POST" action="<?= $base ?>/purchases/cancel/<?= (int)$p['id'] ?>" class="inline" onsubmit="return confirm('Cancel this purchase? Stock and supplier balance will be reversed.') && (this.reason.value = prompt('Reason for cancelling?') || 'No reason given');">
              <?= CsrfHelper::getField() ?><input type="hidden" name="reason" value="">
              <button class="text-red-600 hover:underline text-xs ml-2">Cancel</button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($purchases)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No purchases yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?= renderPagination($page, $totalPages, $base . '/purchases') ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
