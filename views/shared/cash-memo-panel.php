<?php /** Cash memo tab body — voice first. Pairs with views/shared/cash-memo-script.php (cashMemoMixin). Include inside the host's x-data scope. */ ?>
<div x-show="leftTab === 'memo'" x-cloak>
  <datalist id="memoItems"><template x-for="p in memoItems()" :key="p.id"><option :value="p.name"></option></template></datalist>

  <!-- Voice first: tap, speak "item, quantity, rate", say "next" for the following item. Rows fill in as you speak. -->
  <div class="rounded-xl border border-indigo-200 bg-gradient-to-b from-indigo-50 to-white p-4 mb-3 text-center">
    <button type="button" x-show="voiceSupported" @click="toggleVoice()"
            :class="listening ? 'bg-red-600 animate-pulse' : 'bg-indigo-600 hover:bg-indigo-700'"
            class="mx-auto w-full sm:w-auto sm:px-10 flex items-center justify-center gap-3 text-white font-semibold text-base rounded-full py-4 shadow-md transition">
      <span class="text-2xl leading-none" x-text="listening ? '⏹' : '🎤'"></span>
      <span x-text="listening ? 'Listening… tap to stop' : 'Tap and speak your list'"></span>
    </button>
    <p x-show="!voiceSupported" class="text-sm text-amber-700">Voice works in Chrome or Edge. You can type below instead.</p>
    <div class="flex items-center justify-center gap-2 mt-3 text-xs text-slate-600">
      Language
      <select x-model="voiceLang" class="rounded border border-slate-300 px-2 py-1 bg-white text-xs">
        <option value="en-IN">English</option><option value="hi-IN">हिन्दी</option><option value="bn-IN">বাংলা</option>
      </select>
    </div>
    <div x-show="listening" x-cloak class="mt-3 min-h-[1.5rem] text-sm italic text-indigo-700"
         x-text="interim ? '“' + interim + '”' : 'Say the item … pause … quantity … pause … rate'"></div>
    <p x-show="!listening" class="text-xs text-slate-500 mt-2">Say the item, pause, the quantity, pause, the rate — then the next item. No need to say “quantity” or “rate”.</p>
    <div x-show="voiceError" x-cloak class="text-xs text-red-600 mt-2" x-text="voiceError"></div>
    <button type="button" @click="typeOpen = !typeOpen" class="mt-2 text-xs text-indigo-600 hover:underline" x-text="typeOpen ? 'Hide typing box' : 'Type instead'"></button>
  </div>

  <div x-show="typeOpen" x-cloak class="mb-3">
    <textarea x-model="memoText" rows="2" placeholder="e.g.  Basin mixer 2 2500, Granite 10 180, Wall tile 50 68"
              class="w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>
    <button type="button" @click="parseMemoText()" class="mt-2 text-sm font-medium px-3 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700">Fill rows</button>
  </div>

  <div x-show="memoMsg" x-cloak class="mb-2 text-sm text-emerald-700" x-text="memoMsg"></div>

  <!-- The memo: heading + as many rows as needed -->
  <div class="rounded-lg border border-slate-200 overflow-hidden">
    <div class="hidden xl:grid grid-cols-[1fr_4rem_5rem_5rem_1.5rem] gap-2 px-3 py-2 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
      <div>Item</div><div class="text-right">Qty</div><div class="text-right">Rate</div><div class="text-right">Amount</div><div></div>
    </div>
    <div class="xl:hidden px-3 py-2 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Item · Qty · Rate · Amount</div>
    <div class="max-h-[42vh] overflow-y-auto divide-y divide-slate-100">
      <template x-for="(r, i) in memoRows" :key="r.k">
        <div class="px-3 py-2" :class="r.error ? 'bg-red-50' : ''">
          <div class="grid grid-cols-[4rem_1fr_4.75rem_1.5rem] xl:grid-cols-[1fr_4rem_5rem_5rem_1.5rem] gap-2 items-center">
            <input type="text" list="memoItems" data-memo-item x-model="r.item" @change="matchRow(r)" @blur="matchRow(r)" placeholder="Item name"
                   class="col-span-4 xl:col-span-1 min-w-0 rounded border border-slate-300 px-2 py-1.5 text-sm">
            <input type="number" step="any" min="0" x-model.number="r.qty" placeholder="1" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm text-right">
            <input type="number" step="0.01" min="0" x-model.number="r.rate" @keydown.enter.prevent="memoEnter(i)" placeholder="0.00" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm text-right">
            <div class="text-sm font-semibold text-right" x-text="memoAmount(r) > 0 ? '₹' + memoAmount(r).toFixed(2) : ''"></div>
            <button type="button" @click="memoRemove(i)" class="text-red-400 hover:text-red-600 text-sm" aria-label="Remove row">✕</button>
          </div>
          <div class="flex items-center gap-2 mt-1 text-[11px]" x-show="r.item.trim() !== ''">
            <template x-if="r.pid">
              <span :class="r.stock !== null && (r.qty || 1) > r.stock ? 'text-amber-600 font-medium' : 'text-slate-400'"
                    x-text="r.stock === null ? 'From your list · GST ' + r.gst + '%' : ((r.qty || 1) > r.stock ? 'Only ' + Math.max(0, r.stock) + ' in stock' : r.stock + ' in stock · GST ' + r.gst + '%')"></span>
            </template>
            <template x-if="!r.pid">
              <span class="flex items-center gap-1 text-slate-500">Free item (not in your list) · GST
                <select x-model.number="r.gst" class="rounded border border-slate-300 px-1 py-0.5 text-[11px] bg-white">
                  <option value="0">0%</option><option value="5">5%</option><option value="12">12%</option><option value="18">18%</option><option value="28">28%</option>
                </select></span>
            </template>
            <span x-show="r.error" class="text-red-600 font-medium" x-text="r.error"></span>
          </div>
        </div>
      </template>
    </div>
    <div class="flex items-center justify-between gap-2 px-3 py-2.5 bg-slate-50 border-t border-slate-200">
      <div class="flex items-center gap-3 text-sm">
        <button type="button" @click="memoAddRows(1)" class="text-indigo-600 font-medium hover:underline">+ Add row</button>
        <button type="button" @click="memoClear()" class="text-slate-500 hover:underline">Clear</button>
      </div>
      <div class="text-sm text-slate-600">Memo total <span class="font-bold text-slate-900" x-text="'₹' + memoTotal().toFixed(2)"></span></div>
    </div>
  </div>
  <button type="button" @click="memoSubmit()" class="mt-3 w-full bg-indigo-600 text-white font-semibold py-2.5 rounded-lg hover:bg-indigo-700">Add to cart →</button>
</div>
