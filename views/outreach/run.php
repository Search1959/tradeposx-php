<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php';
$dueCount = count(array_filter($rows, fn($r) => $r['due']));
?>

<div class="flex items-center justify-between mb-1">
  <h1 class="text-xl font-semibold text-slate-900"><?= h($campaign['name']) ?></h1>
  <a href="<?= $base ?>/outreach" class="text-sm text-slate-500">&larr; Back to Campaigns</a>
</div>
<p class="text-sm text-slate-500 mb-4">
  <?= $dueCount ?> of <?= count($rows) ?> customer<?= count($rows) === 1 ? '' : 's' ?> due for this message.
  Tapping "Send" opens WhatsApp with the message ready — you still hit send there yourself.
</p>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Customer</th>
        <th class="text-left px-4 py-2">Mobile</th>
        <th class="text-left px-4 py-2">Last Sent</th>
        <th class="text-center px-4 py-2">Status</th>
        <th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($r['customer']['name']) ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($r['customer']['mobile']) ?></td>
        <td class="px-4 py-2 text-slate-500">
          <?= $r['last_sent'] ? formatDateTime($r['last_sent']) . ' (' . $r['days_since'] . 'd ago)' : 'Never' ?>
        </td>
        <td class="px-4 py-2 text-center">
          <?php if ($r['due']): ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Due</span>
          <?php else: ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Sent recently</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-2 text-right">
          <a href="<?= $base ?>/outreach/send/<?= (int)$campaign['id'] ?>?customer=<?= (int)$r['customer']['id'] ?>" target="_blank" rel="noopener"
             class="inline-block bg-green-600 text-white text-xs font-medium px-3 py-1.5 rounded hover:bg-green-700">💬 Send</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No customers with a mobile number on file for this audience yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
