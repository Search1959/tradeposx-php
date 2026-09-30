<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php';
$typeLabels = ['REVIEW' => ['⭐ Review', 'bg-amber-100 text-amber-700'], 'PROMOTION' => ['📣 Promotion', 'bg-indigo-100 text-indigo-700'], 'CUSTOM' => ['✏️ Custom', 'bg-slate-100 text-slate-600']];
$recurrenceLabels = ['ONE_TIME' => 'One-time', 'WEEKLY' => 'Weekly', 'EVERY_15_DAYS' => 'Every 15 days', 'MONTHLY' => 'Monthly'];
$audienceLabels = ['TRADE' => 'Trade Customers', 'SERVICE' => 'Service Customers'];
?>

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-xl font-semibold text-slate-900">Customer Outreach</h1>
    <p class="text-sm text-slate-500 mt-0.5">Review requests &amp; promotions over WhatsApp — sent as a click-to-chat link, same as your Udhaar reminders.</p>
  </div>
  <?php if (in_array($user['role'] ?? '', ['owner', 'admin'], true)): ?>
    <a href="<?= $base ?>/outreach/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700 whitespace-nowrap">+ New Campaign</a>
  <?php endif; ?>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Type</th>
        <th class="text-left px-4 py-2">Audience</th>
        <th class="text-left px-4 py-2">Recurrence</th>
        <th class="text-right px-4 py-2">Sent So Far</th>
        <th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($campaigns as $c): $type = $typeLabels[$c['campaign_type']] ?? ['Custom', 'bg-slate-100 text-slate-600']; ?>
      <tr class="hover:bg-slate-50">
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($c['name']) ?></td>
        <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded-full <?= $type[1] ?>"><?= $type[0] ?></span></td>
        <td class="px-4 py-2 text-slate-600"><?= h($audienceLabels[$c['audience']] ?? $c['audience']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($recurrenceLabels[$c['recurrence']] ?? $c['recurrence']) ?></td>
        <td class="px-4 py-2 text-right text-slate-500"><?= (int)($sentCounts[$c['id']] ?? 0) ?></td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <a href="<?= $base ?>/outreach/run/<?= (int)$c['id'] ?>" class="text-green-600 hover:underline text-xs font-medium">💬 Run</a>
          <?php if (in_array($user['role'] ?? '', ['owner', 'admin'], true)): ?>
            <a href="<?= $base ?>/outreach/edit/<?= (int)$c['id'] ?>" class="text-indigo-600 hover:underline text-xs ml-2">Edit</a>
            <form method="POST" action="<?= $base ?>/outreach/delete/<?= (int)$c['id'] ?>" class="inline" onsubmit="return confirm('Delete this campaign? Its send history will also be deleted.');">
              <?= CsrfHelper::getField() ?>
              <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($campaigns)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No outreach campaigns yet — create one to start sending review requests or promotions.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?= renderPagination($page, $totalPages, $base . '/outreach') ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
