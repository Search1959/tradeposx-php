<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div x-data="posScreen()" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <!-- Product picker -->
  <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <input type="text" x-model="search" placeholder="Search products by name, SKU, barcode…"
           class="w-full rounded border border-slate-300 px-3 py-2 text-sm mb-3" x-ref="searchBox" autofocus>
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
  </div>

  <!-- Cart + checkout -->
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col">
    <h2 class="font-semibold text-slate-900 mb-2">Cart</h2>
    <div class="flex-1 overflow-y-auto max-h-[38vh] divide-y divide-slate-100">
      <template x-for="(line, idx) in cart" :key="line.id">
        <div class="py-2 flex items-center gap-2">
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-slate-900 truncate" x-text="line.name"></div>
            <div class="text-xs text-slate-500" x-text="'₹' + Number(line.price).toFixed(2) + ' each'"></div>
          </div>
          <input type="number" step="0.001" min="0.001" x-model.number="line.qty" @input="clampQty(line)"
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
        <div>
          <div class="flex justify-between"><span class="text-slate-500">CGST</span><span x-text="'₹' + (taxTotal()/2).toFixed(2)"></span></div>
          <div class="flex justify-between"><span class="text-slate-500">SGST</span><span x-text="'₹' + (taxTotal()/2).toFixed(2)"></span></div>
        </div>
      </template>
      <div class="flex justify-between items-center">
        <span class="text-slate-500">Discount</span>
        <input type="number" step="0.01" min="0" x-model.number="discount" class="w-24 rounded border border-slate-300 px-2 py-1 text-right">
      </div>
      <div class="flex justify-between font-semibold text-base pt-1 border-t border-slate-100">
        <span>Grand Total</span><span x-text="'₹' + grandTotal().toFixed(2)"></span>
      </div>
    </div>

    <form method="POST" class="mt-3 space-y-2" @submit="onSubmit">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="cart_json" :value="JSON.stringify(cart.map(l => ({id: l.id, qty: l.qty})))">
      <input type="hidden" name="discount" :value="discount">
      <input type="hidden" name="tax_type" :value="taxType">

      <select name="customer_id" x-model="customerId" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">Walk-in customer (no Udhaar)</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>">
            <?= h($c['name']) ?> · <?= h($c['mobile']) ?><?= (float)$c['current_balance'] > 0 ? ' (Udhaar ₹' . number_format((float)$c['current_balance'],0) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>

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

<script>
function posScreen() {
  return {
    products: <?= json_encode(array_map(fn($p) => [
        'id' => (int)$p['id'], 'name' => $p['name'], 'category' => $p['category'],
        'selling_price' => (float)$p['selling_price'], 'gst_percent' => (float)$p['gst_percent'],
        'current_stock' => (float)$p['current_stock'], 'unit' => $p['unit'],
        'sku' => $p['sku'], 'barcode' => $p['barcode'],
    ], $products)) ?>,
    search: '',
    category: '',
    cart: [],
    discount: 0,
    taxType: 'EXCLUSIVE',
    customerId: '',
    paymentMethod: 'CASH',
    paymentStatus: 'FULL',
    receivedAmount: 0,

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
      else { this.cart.push({ id: p.id, name: p.name, price: p.selling_price, gst: p.gst_percent, qty: 1 }); }
      this.search = '';
      this.$refs.searchBox.focus();
    },
    removeFromCart(idx) { this.cart.splice(idx, 1); },
    clampQty(line) { if (!line.qty || line.qty < 0) line.qty = 0.001; },
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
    grandTotal() { return Math.max(0, this.taxableAmount() + this.taxTotal() - (this.discount||0)); },
    owed() {
      if (this.paymentStatus === 'CREDIT') return this.grandTotal();
      if (this.paymentStatus === 'PARTIAL') return Math.max(0, this.grandTotal() - (this.receivedAmount||0));
      return 0;
    },
    onSubmit(e) {
      if (this.cart.length === 0) { e.preventDefault(); return; }
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
