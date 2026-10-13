<?php /** Camera window for scanning product QR codes / barcodes into the cash memo. Pairs with the scan methods in cash-memo-script.php. */ ?>
<div x-show="scanOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-3" @keydown.escape.window="closeScan()">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[96vh] overflow-y-auto" role="dialog" aria-modal="true" aria-label="Scan items">
    <div class="flex items-center justify-between px-4 pt-4">
      <div class="font-semibold text-slate-900">Scan items</div>
      <button type="button" @click="closeScan()" class="text-slate-400 hover:text-slate-700 text-xl leading-none" aria-label="Close scanner">✕</button>
    </div>
    <p class="px-4 text-xs text-slate-500 mt-0.5">Point the camera at a product’s QR code or barcode. Each scan adds a row — scan the same item again to add 1 more.</p>
    <div class="px-4 mt-3">
      <div id="tpxScanView" class="w-full rounded-xl overflow-hidden bg-slate-900 min-h-[220px] flex items-center justify-center text-slate-400 text-sm">
        <span x-show="!scanErr && !scanReady">Starting camera…</span>
      </div>
      <div id="tpxScanFile" class="hidden"></div>
      <p x-show="scanErr" x-cloak class="mt-2 text-sm text-red-600" x-text="scanErr"></p>
    </div>
    <div class="px-4 mt-3">
      <div x-show="scanLog.length" x-cloak class="rounded-lg border border-slate-200 divide-y divide-slate-100 max-h-40 overflow-y-auto">
        <template x-for="(e, i) in scanLog" :key="i">
          <div class="px-3 py-2 text-sm flex items-center justify-between gap-2">
            <span class="min-w-0 truncate"><span :class="e.known ? 'text-emerald-600' : 'text-amber-600'" x-text="e.known ? '✓' : '?'"></span> <b x-text="e.name"></b></span>
            <span class="shrink-0 text-xs text-slate-500" x-text="e.known ? '× ' + e.qty : 'not in your list'"></span>
          </div>
        </template>
      </div>
      <p x-show="!scanLog.length && !scanErr" class="text-xs text-slate-400 text-center">Nothing scanned yet.</p>
    </div>
    <div class="px-4 py-4 flex items-center gap-2">
      <label class="flex-1 text-center cursor-pointer border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium rounded-lg py-2.5">
        Scan from a photo
        <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanFile($event)">
      </label>
      <button type="button" @click="closeScan()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg py-2.5 text-sm">Done</button>
    </div>
  </div>
</div>