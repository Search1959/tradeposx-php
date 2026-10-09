<?php
/** "Export" dropdown for invoice lists. Expects $exportUrl (GET endpoint). Plain <details>, no JS needed. */
$today = date('Y-m-d');
?>
<details class="relative">
  <summary class="list-none cursor-pointer bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded hover:bg-slate-50 select-none">⬇ Export</summary>
  <form method="GET" action="<?= h($exportUrl) ?>" class="absolute right-0 mt-2 w-64 z-30 bg-white rounded-lg shadow-lg border border-slate-200 p-4 space-y-3 text-sm">
    <div class="font-semibold text-slate-900">Export to Excel (CSV)</div>
    <label class="block text-xs text-slate-600">From
      <input type="date" name="from" max="<?= $today ?>" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></label>
    <label class="block text-xs text-slate-600">To
      <input type="date" name="to" max="<?= $today ?>" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></label>
    <p class="text-[11px] text-slate-400">Leave both empty to export every invoice. Cancelled invoices are included and marked.</p>
    <button class="w-full bg-indigo-600 text-white text-sm font-semibold rounded px-3 py-2 hover:bg-indigo-700">Download</button>
  </form>
</details>
