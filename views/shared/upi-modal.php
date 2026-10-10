<?php /** "Scan to pay" window for UPI checkouts. Pairs with views/shared/upi-script.php (upiMixin). Include inside the host's x-data scope. */ ?>
<div x-show="upiOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-3" @keydown.escape.window="upiOpen = false">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5 text-center max-h-[95vh] overflow-y-auto" @click.outside="upiOpen = false" role="dialog" aria-modal="true" aria-label="Scan to pay">
    <div class="text-sm font-medium text-slate-500">Scan to pay with any UPI app</div>
    <div class="text-4xl font-bold text-slate-900 mt-1" x-text="'₹' + upiAmount.toFixed(2)"></div>
    <div class="mx-auto mt-3 w-56 h-56 sm:w-60 sm:h-60 p-2 bg-white border border-slate-200 rounded-xl" x-ref="upiQr"></div>
    <div class="text-xs text-slate-500 mt-2">Paying <b class="text-slate-700" x-text="upiPayee"></b><br><span class="font-mono" x-text="upiId"></span></div>
    <a :href="upiLink" class="sm:hidden mt-3 block w-full border border-indigo-300 text-indigo-700 font-medium text-sm py-2 rounded-lg">Open in my UPI app</a>
    <button type="button" @click="upiConfirm()" class="mt-3 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg">✓ Payment received — Generate bill</button>
    <button type="button" @click="upiOpen = false" class="mt-2 w-full text-sm text-slate-500 py-1.5">Cancel</button>
    <p class="text-[11px] text-slate-400 mt-2">Tap “Payment received” only after the amount shows in your UPI app or SMS — the bill is created when you tap it.</p>
  </div>
</div>