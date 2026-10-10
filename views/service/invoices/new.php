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
    <!-- Two ways to build a bill: tap items from the list, or speak/write a cash memo (item · qty · rate). -->
    <div class="flex gap-2 mb-3">
      <button type="button" @click="leftTab = 'stock'"
              :class="leftTab === 'stock' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              class="flex-1 text-sm font-medium py-2 rounded-lg transition">Services &amp; stock</button>
      <button type="button" @click="leftTab = 'memo'"
              :class="leftTab === 'memo' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              class="flex-1 text-sm font-medium py-2 rounded-lg transition">🎤 Cash memo</button>
    </div>
    <div x-show="leftTab === 'stock'">
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
          <div class="flex items-center justify-between gap-1 mt-1">
            <span class="text-xs text-slate-500 truncate" x-text="s.category"></span>
            <span x-show="s.type === 'STOCK'" class="text-[11px] px-1.5 py-0.5 rounded whitespace-nowrap"
                  :class="s.stock > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'"
                  x-text="s.stock > 0 ? s.stock + ' in stock' : 'Out of stock'"></span>
          </div>
          <div class="flex items-center justify-between mt-2">
            <span class="text-sm font-semibold text-indigo-700" x-text="'₹' + Number(s.price).toFixed(2)"></span>
            <!-- GST% shown right on the card — so a 0% item (often just a blank
                 CSV cell at import time) is obvious before it ever reaches the
                 invoice, not discovered later when no tax shows up on it. -->
            <span class="text-[11px] px-1.5 py-0.5 rounded" :class="s.gst > 0 ? 'text-slate-400' : 'text-amber-700 bg-amber-50'" x-text="'GST ' + s.gst + '%'"></span>
          </div>
        </button>
      </template>
    </div>
    </div><!-- /stock -->

    <!-- ── Cash memo (shared with TradeERP) ── -->
    <?php require __DIR__ . '/../../shared/cash-memo-panel.php'; ?>
    <?php require __DIR__ . '/../../shared/upi-modal.php'; ?>
  </div>

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col"
       :class="activeTab === 'invoice' ? '' : 'hidden lg:flex'">
    <h2 class="font-semibold text-slate-900 mb-2">Invoice</h2>
    <div class="flex-1 overflow-y-auto max-h-[38vh] divide-y divide-slate-100">
      <template x-for="(line, idx) in cart" :key="line.id">
        <div class="py-2">
          <div class="flex items-center gap-2">
            <div class="flex-1 min-w-0 text-sm font-medium text-slate-900 truncate" x-text="line.name"></div>
            <input type="number" step="0.001" min="0.001" x-model.number="line.qty" @blur="clampQty(line)" class="w-16 rounded border border-slate-300 px-2 py-1 text-sm text-right">
            <span class="text-sm font-semibold w-20 text-right" x-text="'₹' + (line.qty * line.price).toFixed(2)"></span>
            <button type="button" @click="cart.splice(idx,1)" class="text-red-500 text-xs">✕</button>
          </div>
          <!-- Editable per-unit price — for when a customer bargains. Only
               ever changes what this one invoice charges; the service item's
               own price is never touched. -->
          <div class="flex items-center gap-1 text-xs text-slate-500 mt-0.5">
            <span>₹</span>
            <input type="number" step="0.01" min="0" x-model.number="line.price" @blur="clampPrice(line)"
                   class="w-16 rounded border border-slate-300 px-1 py-0.5 text-xs">
            <span>each</span>
            <span x-show="line.price !== line.originalPrice" class="line-through text-slate-400" x-text="'₹' + Number(line.originalPrice).toFixed(2)"></span>
            <!-- Per-line GST% — the CGST/SGST breakdown below only shows rates
                 that are actually present in the cart, so a 0%-GST item (and
                 the resulting "no tax line at all") would otherwise look
                 indistinguishable from a bug. -->
            <span class="ml-auto" :class="line.gst > 0 ? 'text-slate-400' : 'text-amber-700 font-medium'" x-text="'GST ' + (line.gst || 0) + '%'"></span>
          </div>
          <div class="flex items-center gap-1.5 flex-wrap mt-1" x-show="line.custom || (line.stock !== null && line.stock !== undefined)">
            <span x-show="line.custom" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">Free item</span>
            <span x-show="line.custom && line.keep" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">Saved to stock</span>
            <span x-show="line.stock !== null && line.stock !== undefined && !line.custom" class="text-[10px] px-1.5 py-0.5 rounded"
                  :class="line.qty > line.stock ? 'bg-amber-50 text-amber-700 font-semibold' : 'bg-slate-100 text-slate-500'"
                  x-text="line.qty > line.stock ? 'Only ' + Math.max(0, line.stock) + ' in stock' : line.stock + ' in stock'"></span>
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
      <div class="flex justify-between items-center gap-2">
        <span class="text-slate-500 shrink-0">Discount</span>
        <div class="flex items-center gap-1.5">
          <template x-if="discountMode !== 'custom'">
            <span class="text-xs text-slate-400" x-text="discountAmount() > 0 ? '−₹' + discountAmount().toFixed(2) : ''"></span>
          </template>
          <input type="number" step="0.01" min="0" x-show="discountMode === 'custom'" x-model.number="customDiscount" @blur="if (!customDiscount || customDiscount < 0) customDiscount = 0"
                 class="w-20 rounded border border-slate-300 px-2 py-1 text-sm text-right">
          <select x-model="discountMode" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <option value="0">None</option>
            <option value="5">5%</option>
            <option value="10">10%</option>
            <option value="custom">Custom</option>
          </select>
        </div>
      </div>
      <div class="flex justify-between font-semibold text-base pt-1 border-t border-slate-100"><span>Grand Total</span><span x-text="'₹' + grandTotal().toFixed(2)"></span></div>
    </div>

    <form method="POST" class="mt-3 space-y-2" x-ref="checkoutForm" @submit="onSubmit">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="cart_json" :value="JSON.stringify(cart.map(l => ({id: l.custom ? null : l.id, custom: !!l.custom, name: l.custom ? l.name : undefined, qty: l.qty, staff_id: l.staff_id || null, price: l.price, gst: l.gst, keep: !!l.keep, stock: l.custom ? l.stock : undefined})))">
      <input type="hidden" name="labour_charges" :value="labour">
      <input type="hidden" name="discount" :value="discountAmount()">
      <input type="hidden" name="discount_percent" :value="['5','10'].includes(discountMode) ? discountMode : ''">
      <input type="hidden" name="tax_type" :value="taxType">
      <input type="hidden" name="specialized_staff_commission_percent" :value="specializedStaffCommissionPercent()">

      <select name="customer_id" x-model="customerId" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">Walk-in customer</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?> · <?= h($c['mobile']) ?></option>
        <?php endforeach; ?>
      </select>

      <div x-show="!customerId">
        <input type="tel" name="walkin_mobile" x-model="walkinMobile" placeholder="Walk-in customer's WhatsApp no. (optional)"
               class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <p class="text-xs text-slate-400 mt-1">Saved as a customer so they show up under Customer Outreach for review/promo messages.</p>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <select name="specialized_staff_id" x-model="specializedStaffId" @change="specializedStaffCommissionMode = 'default'" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="">No specialized staff</option>
          <?php foreach ($specializedStaff as $sp): ?>
            <option value="<?= (int)$sp['id'] ?>"><?= h($sp['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="flex items-center gap-1.5" x-show="specializedStaffId">
          <input type="number" step="0.01" min="0" x-show="specializedStaffCommissionMode === 'custom'" x-model.number="specializedStaffCustomCommission"
                 @blur="if (!specializedStaffCustomCommission || specializedStaffCustomCommission < 0) specializedStaffCustomCommission = 0"
                 class="w-14 rounded border border-slate-300 px-2 py-1 text-sm text-right">
          <select x-model="specializedStaffCommissionMode" class="flex-1 rounded border border-slate-300 px-2 py-1 text-sm">
            <option value="default" x-text="specializedStaffDefaultCommissionPercent() + '%'"></option>
            <option value="custom">Custom</option>
          </select>
        </div>
      </div>
      <!-- Recorded on the invoice for payout tracking only — never subtracted
           from the customer's total, unlike Discount above. -->
      <div class="text-xs text-slate-400 text-right" x-show="specializedStaffId" x-text="'Staff earns ₹' + specializedStaffCommissionAmount().toFixed(2) + ' (not deducted from total)'"></div>

      <div class="grid grid-cols-2 gap-2">
        <select name="payment_method" x-model="paymentMethod" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="CASH">Cash</option><option value="UPI">UPI</option><option value="BANK">Bank</option><option value="OTHER">Other</option>
        </select>
        <select name="payment_status" x-model="paymentStatus" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="FULL">Paid in full</option>
          <option value="PARTIAL">Part payment</option>
          <option value="CREDIT">Unpaid (bill now, collect later)</option>
        </select>
      </div>
      <div x-show="paymentMethod === 'UPI' && !upiId" x-cloak class="text-xs text-amber-600">Add your UPI ID in <a href="<?= $base ?>/settings" class="underline">Settings</a> to show a QR code here.</div>
      <div x-show="paymentStatus === 'PARTIAL'">
        <label class="block text-xs font-medium text-slate-700 mb-1">Amount Paid Now</label>
        <input type="number" step="0.01" min="0" name="paid_amount" x-model.number="paidAmount" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <div class="text-xs text-amber-600 mt-1">Balance Due: ₹<span x-text="Math.max(0, grandTotal() - (paidAmount||0)).toFixed(2)"></span></div>
      </div>

      <button type="submit" :disabled="cart.length === 0" class="w-full bg-indigo-600 disabled:bg-slate-300 text-white font-medium py-2.5 rounded hover:bg-indigo-700">Save Invoice</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../shared/cash-memo-script.php'; ?>
<?php require __DIR__ . '/../../shared/upi-script.php'; ?>
<script>
function invoiceScreen() {
  return {
    ...cashMemoMixin(),
    ...upiMixin(<?= json_encode((new Organization())->find((int)$org['id'])['upi_id'] ?? '') ?>, <?= json_encode($org['name']) ?>),
    services: <?= json_encode(array_map(fn($s) => ['id'=>(int)$s['id'],'name'=>$s['name'],'category'=>$s['category'],'price'=>(float)$s['price'],'gst'=>(float)$s['gst_percent'],'type'=>($s['item_type'] ?? 'SERVICE'),'stock'=>isset($s['stock_qty']) ? (float)$s['stock_qty'] : null], $services)) ?>,
    specializedStaff: <?= json_encode(array_map(fn($sp) => ['id'=>(int)$sp['id'],'name'=>$sp['name'],'default_commission_percent'=>(float)$sp['default_commission_percent']], $specializedStaff)) ?>,
    search: '', category: '', activeTab: 'services', cart: [], customSeq: 0, labour: 0, discountMode: '0', customDiscount: 0, taxType: 'EXCLUSIVE', customerId: '', walkinMobile: '', paymentStatus: 'FULL', paymentMethod: 'CASH', paidAmount: 0,
    specializedStaffId: '', specializedStaffCommissionMode: 'default', specializedStaffCustomCommission: 0,
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
      else this.cart.push({ id: s.id, name: s.name, price: s.price, originalPrice: s.price, gst: s.gst, qty: 1, staff_id: 0, stock: s.type === 'STOCK' ? s.stock : null });
    },
    init() { this.memoInit(); },
    // ── hooks used by the shared cash memo (views/shared/cash-memo-script.php) ──
    // Stock items carry their count; plain services are not tracked (null).
    memoItems() { return this.services.map(s => ({ id: s.id, name: s.name, price: s.price, gst: s.gst, stock: s.type === 'STOCK' ? s.stock : null })); },
    memoPushCatalog(p, qty, rate) {
      const existing = this.cart.find(l => l.id === p.id);
      if (existing) { existing.qty += qty; existing.price = rate; }
      else this.cart.push({ id: p.id, name: p.name, price: rate, originalPrice: p.price, gst: p.gst, qty, staff_id: 0, stock: p.stock });
    },
    memoPushFree(name, qty, rate, gst) { this.cart.push({ id: -(++this.customSeq), custom: true, name, price: rate, originalPrice: rate, gst, qty, staff_id: 0, stock: null }); },
    memoDone() { this.activeTab = 'invoice'; },
    clampQty(line) { if (!line.qty || line.qty < 0.001) line.qty = 1; },
    // Only clamps a negative/blank price back to 0 on blur (not on every
    // keystroke) — ₹0 itself is left alone since giving a service away is a
    // legitimate bargain outcome.
    clampPrice(line) { if (!line.price || line.price < 0) line.price = 0; },
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
    discountAmount() {
      const base = this.taxableAmount();
      if (this.discountMode === '5') return +(base * 0.05).toFixed(2);
      if (this.discountMode === '10') return +(base * 0.10).toFixed(2);
      if (this.discountMode === 'custom') return +(this.customDiscount || 0);
      return 0;
    },
    grandTotal() { return Math.max(0, this.taxableAmount() + this.taxTotal() + (this.labour||0) - this.discountAmount()); },
    specializedStaffDefaultCommissionPercent() {
      const s = this.specializedStaff.find(x => x.id === Number(this.specializedStaffId));
      return s ? s.default_commission_percent : 0;
    },
    specializedStaffCommissionPercent() {
      if (!this.specializedStaffId) return 0;
      return this.specializedStaffCommissionMode === 'custom' ? +(this.specializedStaffCustomCommission || 0) : this.specializedStaffDefaultCommissionPercent();
    },
    specializedStaffCommissionAmount() { return +(this.taxableAmount() * this.specializedStaffCommissionPercent() / 100).toFixed(2); },
    onSubmit(e) {
      if (this.cart.length === 0) { e.preventDefault(); return; }
      this.cart.forEach(line => { this.clampQty(line); this.clampPrice(line); });
      if (this.paymentStatus === 'FULL') this.paidAmount = this.grandTotal();
      if (this.paymentStatus === 'CREDIT') this.paidAmount = 0;
      if (this.upiNeeded(this.paidAmount)) { e.preventDefault(); this.upiShow(this.paidAmount); }   // QR window; the invoice is created once "Payment received" is tapped
    },
  };
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
