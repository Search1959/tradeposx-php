<?php $org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Scan Purchase Bill</h1>
  <a href="<?= $base ?>/purchases" class="text-sm text-slate-500">&larr; Back to Purchases</a>
</div>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<?php if (!GeminiHelper::isConfigured()): ?>
  <div class="mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded px-3 py-2">
    AI bill scanning isn't set up yet — add a Gemini API key to <code>config/constants.php</code> (<code>GEMINI_API_KEY</code>) to enable it.
    You can still <a href="<?= $base ?>/purchases/new" class="underline">enter a purchase manually</a>.
  </div>
<?php endif; ?>

<?php if (!$scanned): ?>
  <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-lg space-y-4" x-data="scanUpload()" @submit="submitting = true">
    <?= CsrfHelper::getField() ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Photo or PDF of the bill</label>
      <!-- Two separate pickers, not one input with a mixed accept list — combining
           accept="image/*,.pdf" with capture makes many mobile browsers silently
           drop the camera shortcut and fall back to a plain file chooser, since a
           camera obviously can't produce a PDF. A pure accept="image/*" input is
           what actually guarantees the camera opens. -->
      <div class="flex gap-2">
        <label class="flex-1 text-center cursor-pointer bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-2 rounded hover:bg-slate-50">
          📷 Take Photo
          <input type="file" accept="image/*" capture="environment" class="hidden" @change="pick($event)">
        </label>
        <label class="flex-1 text-center cursor-pointer bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-2 rounded hover:bg-slate-50">
          📁 Choose File
          <input type="file" accept="image/*,.pdf" class="hidden" @change="pick($event)">
        </label>
      </div>
      <div class="text-xs mt-2 text-slate-500" x-show="compressing" x-cloak>Preparing photo&hellip;</div>
      <div class="text-xs mt-2" x-show="!compressing" :class="fileName ? 'text-slate-700 font-medium' : 'text-slate-400'" x-text="fileName || 'No file chosen'"></div>
      <!-- No `required` here — a hidden required file input can make some mobile
           browsers (notably Chrome on Android) silently refuse to submit the form
           at all, with no error and no console warning, since a display:none
           element can never show the native "please fill this field" bubble.
           The submit button below is already disabled until fileName is set, so
           HTML5 required validation on this hidden proxy is redundant anyway. -->
      <input type="file" name="bill_file" x-ref="realInput" class="hidden">
      <p class="text-xs text-slate-400 mt-1">Take Photo opens your camera directly. Choose File also accepts a PDF invoice or a photo already in your gallery, including handwritten or regional-language bills.</p>
    </div>
    <button type="submit" :disabled="!fileName || submitting || compressing || <?= GeminiHelper::isConfigured() ? 'false' : 'true' ?>"
            class="bg-indigo-600 disabled:bg-slate-300 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">
      <span x-show="!submitting">Scan Bill</span>
      <span x-show="submitting" x-cloak>Scanning&hellip; this can take up to 15 seconds</span>
    </button>
  </form>

  <script>
  function scanUpload() {
    return {
      fileName: '',
      submitting: false,
      compressing: false,
      async pick(e) {
        const raw = e.target.files[0];
        if (!raw) return;
        this.compressing = true;
        try {
          const file = await resizeImageIfNeeded(raw);
          const dt = new DataTransfer();
          dt.items.add(file);
          this.$refs.realInput.files = dt.files;
          this.fileName = file.name;
        } finally {
          this.compressing = false;
        }
      },
    };
  }

  /** A real phone-camera photo is often 3-8MB at full resolution — plenty for a
   * human to read but far more than Gemini needs for OCR, and slow/unreliable to
   * upload from a shop's mobile connection. Downscaling to a readable-but-modest
   * width before sending cuts the payload by ~85-90% and made a real-world blurry
   * photo bill that was timing out on every attempt scan successfully instead.
   * PDFs pass through untouched — this only applies to actual photos. */
  async function resizeImageIfNeeded(file, maxDim = 1600, quality = 0.85) {
    if (!file.type.startsWith('image/')) return file;
    try {
      const bitmap = await createImageBitmap(file);
      const { width, height } = bitmap;
      if (Math.max(width, height) <= maxDim && file.size <= 1.5 * 1024 * 1024) return file;
      const scale = Math.min(1, maxDim / Math.max(width, height));
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(width * scale);
      canvas.height = Math.round(height * scale);
      canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));
      return blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file;
    } catch (e) {
      return file; // if compression fails for any reason, fall back to the original rather than blocking the scan
    }
  }
  </script>
