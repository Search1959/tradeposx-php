<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div x-data="posScreen()" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <!-- Mobile-only tab switcher — on lg+ both panels already sit side by side,
       so this whole bar disappears there. Solves having to scroll past the
       whole product grid just to see whether the cart picked something up. -->
  <div class="lg:hidden flex gap-2">
    <button type="button" @click="activeTab = 'products'"
            :class="activeTab === 'products' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
            class="flex-1 text-sm font-medium py-2.5 rounded-lg transition">Products</button>
    <button type="button" @click="activeTab = 'cart'"
            :class="activeTab === 'cart' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
            class="flex-1 text-sm font-medium py-2.5 rounded-lg transition">
      Cart<template x-if="cart.length > 0"><span x-text="' (' + cart.length + ')'"></span></template>
    </button>
  </div>

  <!-- Product picker -->
  <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 p-4"
       :class="activeTab === 'products' ? '' : 'hidden lg:block'">
    <!-- The picker is split in two: what's in stock (tap to add) and a cash-memo pad for writing/dictating
         a whole list of items, quantities and rates, then sending it to the cart in one go. -->
    <div class="flex gap-1 p-1 rounded-lg bg-slate-100 mb-3">
      <button type="button" @click="leftTab = 'stock'" :class="leftTab === 'stock' ? 'bg-white shadow text-indigo-700' : 'text-slate-500'"
              class="flex-1 text-sm font-semibold py-2 rounded-md transition">📦 Stock available</button>
      <button type="button" @click="leftTab = 'memo'" :class="leftTab === 'memo' ? 'bg-white shadow text-indigo-700' : 'text-slate-500'"
              class="flex-1 text-sm font-semibold py-2 rounded-md transition">🧾 Cash memo</button>
    </div>

    <div x-show="leftTab === 'stock'">
    <input type="text" x-model="search" placeholder="Search products by name, SKU, barcode…"
           class="w-full rounded border border-slate-300 px-3 py-2 text-sm mb-3" x-ref="searchBox">
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
      <template x-for="p in filteredProducts()" :key="p.id">
        <button type="button" @click="addToCart(p)"
                class="text-left border border-slate-200 rounded-lg p-3 hover:border-indigo-400 hover:bg-indigo-50 transition">
          <div class="font-medium text-sm text-slate-900 truncate" x-text="p.name"></div>
          <div class="text-xs text-slate-500 mt-1" x-text="p.category"></div>
          <div class="flex items-center justify-between mt-2">
            <span class="text-sm font-semibold text-indigo-700" x-text="'₹' + Number(p.selling_price).toFixed(2)"></span>
            <span class="text-xs" :class="Number(p.current_stock) <= 0 ? 'text-red-500' : 'text-slate-400'"
                  x-text="Number(p.current_stock) + ' ' + p.unit"></span>
          </div>
        </button>
      </template>
    </div>
    </div><!-- /stock -->

    <!-- ── Cash memo (shared with ServiceERP) ── -->
    <?php require __DIR__ . '/../../shared/cash-memo-panel.php'; ?>
  </div>

  <!-- Cart + checkout -->
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col"
       :class="activeTab === 'cart' ? '' : 'hidden lg:flex'">
    <h2 class="font-semibold text-slate-900 mb-2">Cart</h2>
    <div class="flex-1 overflow-y-auto max-h-[38vh] divide-y divide-slate-100">
      <template x-for="(line, idx) in cart" :key="line.id">
        <div class="py-2 flex items-center gap-2">
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-slate-900 truncate"><span x-text="line.name"></span> <span x-show="line.custom" class="text-[10px] font-semibold px-1 rounded bg-indigo-50 text-indigo-700 align-middle">Free item</span></div>
            <!-- Editable per-unit price — for when a customer bargains. Only
                 ever changes what this one sale charges; the product's own
                 selling_price is never touched. -->
            <div class="flex items-center gap-1 text-xs text-slate-500 mt-0.5">
              <span>₹</span>
              <input type="number" step="0.01" min="0" x-model.number="line.price" @blur="clampPrice(line)"
                     class="w-16 rounded border border-slate-300 px-1 py-0.5 text-xs">
              <span>each</span>
              <span x-show="line.price !== line.originalPrice" class="line-through text-slate-400" x-text="'₹' + Number(line.originalPrice).toFixed(2)"></span>
            </div>
          </div>
          <input type="number" step="0.001" min="0.001" x-model.number="line.qty" @blur="clampQty(line)"
                 class="w-16 rounded border border-slate-300 px-2 py-1 text-sm text-right">
          <span class="text-sm font-semibold w-20 text-right" x-text="'₹' + (line.qty * line.price).toFixed(2)"></span>
          <button type="button" @click="removeFromCart(idx)" class="text-red-500 text-xs">✕</button>
        </div>
      </template>
      <div x-show="cart.length === 0" class="text-sm text-slate-400 text-center py-8">Cart is empty — tap a product to add it.</div>
    </div>

    <div class="pt-2">
      <label class="block text-xs font-medium text-slate-700 mb-1">GST</label>
      <select x-model="taxType" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
        <option value="EXCLUSIVE">Add GST on top (prices exclude tax)</option>
        <option value="INCLUSIVE">GST included in prices shown</option>
        <option value="EXEMPT">No GST on this sale</option>
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
      <div class="flex justify-between font-semibold text-base pt-1 border-t border-slate-100">
        <span>Grand Total</span><span x-text="'₹' + grandTotal().toFixed(2)"></span>
      </div>
    </div>

    <form method="POST" class="mt-3 space-y-2" @submit="onSubmit">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="cart_json" :value="JSON.stringify(cart.map(l => ({id: l.custom ? null : l.id, custom: !!l.custom, name: l.custom ? l.name : undefined, qty: l.qty, price: l.price, gst: l.gst})))">
      <input type="hidden" name="discount" :value="discountAmount()">
      <input type="hidden" name="discount_percent" :value="['5','10'].includes(discountMode) ? discountMode : ''">
      <input type="hidden" name="tax_type" :value="taxType">
      <input type="hidden" name="broker_commission_percent" :value="brokerCommissionPercent()">

      <select name="customer_id" x-model="customerId" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">Walk-in customer (no Udhaar)</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>">
            <?= h($c['name']) ?> · <?= h($c['mobile']) ?><?= (float)$c['current_balance'] > 0 ? ' (Udhaar ₹' . number_format((float)$c['current_balance'],0) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div x-show="!customerId">
        <input type="tel" name="walkin_mobile" x-model="walkinMobile" placeholder="Walk-in customer's WhatsApp no. (optional)"
               class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <p class="text-xs text-slate-400 mt-1">Saved as a customer so they show up under Customer Outreach for review/promo messages.</p>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <select name="broker_id" x-model="brokerId" @change="brokerCommissionMode = 'default'" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="">No broker</option>
          <?php foreach ($brokers as $br): ?>
            <option value="<?= (int)$br['id'] ?>"><?= h($br['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="flex items-center gap-1.5" x-show="brokerId">
          <input type="number" step="0.01" min="0" x-show="brokerCommissionMode === 'custom'" x-model.number="brokerCustomCommission"
                 @blur="if (!brokerCustomCommission || brokerCustomCommission < 0) brokerCustomCommission = 0"
                 class="w-14 rounded border border-slate-300 px-2 py-1 text-sm text-right">
          <select x-model="brokerCommissionMode" class="flex-1 rounded border border-slate-300 px-2 py-1 text-sm">
            <option value="default" x-text="brokerDefaultCommissionPercent() + '%'"></option>
            <option value="custom">Custom</option>
          </select>
        </div>
      </div>
      <!-- Recorded on the sale for payout tracking only — never subtracted from
           what the customer pays, unlike Discount above. -->
      <div class="text-xs text-slate-400 text-right" x-show="brokerId" x-text="'Broker earns ₹' + brokerCommissionAmount().toFixed(2) + ' (not deducted from total)'"></div>

      <div class="grid grid-cols-2 gap-2">
        <select name="payment_method" x-model="paymentMethod" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="CASH">Cash</option>
          <option value="UPI">UPI</option>
          <option value="BANK">Bank</option>
          <option value="OTHER">Other</option>
        </select>
        <select name="payment_status" x-model="paymentStatus" class="rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="FULL">Paid in full</option>
          <option value="PARTIAL">Part payment</option>
          <option value="CREDIT">Full Udhaar (no payment now)</option>
        </select>
      </div>

      <div x-show="paymentStatus === 'PARTIAL'">
        <label class="block text-xs font-medium text-slate-700 mb-1">Amount Received Now</label>
        <input type="number" step="0.01" min="0" name="received_amount" x-model.number="receivedAmount" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <div class="text-xs mt-1" :class="owed() > 0 ? 'text-amber-600' : 'text-slate-400'" x-text="'Balance to Udhaar: ₹' + owed().toFixed(2)"></div>
      </div>
      <div x-show="paymentStatus === 'CREDIT'" class="text-xs text-amber-600">
        Full amount (₹<span x-text="grandTotal().toFixed(2)"></span>) goes to Udhaar — a customer must be selected above.
      </div>

      <button type="submit" :disabled="cart.length === 0"
              class="w-full bg-indigo-600 disabled:bg-slate-300 text-white font-medium py-2.5 rounded hover:bg-indigo-700">
        Complete Sale
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../shared/cash-memo-script.php'; ?>
<script>
function posScreen() {
  return {
    ...cashMemoMixin(),
    products: <?= json_encode(array_map(fn($p) => [
        'id' => (int)$p['id'], 'name' => $p['name'], 'category' => $p['category'],
        'selling_price' => (float)$p['selling_price'], 'gst_percent' => (float)$p['gst_percent'],
        'current_stock' => (float)$p['current_stock'], 'unit' => $p['unit'],
        'sku' => $p['sku'], 'barcode' => $p['barcode'],
    ], $products)) ?>,
    brokers: <?= json_encode(array_map(fn($br) => [
        'id' => (int)$br['id'], 'name' => $br['name'], 'default_commission_percent' => (float)$br['default_commission_percent'],
    ], $brokers)) ?>,
    search: '',
    category: '',
    activeTab: 'products',
    customSeq: 0,
    // A physical-keyboard + barcode-scanner desktop setup wants the search box
    // refocused after every add so scanning can continue uninterrupted; a
    // touchscreen phone doesn't have that workflow, and refocusing there just
    // pops the on-screen keyboard back up and yanks the page scroll position
    // on every single product tap.
    isTouchDevice: window.matchMedia('(pointer: coarse)').matches,
    cart: [],
    discountMode: '0', // '0' | '5' | '10' | 'custom'
    customDiscount: 0,
    taxType: 'EXCLUSIVE',
    customerId: '',
    walkinMobile: '',
    brokerId: '',
    brokerCommissionMode: 'default', // 'default' | 'custom' — reset to 'default' whenever the broker changes
    brokerCustomCommission: 0,
    paymentMethod: 'CASH',
    paymentStatus: 'FULL',
    receivedAmount: 0,

    init() {
      this.memoInit();
      if (!this.isTouchDevice) this.$refs.searchBox.focus();
    },

    // ── hooks used by the shared cash memo (views/shared/cash-memo-script.php) ──
    memoItems() { return this.products.map(p => ({ id: p.id, name: p.name, price: p.selling_price, gst: p.gst_percent, stock: Number(p.current_stock) })); },
    memoPushCatalog(p, qty, rate) {
      const existing = this.cart.find(l => l.id === p.id);
      if (existing) { existing.qty += qty; existing.price = rate; }
      else this.cart.push({ id: p.id, name: p.name, price: rate, originalPrice: p.price, gst: p.gst, qty });
    },
    memoPushFree(name, qty, rate, gst) { this.cart.push({ id: -(++this.customSeq), custom: true, name, price: rate, originalPrice: rate, gst, qty }); },
    memoDone() { this.activeTab = 'cart'; },
    categories() {
      return [...new Set(this.products.map(p => p.category).filter(c => c))].sort();
    },
    filteredProducts() {
      const q = this.search.trim().toLowerCase();
      let list = this.products;
      if (this.category) list = list.filter(p => p.category === this.category);
      if (q) list = list.filter(p =>
        p.name.toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q) || (p.barcode||'') === q
      );
      return list.slice(0, 60);
    },
    addToCart(p) {
      const existing = this.cart.find(l => l.id === p.id);
      if (existing) { existing.qty += 1; }
      else { this.cart.push({ id: p.id, name: p.name, price: p.selling_price, originalPrice: p.selling_price, gst: p.gst_percent, qty: 1 }); }
      this.search = '';
      if (!this.isTouchDevice) this.$refs.searchBox.focus();
    },
    removeFromCart(idx) { this.cart.splice(idx, 1); },
    clampQty(line) { if (!line.qty || line.qty < 0) line.qty = 0.001; },
    // Only clamps a negative/blank price back to 0 on blur (not on every
    // keystroke — see clampQty's own history of the same bug) — ₹0 itself is
    // left alone since giving an item away is a legitimate bargain outcome.
    clampPrice(line) { if (!line.price || line.price < 0) line.price = 0; },
    lineTotal() { return this.cart.reduce((s, l) => s + l.qty * l.price, 0); },
    taxableAmount() {
      if (this.taxType === 'INCLUSIVE') {
        return this.cart.reduce((s, l) => s + (l.qty * l.price) / (1 + (l.gst || 0) / 100), 0);
      }
      return this.lineTotal(); // EXCLUSIVE: price is already the pre-tax base. EXEMPT: no tax involved either way.
    },
    taxTotal() {
      if (this.taxType === 'EXEMPT') return 0;
      if (this.taxType === 'INCLUSIVE') return this.lineTotal() - this.taxableAmount();
      return this.cart.reduce((s, l) => s + (l.qty * l.price) * ((l.gst || 0) / 100), 0);
    },
    // Groups cart lines by their own GST% so a cart mixing rates (e.g. 5% and 18%
    // items in one sale) shows each slab's taxable/CGST/SGST separately, instead of
    // one blended CGST/SGST total with no rate shown.
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
    // 5%/10% are computed off the taxable (pre-tax) amount and stay reactive to
    // the cart — a preset picked before the cart is finished still tracks it as
    // items are added/removed, instead of freezing a stale rupee figure.
    discountAmount() {
      const base = this.taxableAmount();
      if (this.discountMode === '5') return +(base * 0.05).toFixed(2);
      if (this.discountMode === '10') return +(base * 0.10).toFixed(2);
      if (this.discountMode === 'custom') return +(this.customDiscount || 0);
      return 0;
    },
    grandTotal() { return Math.max(0, this.taxableAmount() + this.taxTotal() - this.discountAmount()); },
    // Broker commission is a payout the shop owes out of its own margin — it's
    // recorded on the sale for the Brokers report, but unlike Discount it never
    // touches grandTotal(); the customer's bill is unaffected either way.
    brokerDefaultCommissionPercent() {
      const b = this.brokers.find(x => x.id === Number(this.brokerId));
      return b ? b.default_commission_percent : 0;
    },
    brokerCommissionPercent() {
      if (!this.brokerId) return 0;
      return this.brokerCommissionMode === 'custom' ? +(this.brokerCustomCommission || 0) : this.brokerDefaultCommissionPercent();
    },
    brokerCommissionAmount() { return +(this.taxableAmount() * this.brokerCommissionPercent() / 100).toFixed(2); },
    owed() {
      if (this.paymentStatus === 'CREDIT') return this.grandTotal();
      if (this.paymentStatus === 'PARTIAL') return Math.max(0, this.grandTotal() - (this.receivedAmount||0));
      return 0;
    },
    onSubmit(e) {
      if (this.cart.length === 0) { e.preventDefault(); return; }
      // A tap straight from the qty/price field to "Complete Sale" can beat
      // that field's own blur handler to the submit — clamp every line here
      // too so a still-empty/zero quantity or negative price never reaches
      // the server.
      this.cart.forEach(line => { this.clampQty(line); this.clampPrice(line); });
      if (this.owed() > 0 && !this.customerId) {
        e.preventDefault();
        alert('Select a customer before recording a part-paid or Udhaar sale.');
        return;
      }
      if (this.paymentStatus === 'FULL') this.receivedAmount = this.grandTotal();
      if (this.paymentStatus === 'CREDIT') this.receivedAmount = 0;
    },
  };
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
