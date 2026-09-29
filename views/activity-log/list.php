<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php';
$actionLabels = [
    'product_create' => 'Product created', 'product_bulk_import' => 'Products bulk imported',
    'service_item_bulk_import' => 'Service items bulk imported', 'customer_create' => 'Customer added',
    'supplier_create' => 'Supplier added', 'team_member_created' => 'Team member added',
    'team_member_updated' => 'Team member updated', 'team_member_status_changed' => 'Team member status changed',
    'team_member_password_reset' => 'Team member password reset', 'sale_voided' => 'Sale voided',
    'customer_payment_recorded' => 'Udhaar payment recorded',
];
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Activity Log</h1>
  <span class="text-xs text-slate-400"><?= (int)$total ?> total entries</span>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">When</th>
        <th class="text-left px-4 py-2">By</th>
        <th class="text-left px-4 py-2">Action</th>
        <th class="text-left px-4 py-2">Details</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($entries as $e): ?>
      <tr>
        <td class="px-4 py-2 text-slate-500 whitespace-nowrap"><?= formatDateTime($e['created_at']) ?></td>
        <td class="px-4 py-2 text-slate-700"><?= h($e['user_name'] ?? 'System') ?></td>
        <td class="px-4 py-2 text-slate-900"><?= h($actionLabels[$e['action']] ?? str_replace('_', ' ', $e['action'])) ?></td>
        <td class="px-4 py-2 text-slate-500"><?= h($e['description'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($entries)): ?>
      <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No activity recorded yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-2 mt-4 text-sm">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="<?= $base ?>/activity-log?page=<?= $p ?>"
       class="px-3 py-1 rounded <?= $p == ($_GET['page'] ?? 1) ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
