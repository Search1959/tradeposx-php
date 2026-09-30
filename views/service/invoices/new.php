<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div x-data="invoiceScreen()" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <!-- Mobile-only tab switcher — on lg+ both panels already sit side by side.
       Matches the same split done on the TradeERP POS screen (views/trade/sales/pos.php)
       so adding a service doesn't mean scrolling past the whole grid to check the invoice. -->
  <div class="lg:hidden flex gap-2">
    <button type="button" @click="activeTab = 'services'"
            :class="activeTab === 'services' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
            class="flex-1 text-sm font-medium py-2.5 rounded-lg transition">Services</button>
    <button type="button" @click="activeTab = 'invoice'"
            :class="activeTab === 'invoice' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
            class="flex-1 text-sm font-medium py-2.5 rounded-lg transition">
      Invoice<template x-if="cart.length > 0"><span x-text="' (' + cart.length + ')'"></span></template>
    </button>
  </div>

  <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 p-4"
       :class="activeTab === 'services' ? '' : 'hidden lg:block'">
    <input type="text" x-model="search" placeholder="Search services…" class="w-full rounded border border-slate-300 px-3 py-2 text-sm mb-3">
    <div class="flex flex-wrap gap-1.5 mb-3">
      <button type="button" @click="category = ''"
              :class="category === '' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              class="text-xs font-medium px-2.5 py-1 rounded-full transition">All</button>
      <template x-for="c in categories()" :key="c">
        <button type="button" @click="category = c"
                :class="category === c ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="text-xs font-medium px-2.5 py-1 rounded-full transition" x-text="c"></button>
      </template>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[65vh] overflow-y-auto">
      <template x-for="s in filteredServices()" :key="s.id">
        <button type="button" @click="addToCart(s)" class="text-left border border-slate-200 rounded-lg p-3 hover:border-indigo-400 hover:bg-indigo-50 transition">
          <div class="font-medium text-sm text-slate-900 truncate" x-text="s.name"></div>
          <div class="text-xs text-slate-500 mt-1" x-text="s.category"></div>
          <div class="text-sm font-semibold text-indigo-700 mt-2" x-text="'₹' + Number(s.price).toFixed(2)"></div>
        </button>
      </template>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col"
       :class="activeTab === 'invoice' ? '' : 'hidden lg:flex'">
    <h2 class="font-semibold text-slate-900 mb-2">Invoice</h2>
    <div class="flex-1 overflow-y-auto max-h-[38vh] divide-y divide-slate-100">
      <template x-for="(line, idx) in cart" :key="line.id">
        <div class="py-2">
          <div class="flex items-center gap-2">
            <div class="flex-1 min-w-0 text-sm font-medium text-slate-900 truncate" x-text="line.name"></div>
            <input type="number" step="1" min="1" x-model.number="line.qty" @blur="clampQty(line)" class="w-14 rounded border border-slate-300 px-2 py-1 text-sm text-right">
            <span class="text-sm font-semibold w-20 text-right" x-text="'₹' + (line.qty * line.price).toFixed(2)"></span>
            <button type="button" @click="cart.splice(idx,1)" class="text-red-500 text-xs">✕</button>
          </div>
          <select x-model.number="line.staff_id" class="mt-1 w-full text-xs rounded border border-slate-200 px-2 py-1">
            <option value="0">No staff assigned</option>
            <?php foreach ($staff as $s): ?>
              <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?> (<?= h((string)$s['commission_percent']) ?>% commission)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </template>
      <div x-show="cart.length === 0" class="text-sm text-slate-400 text-center py-8">Tap a service to add it.</div>
    </div>

    <div class="pt-2">
      <label class="block text-xs font-medium text-slate-700 mb-1">GST</label>
      <select x-model="taxType" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
        <option value="EXCLUSIVE">Add GST on top (prices exclude tax)</option>
        <option value="INCLUSIVE">GST included in prices shown</option>
        <option value="EXEMPT">No GST on this invoice</option>
      </select>
    </div>

    <div class="border-t border-slate-200 mt-2 pt-3 space-y-2 text-sm">
      <div class="flex justify-between"><span class="text-slate-500">Taxable Amount</span><span x-text="'₹' + taxableAmount().toFixed(2)"></span></div>
      <template x-if="taxType !== 'EXEMPT'">
        <template x-for="g in taxBreakdown()" :key="g.rate">
          <div>
            <div class="flex justify-between"><span class="text-slate-500" x-text="'CGST @ ' + halfRate(g.rate) + '%'"></span><span x-text="'₹' + (g.tax/2).toFixed(2)"></span></div>
            <div class="flex justify-between"><span class="text-slate-500" x-text="'SGST @ ' + halfRate(g.rate) + '%'"></span><span x-text="'₹' + (g.tax/2).toFixed(2)"></span></div>
          </div>
        </template>
      </template>
      <div class="flex justify-between items-center"><span class="text-slate-500">Labour Charges</span><input type="number" step="0.01" x-model.number="labour" class="w-24 rounded border border-slate-300 px-2 py-1 text-right"></div>
      <div class="flex justify-between items-center"><span class="text-slate-500">Discount</span><input type="number" step="0.01" x-model.number="discount" class="w-24 rounded border border-slate-300 px-2 py-1 text-right"></div>
      <div class="flex justify-between font-semibold text-base pt-1 border-t border-slate-100"><span>Grand Total</span><span x-text="'₹' + grandTotal().toFixed(2)"></span></div>
    </div>

    <form method="POST" class="mt-3 space-y-2" @submit="onSubmit">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="cart_json" :value="JSON.stringify(cart.map(l => ({id: l.id, qty: l.qty, staff_id: l.staff_id || null})))">
      <input type="hidden" name="labour_charges" :value="labour">
      <input type="hidden" name="discount" :value="discount">
      <input type="hidden" name="tax_type" :value="taxType">

      <select name="customer_id" x-model="customerId" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">Walk-in customer</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?> · <?= h($c['mobile']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="grid grid-cols-2 gap-2">
        <select name="payment_method" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="CASH">Cash</option><option value="UPI">UPI</option><option value="BANK">Bank</option><option value="OTHER">Other</option>
        </select>
        <select name="payment_status" x-model="paymentStatus" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="FULL">Paid in full</option>
          <option value="PARTIAL">Part payment</option>
          <option value="CREDIT">Unpaid (bill now, collect later)</option>
        </select>
      </div>
      <div x-show="paymentStatus === 'PARTIAL'">
        <label class="block text-xs font-medium text-slate-700 mb-1">Amount Paid Now</label>
        <input type="number" step="0.01" min="0" name="paid_amount" x-model.number="paidAmount" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <div class="text-xs text-amber-600 mt-1">Balance Due: ₹<span x-text="Math.max(0, grandTotal() - (paidAmount||0)).toFixed(2)"></span></div>
      </div>

      <button type="submit" :disabled="cart.length === 0" class="w-full bg-indigo-600 disabled:bg-slate-300 text-white font-medium py-2.5 rounded hover:bg-indigo-700">Save Invoice</button>
    </form>
  </div>
