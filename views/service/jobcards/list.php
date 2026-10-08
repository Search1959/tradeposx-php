<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php';
$statusColors = ['Pending'=>'bg-slate-100 text-slate-600','In-Progress'=>'bg-blue-100 text-blue-700','Completed'=>'bg-green-100 text-green-700','Delivered'=>'bg-emerald-100 text-emerald-700','Cancelled'=>'bg-red-100 text-red-700'];
$priorityColors = ['Low'=>'text-slate-400','Medium'=>'text-slate-600','High'=>'text-amber-600','Urgent'=>'text-red-600 font-semibold'];
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Job Cards</h1>
  <a href="<?= $base ?>/job-cards/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ New Job Card</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm sticky-actions">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr><th class="text-left px-4 py-2">Job #</th><th class="text-left px-4 py-2">Customer</th><th class="text-left px-4 py-2">Issue</th><th class="text-center px-4 py-2">Priority</th><th class="text-center px-4 py-2">Warranty</th><th class="text-right px-4 py-2">Balance Due</th><th class="text-center px-4 py-2">Status</th><th class="px-4 py-2"></th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($jobs as $j): $warrantyActive = $j['warranty_status'] !== 'None' && $j['warranty_expiry_date'] && strtotime($j['warranty_expiry_date']) >= strtotime('today'); $bal = (float)$j['balance_amount']; ?>
      <tr class="hover:bg-slate-50 cursor-pointer" onclick="location.href='<?= $base ?>/job-cards/view/<?= (int)$j['id'] ?>'">
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($j['job_no']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($j['customer_name'] ?? '') ?></td>
        <td class="px-4 py-2 text-slate-600 truncate max-w-xs"><?= h($j['issue_description']) ?></td>
        <td class="px-4 py-2 text-center <?= $priorityColors[$j['priority']] ?? '' ?>"><?= h($j['priority']) ?></td>
        <td class="px-4 py-2 text-center">
          <?php if ($warrantyActive): ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">🛡 Active</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-2 text-right <?= $bal > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400' ?>"><?= formatCurrency($bal) ?></td>
        <td class="px-4 py-2 text-center"><span class="text-xs px-2 py-0.5 rounded-full <?= $statusColors[$j['status']] ?? '' ?>"><?= h($j['status']) ?></span></td>
        <td class="px-4 py-2 text-right whitespace-nowrap" onclick="event.stopPropagation()">
          <?php if ($bal > 0 && !empty($j['mobile'])): ?>
            <?php $msg = serviceBalanceReminderMessage($org['name'], $j['customer_name'] ?: 'Customer', $j['job_no'], $bal, $org['phone'] ?? null); ?>
            <a href="<?= h(whatsappLink($j['mobile'], $msg)) ?>" target="_blank" rel="noopener" class="text-green-600 hover:underline text-xs">💬 Remind</a>
          <?php endif; ?>
          <a href="<?= $base ?>/job-cards/view/<?= (int)$j['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">View</a>
          <a href="<?= $base ?>/job-cards/edit/<?= (int)$j['id'] ?>" class="text-slate-600 hover:underline text-xs ml-2">Edit</a>
          <form method="POST" action="<?= $base ?>/job-cards/delete/<?= (int)$j['id'] ?>" class="inline" onsubmit="return confirm('Delete job card <?= h($j['job_no']) ?>? This cannot be undone.');">
            <?= CsrfHelper::getField() ?><button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($jobs)): ?>
      <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">No job cards yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?= renderPagination($page, $totalPages, $base . '/job-cards') ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
