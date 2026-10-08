<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h1 class="text-xl font-semibold text-slate-900"><?= h($customer['name']) ?></h1>
  <div class="flex items-center gap-2">
    <a href="<?= $base ?>/service-customers" class="text-sm text-slate-500">&larr; Customers</a>
    <a href="<?= $base ?>/service-customers/edit/<?= (int)$customer['id'] ?>" class="bg-white border border-slate-300 text-slate-700 text-sm px-3 py-1.5 rounded hover:bg-slate-50">Edit</a>
    <a href="<?= $base ?>/service-invoices/new" class="bg-emerald-600 text-white text-sm px-3 py-1.5 rounded hover:bg-emerald-700">+ New Bill</a>
  </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
  <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-xs text-slate-500">Total spent</div><div class="text-xl font-bold text-slate-900 mt-1"><?= formatCurrency((float)$customer['total_spent']) ?></div></div>
  <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-xs text-slate-500">Dues outstanding</div><div class="text-xl font-bold mt-1 <?= $due > 0 ? 'text-amber-600' : 'text-slate-900' ?>"><?= formatCurrency($due) ?></div></div>
  <div class="bg-white rounded-xl border border-slate-200 p-4 col-span-2 md:col-span-1"><div class="text-xs text-slate-500">Visits (bills + jobs + bookings)</div><div class="text-xl font-bold text-slate-900 mt-1"><?= count($history['invoices']) + count($history['jobs']) + count($history['appointments']) ?></div></div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4 text-sm grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
  <?php if (!empty($customer['mobile'])): ?>
    <div><span class="text-slate-500">Mobile:</span> <span class="text-slate-900"><?= h($customer['mobile']) ?></span>
      <a href="<?= h(whatsappLink($customer['mobile'], 'Hello ' . $customer['name'] . ',')) ?>" target="_blank" rel="noopener" class="text-green-600 hover:underline text-xs ml-2">💬 WhatsApp</a></div>
  <?php endif; ?>
  <?php $rows = ['Email' => $customer['email'], 'Address' => $customer['address'], 'Sector' => $customer['sector'], 'AMC' => trim(($customer['amc_status'] ?? '') . ($customer['amc_expiry_date'] ? ' · till ' . formatDate($customer['amc_expiry_date']) : '')), 'Notes' => $customer['notes']];
  foreach ($rows as $label => $val): if (trim((string)$val) === '') continue; ?>
    <div><span class="text-slate-500"><?= h($label) ?>:</span> <span class="text-slate-900"><?= h($val) ?></span></div>
  <?php endforeach; ?>
</div>

<?php
$section = function (string $title, array $head, array $rows) { ?>
  <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto mb-4">
    <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900"><?= h($title) ?></div>
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-xs uppercase"><tr><?php foreach ($head as $h): ?><th class="text-left px-4 py-2"><?= h($h) ?></th><?php endforeach; ?></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($rows as $r): ?><tr><?php foreach ($r as $cell): ?><td class="px-4 py-2 text-slate-700"><?= $cell ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="<?= count($head) ?>" class="px-4 py-6 text-center text-slate-400">Nothing yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php };
$section('Invoices', ['Invoice #', 'Date', 'Amount', 'Balance', 'Status', ''], array_map(fn($i) => [
    h($i['invoice_no']), formatDate($i['invoice_date']), formatCurrency((float)$i['grand_total']), formatCurrency((float)$i['balance_amount']), h($i['status']),
    '<a href="' . $base . '/service-invoices/view/' . (int)$i['id'] . '" class="text-indigo-600 hover:underline text-xs">View</a>'], $history['invoices']));
$section('Job cards', ['Job #', 'Issue', 'Amount', 'Balance', 'Status', ''], array_map(fn($j) => [
    h($j['job_no']), h(mb_strimwidth((string)$j['issue_description'], 0, 40, '…')), formatCurrency((float)$j['total_amount']), formatCurrency((float)$j['balance_amount']), h($j['status']),
    '<a href="' . $base . '/job-cards/view/' . (int)$j['id'] . '" class="text-indigo-600 hover:underline text-xs">View</a>'], $history['jobs']));
$section('Appointments', ['Date', 'Time', 'Service', 'Status'], array_map(fn($a) => [
    formatDate($a['appt_date']), h(substr((string)$a['appt_time'], 0, 5)), h($a['service_name'] ?? ''), h($a['status'])], $history['appointments']));
?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