</div>

<script>
function invoiceScreen() {
  return {
    services: <?= json_encode(array_map(fn($s) => ['id'=>(int)$s['id'],'name'=>$s['name'],'category'=>$s['category'],'price'=>(float)$s['price'],'gst'=>(float)$s['gst_percent']], $services)) ?>,
    search: '', category: '', activeTab: 'services', cart: [], labour: 0, discount: 0, taxType: 'EXCLUSIVE', customerId: '', paymentStatus: 'FULL', paidAmount: 0,
    categories() {
      return [...new Set(this.services.map(s => s.category).filter(c => c))].sort();
    },
    filteredServices() {
      const q = this.search.trim().toLowerCase();
      let list = this.services;
      if (this.category) list = list.filter(s => s.category === this.category);
      if (q) list = list.filter(s => s.name.toLowerCase().includes(q));
      return list.slice(0, 60);
    },
    addToCart(s) {
      const existing = this.cart.find(l => l.id === s.id);
      if (existing) existing.qty += 1;
      else this.cart.push({ id: s.id, name: s.name, price: s.price, gst: s.gst, qty: 1, staff_id: 0 });
    },
    clampQty(line) { if (!line.qty || line.qty < 1) line.qty = 1; },
    lineTotal() { return this.cart.reduce((s,l) => s + l.qty*l.price, 0); },
    taxableAmount() {
      if (this.taxType === 'INCLUSIVE') {
        return this.cart.reduce((s, l) => s + (l.qty * l.price) / (1 + (l.gst || 0) / 100), 0);
      }
      return this.lineTotal();
    },
    taxTotal() {
      if (this.taxType === 'EXEMPT') return 0;
      if (this.taxType === 'INCLUSIVE') return this.lineTotal() - this.taxableAmount();
      return this.cart.reduce((s, l) => s + (l.qty * l.price) * ((l.gst || 0) / 100), 0);
    },
    taxBreakdown() {
      if (this.taxType === 'EXEMPT') return [];
      const groups = {};
      for (const l of this.cart) {
        const rate = l.gst || 0;
        if (rate <= 0) continue;
        const lineAmt = l.qty * l.price;
        const taxable = this.taxType === 'INCLUSIVE' ? lineAmt / (1 + rate / 100) : lineAmt;
        const tax = this.taxType === 'INCLUSIVE' ? (lineAmt - taxable) : (lineAmt * rate / 100);
        if (!groups[rate]) groups[rate] = { rate, taxable: 0, tax: 0 };
        groups[rate].taxable += taxable;
        groups[rate].tax += tax;
      }
      return Object.values(groups).sort((a, b) => a.rate - b.rate);
    },
    halfRate(rate) { return (Math.round(rate * 50) / 100).toString().replace(/\.0$/, ''); },
    grandTotal() { return Math.max(0, this.taxableAmount() + this.taxTotal() + (this.labour||0) - (this.discount||0)); },
    onSubmit(e) {
      if (this.cart.length === 0) { e.preventDefault(); return; }
      this.cart.forEach(line => this.clampQty(line));
      if (this.paymentStatus === 'FULL') this.paidAmount = this.grandTotal();
      if (this.paymentStatus === 'CREDIT') this.paidAmount = 0;
    },
  };
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
