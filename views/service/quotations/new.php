<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div x-data="quoteScreen()" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <input type="text" x-model="search" placeholder="Search services…" class="w-full rounded border border-slate-300 px-3 py-2 text-sm mb-3">
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

  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col">
    <h2 class="font-semibold text-slate-900 mb-2">Quotation Items</h2>
    <div class="flex-1 overflow-y-auto max-h-[38vh] divide-y divide-slate-100">
      <template x-for="(line, idx) in cart" :key="line.id">
        <div class="py-2 flex items-center gap-2">
          <div class="flex-1 min-w-0 text-sm font-medium text-slate-900 truncate" x-text="line.name"></div>
          <input type="number" step="1" min="1" x-model.number="line.qty" class="w-14 rounded border border-slate-300 px-2 py-1 text-sm text-right">
          <span class="text-sm font-semibold w-20 text-right" x-text="'₹' + (line.qty * line.price).toFixed(2)"></span>
          <button type="button" @click="cart.splice(idx,1)" class="text-red-500 text-xs">✕</button>
        </div>
      </template>
      <div x-show="cart.length === 0" class="text-sm text-slate-400 text-center py-8">Tap a service to add it.</div>
    </div>

    <div class="border-t border-slate-200 mt-2 pt-3 space-y-2 text-sm">
      <div class="flex justify-between items-center"><span class="text-slate-500">Labour Charges</span><input type="number" step="0.01" x-model.number="labour" class="w-24 rounded border border-slate-300 px-2 py-1 text-right"></div>
      <div class="flex justify-between items-center"><span class="text-slate-500">Discount</span><input type="number" step="0.01" x-model.number="discount" class="w-24 rounded border border-slate-300 px-2 py-1 text-right"></div>
      <div class="flex justify-between font-semibold text-base pt-1 border-t border-slate-100"><span>Grand Total</span><span x-text="'₹' + grandTotal().toFixed(2)"></span></div>
    </div>

    <form method="POST" class="mt-3 space-y-2">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="cart_json" :value="JSON.stringify(cart.map(l => ({id: l.id, qty: l.qty})))">
      <input type="hidden" name="labour_charges" :value="labour">
      <input type="hidden" name="discount" :value="discount">

      <select name="customer_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="">Walk-in customer</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?> · <?= h($c['mobile']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Quote Date</label>
          <input type="date" name="quote_date" value="<?= date('Y-m-d') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Valid Until</label>
          <input type="date" name="valid_until" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
      </div>
      <textarea name="notes" placeholder="Notes" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>

      <button type="submit" :disabled="cart.length === 0" class="w-full bg-indigo-600 disabled:bg-slate-300 text-white font-medium py-2.5 rounded hover:bg-indigo-700">Save Quotation</button>
    </form>
  </div>
</div>

<script>
function quoteScreen() {
  return {
    services: <?= json_encode(array_map(fn($s) => ['id'=>(int)$s['id'],'name'=>$s['name'],'category'=>$s['category'],'price'=>(float)$s['price']], $services)) ?>,
    search: '', cart: [], labour: 0, discount: 0,
    filteredServices() {
      const q = this.search.trim().toLowerCase();
      if (!q) return this.services.slice(0, 60);
      return this.services.filter(s => s.name.toLowerCase().includes(q)).slice(0, 60);
    },
    addToCart(s) {
      const existing = this.cart.find(l => l.id === s.id);
      if (existing) existing.qty += 1;
      else this.cart.push({ id: s.id, name: s.name, price: s.price, qty: 1 });
    },
    subtotal() { return this.cart.reduce((s,l) => s + l.qty*l.price, 0); },
    grandTotal() { return Math.max(0, this.subtotal() + (this.labour||0) - (this.discount||0)); },
  };
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