<?php else: ?>
  <div x-data="scanReview()" x-cloak>
    <div class="bg-blue-50 border border-blue-200 rounded px-4 py-2 text-sm text-blue-800 mb-4">
      Scanned successfully — review and correct anything below before saving. Nothing is saved yet.
    </div>

    <form method="POST" action="<?= $base ?>/purchases/saveScanned" class="space-y-4">
      <?= CsrfHelper::getField() ?>
      <input type="hidden" name="items_json" :value="JSON.stringify(items)">

      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Supplier</label>
          <select name="supplier_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">— (use scanned name) —</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="supplier_name" value="<?= h($scanned['supplierName'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Invoice Number</label>
          <input type="text" name="invoice_number" value="<?= h($scanned['invoiceNumber'] ?? '') ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Purchase Date</label>
          <input type="date" name="purchase_date" value="<?= h($scanned['invoiceDate'] ?? date('Y-m-d')) ?>" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Payment Method</label>
          <select name="payment_method" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="CASH">Cash</option><option value="UPI">UPI</option><option value="BANK">Bank</option><option value="OTHER">Other</option>
          </select>
        </div>
      </div>

      <div class="border border-slate-200 rounded-lg overflow-x-auto bg-white">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
            <tr><th class="text-left px-3 py-2">Item Name</th><th class="text-right px-3 py-2 w-24">Qty</th><th class="text-left px-3 py-2 w-20">Unit</th><th class="text-right px-3 py-2 w-28">Rate</th><th class="text-right px-3 py-2 w-20">GST%</th><th class="text-right px-3 py-2 w-24">Total</th><th class="w-8"></th></tr>
          </thead>
          <tbody>
            <template x-for="(item, idx) in items" :key="idx">
              <tr class="border-t border-slate-100">
                <td class="px-3 py-1.5"><input type="text" x-model="item.name" class="w-full rounded border border-slate-300 px-2 py-1 text-sm"></td>
                <td class="px-3 py-1.5"><input type="number" step="0.001" x-model.number="item.quantity" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
                <td class="px-3 py-1.5"><input type="text" x-model="item.unit" class="w-full rounded border border-slate-300 px-2 py-1 text-sm"></td>
                <td class="px-3 py-1.5"><input type="number" step="0.01" x-model.number="item.purchase_price" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
                <td class="px-3 py-1.5"><input type="number" step="0.01" x-model.number="item.gst_percent" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
                <td class="px-3 py-1.5 text-right font-medium" x-text="'₹' + lineTotal(item).toFixed(2)"></td>
                <td class="px-3 py-1.5 text-center"><button type="button" @click="items.splice(idx,1)" class="text-red-500">✕</button></td>
              </tr>
            </template>
          </tbody>
        </table>
        <button type="button" @click="items.push({name:'',quantity:1,unit:'pcs',purchase_price:0,gst_percent:0,hsn:''})" class="w-full text-sm text-indigo-600 py-2 hover:bg-indigo-50 border-t border-slate-100">+ Add line</button>
      </div>

      <div class="flex items-center justify-between bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <div class="text-sm">
          <label class="font-medium text-slate-700 mr-2">Paid Now</label>
          <input type="number" step="0.01" name="paid_amount" x-model.number="paidAmount" class="rounded border border-slate-300 px-3 py-2 text-sm w-36">
        </div>
        <div class="text-right">
          <div class="text-xs text-slate-500">Grand Total</div>
          <div class="text-lg font-semibold" x-text="'₹' + grandTotal().toFixed(2)"></div>
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save Purchase</button>
        <a href="<?= $base ?>/purchases/scan" class="text-sm text-slate-500 px-4 py-2">Scan a different bill</a>
      </div>
    </form>
  </div>

  <script>
  function scanReview() {
    return {
      items: <?= json_encode(array_map(fn($i) => [
          'name' => $i['name'] ?? '', 'quantity' => (float)($i['quantity'] ?? 1), 'unit' => $i['unit'] ?? 'pcs',
          'purchase_price' => (float)($i['purchasePrice'] ?? 0), 'gst_percent' => (float)($i['gstPercent'] ?? 0),
          'hsn' => $i['hsn'] ?? '',
      ], $scanned['items'] ?? [])) ?>,
      paidAmount: 0,
      lineTotal(item) { return (item.quantity||0) * (item.purchase_price||0) * (1 + (item.gst_percent||0)/100); },
      grandTotal() { return this.items.reduce((s,i) => s + this.lineTotal(i), 0); },
    };
  }
  </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
