<?php /** Cash memo tab body — voice first. Pairs with views/shared/cash-memo-script.php (cashMemoMixin). Include inside the host's x-data scope. */ ?>
<div x-show="leftTab === 'memo'" x-cloak>
  <datalist id="memoItems"><template x-for="p in memoItems()" :key="p.id"><option :value="p.name"></option></template></datalist>

  <!-- Speak one item → it appears in the box → check/edit → press Add → repeat for the next item. -->
  <div class="rounded-xl border border-indigo-200 bg-gradient-to-b from-indigo-50 to-white p-4 mb-3">
    <div class="flex items-stretch gap-2">
      <button type="button" x-show="voiceSupported" @click="toggleVoice()"
              :class="listening ? 'bg-red-600 animate-pulse' : 'bg-indigo-600 hover:bg-indigo-700'"
              class="shrink-0 flex items-center justify-center gap-2 text-white font-semibold text-sm rounded-lg px-4 py-3 shadow transition"
              :aria-label="listening ? 'Stop listening' : 'Speak an item'">
        <span class="text-xl leading-none" x-text="listening ? '⏹' : '🎤'"></span>
        <span class="hidden sm:inline" x-text="listening ? 'Listening…' : 'Speak'"></span>
      </button>
      <input id="memoSayBox" type="text" x-model="memoText" @keydown.enter.prevent="parseMemoText()"
             :placeholder="listening ? 'Listening… say the item, quantity and rate' : 'What you say appears here — e.g. Coffee 2 50'"
             class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm">
      <button type="button" @click="parseMemoText()" class="shrink-0 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-4 py-3 shadow">Add</button>
    </div>
    <div class="flex items-center justify-between gap-2 mt-2 text-xs text-slate-600">
      <span>Say <b>item, quantity, rate</b> in one breath — “Coffee 2 50” — then tap <b>Add</b>.</span>
      <label class="shrink-0 flex items-center gap-1">
        <select x-model="voiceLang" class="rounded border border-slate-300 px-1.5 py-1 bg-white text-xs">
          <option value="en-IN">English</option><option value="hi-IN">हिन्दी</option><option value="bn-IN">বাংলা</option>
        </select>
      </label>
    </div>
    <p x-show="!voiceSupported" class="text-xs text-amber-700 mt-2">Voice works in Chrome or Edge — you can type in the box instead.</p>
    <div x-show="voiceError" x-cloak class="text-xs text-red-600 mt-2" x-text="voiceError"></div>
    <span class="block text-[10px] text-slate-300 mt-1 text-right">memo v10</span>
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
        <div class="px-3 py-2" :class="r.error ? 'bg-red-50' : ''" :data-memo-err="r.error ? '1' : null">
          <div class="grid grid-cols-[4rem_1fr_4.75rem_1.5rem] xl:grid-cols-[1fr_4rem_5rem_5rem_1.5rem] gap-2 items-center">
            <input type="text" list="memoItems" data-memo-item x-model="r.item" @change="matchRow(r)" @blur="matchRow(r)" placeholder="Item name"
                   class="col-span-4 xl:col-span-1 min-w-0 rounded border border-slate-300 px-2 py-1.5 text-sm">
            <input type="number" step="any" min="0" x-model.number="r.qty" @input="r.guess = null" placeholder="1" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm text-right">
            <input type="number" step="0.01" min="0" x-model.number="r.rate" @input="r.guess = null" @keydown.enter.prevent="memoEnter(i)" placeholder="0.00" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm text-right">
            <div class="text-sm font-semibold text-right" x-text="memoAmount(r) > 0 ? '₹' + memoAmount(r).toFixed(2) : ''"></div>
            <button type="button" @click="memoRemove(i)" class="text-red-400 hover:text-red-600 text-sm" aria-label="Remove row">✕</button>
          </div>
          <div class="mt-1 text-[11px] text-amber-700 bg-amber-50 rounded px-2 py-1" x-show="r.guess" x-cloak>
            Heard “<span x-text="r.guess"></span>” — split as <b x-text="r.qty"></b> × ₹<b x-text="r.rate"></b>.
            <button type="button" class="underline font-medium ml-1" @click="r.qty = 1; r.rate = r.guess; r.guess = null">Use <span x-text="r.guess"></span> as the rate instead</button>
          </div>
          <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-[11px]" x-show="r.item.trim() !== ''">
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
            <span class="flex items-center gap-1 text-slate-500 ml-auto">Unit
              <select x-model="r.unit" class="rounded border border-slate-300 px-1 py-0.5 text-[11px] bg-white">
                <optgroup label="Count"><option value="pcs">pcs</option><option value="dozen">dozen</option><option value="box">box</option><option value="pkt">packet</option><option value="bag">bag</option><option value="strip">strip</option><option value="set">set</option></optgroup>
                <optgroup label="Weight"><option value="kg">kg</option><option value="g">gram</option></optgroup>
                <optgroup label="Volume"><option value="ltr">litre</option><option value="ml">ml</option></optgroup>
                <optgroup label="Length"><option value="m">metre</option><option value="cm">cm</option><option value="ft">feet</option></optgroup>
              </select>
              <span x-show="unitInfo(r).base !== 'pcs'" class="text-slate-400" x-text="'· rate per ' + unitInfo(r).base"></span>
              <span x-show="unitInfo(r).f !== 1" class="text-indigo-600 font-medium" x-text="'= ' + baseQty(r) + ' ' + unitInfo(r).base"></span>
            </span>
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
  <div x-show="memoErr" x-cloak class="mt-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2" x-text="memoErr"></div>
  <button type="button" @click="memoSubmit()" class="mt-3 w-full bg-indigo-600 text-white font-semibold py-2.5 rounded-lg hover:bg-indigo-700">Add to cart →</button>
</div>
