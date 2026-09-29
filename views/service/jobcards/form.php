<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4">New Job Card</h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div x-data="jobCardForm()" x-cloak>
  <form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-2xl space-y-4">
    <?= CsrfHelper::getField() ?>
    <input type="hidden" name="parts_json" :value="JSON.stringify(parts)">

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
        <select name="customer_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="">— Walk-in —</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?> · <?= h($c['mobile']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Assigned Staff</label>
        <select name="assigned_staff_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="">—</option>
          <?php foreach ($staff as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Device / Vehicle Info</label>
        <input type="text" name="device_or_vehicle_info" placeholder="e.g. iPhone 13, MH-12-AB-1234" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
        <select name="priority" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          <?php foreach (['Low','Medium','High','Urgent'] as $p): ?><option value="<?= $p ?>" <?= $p==='Medium'?'selected':'' ?>><?= $p ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Issue Description *</label>
      <textarea name="issue_description" required rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Existing Warranty (as told by customer)</label>
        <select name="warranty_status" x-model="warrantyStatus" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="None">None</option>
          <option value="Manufacturer">Manufacturer Warranty</option>
          <option value="Extended">Extended Warranty</option>
        </select>
      </div>
      <div x-show="warrantyStatus !== 'None'">
        <label class="block text-sm font-medium text-slate-700 mb-1">Warranty Expiry (if known)</label>
        <input type="date" name="warranty_expiry_date" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
    </div>

    <div class="border border-slate-200 rounded-lg overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs uppercase"><tr><th class="text-left px-3 py-2">Part / Material</th><th class="text-right px-3 py-2 w-20">Qty</th><th class="text-right px-3 py-2 w-28">Unit Price</th><th class="text-right px-3 py-2 w-24">Total</th><th class="w-8"></th></tr></thead>
        <tbody>
          <template x-for="(p, idx) in parts" :key="idx">
            <tr class="border-t border-slate-100">
              <td class="px-3 py-1.5"><input type="text" x-model="p.name" class="w-full rounded border border-slate-300 px-2 py-1 text-sm"></td>
              <td class="px-3 py-1.5"><input type="number" step="0.01" x-model.number="p.quantity" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
              <td class="px-3 py-1.5"><input type="number" step="0.01" x-model.number="p.unit_price" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
              <td class="px-3 py-1.5 text-right font-medium" x-text="'₹' + ((p.quantity||0)*(p.unit_price||0)).toFixed(2)"></td>
              <td class="px-3 py-1.5 text-center"><button type="button" @click="parts.splice(idx,1)" class="text-red-500">✕</button></td>
            </tr>
          </template>
        </tbody>
      </table>
      <button type="button" @click="parts.push({name:'',quantity:1,unit_price:0})" class="w-full text-sm text-indigo-600 py-2 hover:bg-indigo-50 border-t border-slate-100">+ Add part/material</button>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Labour Charges</label>
        <input type="number" step="0.01" name="labour_charges" x-model.number="labour" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Estimated Completion</label>
        <input type="date" name="estimated_completion_date" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
    </div>
    <div class="text-right text-sm font-semibold">Total: ₹<span x-text="total().toFixed(2)"></span></div>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save Job Card</button>
      <a href="<?= $base ?>/job-cards" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
    </div>
  </form>
</div>

<script>
function jobCardForm() {
  return {
    parts: [],
    labour: 0,
    warrantyStatus: 'None',
    partsTotal() { return this.parts.reduce((s,p) => s + (p.quantity||0)*(p.unit_price||0), 0); },
    total() { return this.partsTotal() + (this.labour||0); },
  };
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
