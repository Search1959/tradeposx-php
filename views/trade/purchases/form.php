<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<h1 class="text-xl font-semibold text-slate-900 mb-4">New Purchase</h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div x-data="purchaseForm()" x-cloak>
  <form method="POST" @submit="onSubmit" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-4">
    <?= CsrfHelper::getField() ?>
    <input type="hidden" name="items_json" :value="JSON.stringify(lines)">

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Supplier</label>
        <select name="supplier_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="">—</option>
          <?php foreach ($suppliers as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Number</label>
        <input type="text" name="invoice_number" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Purchase Date</label>
        <input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
        <select name="payment_method" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="CASH">Cash</option><option value="UPI">UPI</option><option value="BANK">Bank</option><option value="OTHER">Other</option>
        </select>
      </div>
    </div>

    <div class="border border-slate-200 rounded-lg overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
          <tr>
            <th class="text-left px-3 py-2">Product</th>
            <th class="text-right px-3 py-2 w-24">Qty</th>
            <th class="text-right px-3 py-2 w-28">Rate</th>
            <th class="text-right px-3 py-2 w-20">GST%</th>
            <th class="text-left px-3 py-2 w-28">Batch</th>
            <th class="text-left px-3 py-2 w-32">Expiry</th>
            <th class="text-right px-3 py-2 w-24">Total</th>
            <th class="w-8"></th>
          </tr>
        </thead>
        <tbody>
          <template x-for="(line, idx) in lines" :key="idx">
            <tr class="border-t border-slate-100">
              <td class="px-3 py-1.5">
                <select x-model.number="line.product_id" @change="onProductChange(line)" class="w-full rounded border border-slate-300 px-2 py-1 text-sm">
                  <option value="0">Select product…</option>
                  <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" data-price="<?= (float)$p['purchase_price'] ?>" data-gst="<?= (float)$p['gst_percent'] ?>"><?= h($p['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="px-3 py-1.5"><input type="number" step="0.001" x-model.number="line.quantity" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
              <td class="px-3 py-1.5"><input type="number" step="0.01" x-model.number="line.purchase_price" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
              <td class="px-3 py-1.5"><input type="number" step="0.01" x-model.number="line.gst_percent" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
              <td class="px-3 py-1.5"><input type="text" x-model="line.batch_number" class="w-full rounded border border-slate-300 px-2 py-1 text-sm"></td>
              <td class="px-3 py-1.5"><input type="date" x-model="line.expiry_date" class="w-full rounded border border-slate-300 px-2 py-1 text-sm"></td>
              <td class="px-3 py-1.5 text-right font-medium" x-text="'₹' + lineTotal(line).toFixed(2)"></td>
              <td class="px-3 py-1.5 text-center"><button type="button" @click="lines.splice(idx,1)" class="text-red-500">✕</button></td>
            </tr>
          </template>
        </tbody>
      </table>
      <button type="button" @click="addLine()" class="w-full text-sm text-indigo-600 py-2 hover:bg-indigo-50 border-t border-slate-100">+ Add line</button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Discount Amount</label>
        <input type="number" step="0.01" name="discount_amount" x-model.number="discount" value="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Freight Charges</label>
        <input type="number" step="0.01" name="freight_charges" x-model.number="freight" value="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Grand Total</label>
        <div class="px-3 py-2 text-sm font-semibold bg-slate-50 rounded border border-slate-200" x-text="'₹' + grandTotal().toFixed(2)"></div>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Paid Now</label>
        <input type="number" step="0.01" name="paid_amount" x-model.number="paidAmount" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <div class="text-xs text-amber-600 mt-1" x-show="grandTotal() - (paidAmount||0) > 0">
          Balance to supplier: ₹<span x-text="(grandTotal() - (paidAmount||0)).toFixed(2)"></span>
        </div>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
      <textarea name="notes" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>
    </div>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save Purchase</button>
      <a href="<?= $base ?>/purchases" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
    </div>
  </form>
</div>

<script>
function purchaseForm() {
  return {
    lines: [{ product_id: 0, quantity: 1, purchase_price: 0, gst_percent: 0, batch_number: '', expiry_date: '' }],
    discount: 0,
    freight: 0,
    paidAmount: 0,
    addLine() { this.lines.push({ product_id: 0, quantity: 1, purchase_price: 0, gst_percent: 0, batch_number: '', expiry_date: '' }); },
    onProductChange(line) {
      const select = event.target;
      const opt = select.options[select.selectedIndex];
      line.purchase_price = parseFloat(opt.dataset.price || 0);
      line.gst_percent = parseFloat(opt.dataset.gst || 0);
    },
    lineTotal(line) { return (line.quantity||0) * (line.purchase_price||0) * (1 + (line.gst_percent||0)/100); },
    subtotal() { return this.lines.reduce((s,l) => s + (l.quantity||0) * (l.purchase_price||0), 0); },
    taxTotal() { return this.lines.reduce((s,l) => s + (l.quantity||0) * (l.purchase_price||0) * (l.gst_percent||0)/100, 0); },
    grandTotal() { return Math.max(0, this.subtotal() + this.taxTotal() + (this.freight||0) - (this.discount||0)); },
    onSubmit(e) {
      const valid = this.lines.filter(l => l.product_id > 0 && l.quantity > 0);
      if (valid.length === 0) { e.preventDefault(); alert('Add at least one product line.'); return; }
      if (!this.paidAmount && this.paidAmount !== 0) this.paidAmount = this.grandTotal();
    },
  };
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
