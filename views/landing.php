<?php
$tradeSectors = [
    'Kirana & FMCG','Metals & Steel','Ball Bearings & Industrial Parts','Agriculture & Farm Produce',
    'Textiles & Fabrics','Chemicals & Industrial Supplies','Energy & Fuel','Jewellery & Ornaments',
    'Stationery & Office Supplies','Building Materials & Hardware','General Trading','Pharmacy & Medical Supplies',
    'Electrical & Electronics','Auto Parts & Accessories','Dairy & Beverages','Fruits & Vegetables',
    'Bakery & Confectionery','Furniture & Wood','Plastics & Packaging','Mobile & Computers',
    'Footwear & Garments','Cosmetics & Personal Care','Seeds & Fertilizers','Water & RO Systems',
    'Toys & Games','Sports Goods & Fitness Equipment','Optical & Eyewear','Watches & Accessories',
    'Musical Instruments','Home Decor & Furnishings','Pet Supplies & Pet Food','Books & Publications',
    'Leather Goods','Bags & Luggage','Tobacco & Pan Shop','Ice Cream & Frozen Foods',
    'Meat & Poultry','Spices & Condiments','Rice & Grain Mill','Cold Storage & Warehousing',
    'Timber & Plywood','Cement & Building Supplies','Sanitaryware & Bath Fittings','Solar & Renewable Equipment',
    'CCTV & Security Equipment','Gym & Fitness Equipment','Ayurvedic & Herbal Products','Party & Event Supplies',
    'Paints & Hardware','Tyres & Batteries',
];
$serviceSectors = array_values(SectorHelper::serviceSectors());

// Only real, shipped capabilities below — every line here maps to an actual
// screen/feature in the app, never a placeholder or aspirational claim.
$tradeFeatures = [
    'Products, stock &amp; barcode-ready POS',
    'GST Tax Invoices (Exclusive / Inclusive / Exempt)',
    'Purchases, suppliers &amp; bulk CSV import',
    'Udhaar (credit) ledger with WhatsApp reminders',
    'Mobile card-tap POS, installable as an app',
];
$serviceFeatures = [
    'Appointments &amp; job card tracking',
    'GST service invoices &amp; quotations',
    'Staff assignments &amp; commission payouts',
    'Balance-due reminders sent over WhatsApp',
    'Mobile-first billing, installable as an app',
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h(APP_NAME) ?> — POS &amp; Business Management for Every Trade</title>
<meta name="description" content="Mobile-first POS, GST billing, stock, Udhaar, appointments and job cards for Indian shops and service businesses.">
<link rel="manifest" href="/assets/manifest.webmanifest">
<link rel="icon" href="/assets/img/icons/icon-192.png">
<meta name="theme-color" content="#020617">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  [x-cloak] { display: none !important; }
  body { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  .card-trade:hover { border-color: rgba(99,102,241,0.5); }
  .card-service:hover { border-color: rgba(16,185,129,0.5); }
  details > summary::-webkit-details-marker { display: none; }
</style>
</head>
<?php
require_once __DIR__ . '/layouts/icons.php';
// Every card below maps to a screen that exists in the app today — no aspirational claims.
$featureCards = [
    ['cart', 'indigo', 'Fast POS & billing', 'Tap-to-add cart, editable prices for bargaining, discounts, and paid / part-paid / credit sales.'],
    ['receipt', 'emerald', 'GST invoices', 'Exclusive, inclusive or exempt, with per-item GST rates and CGST/SGST split on every bill.'],
    ['box', 'amber', 'Stock & purchases', 'Products, low-stock alerts, suppliers and purchase bills — import by CSV or scan a bill photo.'],
    ['users', 'rose', 'Udhaar ledger', 'Customer credit balances, statements, part payments and WhatsApp reminders for dues.'],
    ['calendar', 'sky', 'Appointments & job cards', 'Book customers, track repairs and jobs from pending to delivered, with warranty tracking.'],
    ['briefcase', 'violet', 'Staff & commissions', 'Assign staff to services, track commission payable, and pay it out with a clear trail.'],
    ['search', 'teal', 'AI photo scan', 'Photograph a menu, rate card or supplier bill — review the extracted items, then save.'],
    ['phone', 'indigo', 'Installable on your phone', 'Add to home screen and bill from a card-tap mobile POS, built for one-handed use.'],
    ['chart', 'emerald', 'Reports & profit', 'Daily sales, top products, income vs expenses and real margin — computed from your own data.'],
    ['chat', 'sky', 'Customer outreach', 'Send review requests and promotions to every customer you have captured at the counter.'],
    ['shield', 'slate', 'Team roles & audit log', 'Owner, admin and staff access, with an activity log of who changed what.'],
    ['cash', 'amber', 'Expenses', 'Track spending by head and see profit after expenses, not just sales.'],
];
$steps = [
    ['Pick your business', 'Choose TradeERP or ServiceERP and your sector. Starter catalogs are ready to import.'],
    ['Add your items', 'Import a CSV, or just photograph your menu or price list and let AI read it.'],
    ['Bill in seconds', 'Use the phone POS at the counter. GST, discounts and part payments are built in.'],
    ['Collect and grow', 'Chase dues on WhatsApp, watch profit, and message customers who keep coming back.'],
];
$faqs = [
    ['Who is TradePOSX for?', 'Shops and service businesses in India — kirana, hardware, jewellery, pharmacy, salons, clinics, repair shops, restaurants and many more. Pick from ' . count($tradeSectors) . ' trade and ' . count($serviceSectors) . ' service sectors.'],
    ['Does it work on my phone?', 'Yes. It is built mobile-first and can be installed on your home screen like an app. The mobile POS is designed for fast, one-handed billing.'],
    ['Can I bring my existing items in?', 'Yes. Upload a CSV, or photograph a printed menu, rate card or supplier bill and review what the AI reads before anything is saved.'],
    ['Does it handle GST?', 'Yes. Invoices can be GST-exclusive, GST-inclusive or exempt, with a per-item rate and a CGST/SGST breakdown. Confirm your rates with your accountant.'],
    ['Is my data separate from other businesses?', 'Yes. Each business is its own workspace and only its own team can see its products, customers and sales.'],
    ['Can I try it first?', 'Yes — the live demo opens a sample business with products, customers, sales and job cards. No signup needed. Demo data is shared and may be reset.'],
];
$tints = ['indigo' => 'bg-indigo-500/15 text-indigo-300', 'emerald' => 'bg-emerald-500/15 text-emerald-300', 'amber' => 'bg-amber-500/15 text-amber-300',
          'rose' => 'bg-rose-500/15 text-rose-300', 'sky' => 'bg-sky-500/15 text-sky-300', 'violet' => 'bg-violet-500/15 text-violet-300',
          'teal' => 'bg-teal-500/15 text-teal-300', 'slate' => 'bg-slate-500/20 text-slate-300'];
?>
<body class="bg-slate-950 text-white antialiased" x-data="{ nav: false }">

  <!-- Nav -->
  <header class="sticky top-0 z-40 bg-slate-950/85 backdrop-blur border-b border-white/5">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
      <a href="/" class="flex items-center gap-2.5 font-bold text-lg tracking-tight">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-emerald-500 flex items-center justify-center text-white"><?= uiIcon('cart', 'w-5 h-5') ?></span>
        <?= h(APP_NAME) ?>
      </a>
      <nav class="hidden md:flex items-center gap-7 text-sm text-slate-300">
        <a href="#engines" class="hover:text-white">TradeERP &amp; ServiceERP</a>
        <a href="#features" class="hover:text-white">Features</a>
        <a href="#how" class="hover:text-white">How it works</a>
        <a href="#faq" class="hover:text-white">FAQ</a>
      </nav>
      <div class="flex items-center gap-2">
        <a href="/login/demo" class="hidden sm:inline-flex text-sm font-medium border border-white/15 hover:bg-white/5 rounded-lg px-4 py-2">Live demo</a>
        <a href="/login" class="text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 rounded-lg px-4 py-2">Login</a>
        <button type="button" @click="nav = !nav" class="md:hidden p-2 -mr-2 text-slate-300" aria-label="Menu"><?= uiIcon('menu', 'w-6 h-6') ?></button>
      </div>
    </div>
    <div x-show="nav" x-cloak class="md:hidden border-t border-white/5 px-4 py-3 text-sm text-slate-300 grid gap-1">
      <a @click="nav=false" href="#engines" class="py-2">TradeERP &amp; ServiceERP</a>
      <a @click="nav=false" href="#features" class="py-2">Features</a>
      <a @click="nav=false" href="#how" class="py-2">How it works</a>
      <a @click="nav=false" href="#faq" class="py-2">FAQ</a>
      <a href="/login/demo" class="py-2 font-semibold text-emerald-300">Try the live demo</a>
    </div>
  </header>

  <!-- Hero -->
  <section class="relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(60% 50% at 80% 0%, rgba(99,102,241,.22), transparent 70%), radial-gradient(50% 40% at 0% 30%, rgba(16,185,129,.12), transparent 70%)"></div>
    <div class="relative max-w-6xl mx-auto px-4 pt-10 pb-14 md:pt-16 md:pb-20 grid md:grid-cols-2 gap-10 items-center">
      <div>
        <span class="inline-flex items-center gap-2 text-[11px] font-semibold tracking-widest uppercase text-indigo-300 bg-indigo-500/10 border border-indigo-400/20 rounded-full px-3 py-1">POS &amp; business management for India</span>
        <h1 class="mt-5 text-4xl sm:text-5xl font-extrabold leading-[1.08] tracking-tight">
          Bill faster. Collect dues.
          <span class="bg-gradient-to-r from-indigo-400 to-emerald-400 bg-clip-text text-transparent">Run it all from your phone.</span>
        </h1>
        <p class="mt-5 text-base sm:text-lg text-slate-300 max-w-xl">One platform for trading shops and service businesses — POS, GST billing, stock, Udhaar, appointments and job cards.</p>
        <ul class="mt-6 space-y-2.5 text-sm sm:text-base text-slate-200">
          <li class="flex items-start gap-2.5"><span class="mt-0.5 text-emerald-400"><?= uiIcon('check', 'w-5 h-5') ?></span>Mobile-first POS you can install on your home screen</li>
          <li class="flex items-start gap-2.5"><span class="mt-0.5 text-emerald-400"><?= uiIcon('check', 'w-5 h-5') ?></span>GST invoices, part payments and Udhaar tracking</li>
          <li class="flex items-start gap-2.5"><span class="mt-0.5 text-emerald-400"><?= uiIcon('check', 'w-5 h-5') ?></span>Photograph a menu or bill — AI fills in your items</li>
        </ul>
        <div class="mt-8 flex flex-col sm:flex-row gap-3">
          <a href="/login/demo" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl px-6 py-3.5 text-base font-semibold shadow-lg shadow-indigo-900/40">Try the live demo <?= uiIcon('arrow', 'w-5 h-5') ?></a>
          <a href="/login" class="inline-flex items-center justify-center border border-white/15 hover:bg-white/5 rounded-xl px-6 py-3.5 text-base font-semibold">Login or register</a>
        </div>
        <p class="mt-4 text-xs text-slate-500">No signup needed to explore — sample business with products, customers and sales.</p>
      </div>

      <!-- Product mock: a phone showing the real dashboard layout -->
      <div class="relative mx-auto w-full max-w-[19rem] md:max-w-sm">
        <div class="absolute -inset-6 rounded-[3rem] bg-gradient-to-br from-indigo-500/20 to-emerald-500/10 blur-2xl"></div>
        <div class="relative rounded-[2.2rem] border-[7px] border-slate-800 bg-slate-100 text-slate-900 shadow-2xl overflow-hidden">
          <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
            <div><div class="text-[11px] font-semibold leading-none">Dashboard</div><div class="text-[9px] text-slate-500 mt-1">Sharma Traders</div></div>
            <div class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">S</div>
          </div>
          <div class="p-3 space-y-2.5">
            <div class="flex gap-2"><div class="flex-1 rounded-lg bg-indigo-600 text-white text-[10px] font-semibold text-center py-2">New Sale</div><div class="flex-1 rounded-lg bg-white border border-slate-200 text-[10px] font-semibold text-center py-2">Quick Sale</div></div>
            <div class="grid grid-cols-2 gap-2">
              <div class="rounded-xl bg-white border border-slate-200 p-2.5"><div class="text-[8px] font-semibold text-slate-500 tracking-wide">TODAY'S SALES</div><div class="text-base font-bold mt-0.5">₹18,450</div><div class="text-[8px] text-slate-500">24 sales</div></div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5"><div class="text-[8px] font-semibold text-slate-500 tracking-wide">UDHAAR PENDING</div><div class="text-base font-bold mt-0.5 text-amber-600">₹42,300</div><div class="text-[8px] text-slate-500">9 customers</div></div>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 p-2.5">
              <div class="text-[9px] font-semibold mb-2">Sales · last 7 days</div>
              <div class="flex items-end gap-1.5 h-16">
                <?php foreach ([35, 52, 44, 70, 58, 88, 64] as $i => $v): ?><div class="flex-1 rounded-t bg-indigo-500 <?= $i === 5 ? '' : 'opacity-70' ?>" style="height: <?= $v ?>%"></div><?php endforeach; ?>
              </div>
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[9px] px-2.5 py-2">3 items low on stock · ₹42,300 Udhaar to collect</div>
          </div>
          <div class="bg-white border-t border-slate-200 grid grid-cols-5 text-[8px] text-slate-500 text-center pt-1.5 pb-2 relative">
            <span class="text-indigo-600 font-semibold">Home</span><span>Products</span><span class="text-indigo-600 font-semibold"><span class="absolute left-1/2 -translate-x-1/2 -top-4 w-9 h-9 rounded-full bg-indigo-600 text-white text-lg flex items-center justify-center shadow">+</span><span class="block pt-4"></span>New Sale</span><span>Customers</span><span>More</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats strip -->
  <section class="border-y border-white/5 bg-white/[0.02]">
    <div class="max-w-6xl mx-auto px-4 py-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
      <div><div class="text-3xl font-bold text-indigo-300"><?= count($tradeSectors) ?></div><div class="text-xs sm:text-sm text-slate-400 mt-1">trade sectors</div></div>
      <div><div class="text-3xl font-bold text-emerald-300"><?= count($serviceSectors) ?></div><div class="text-xs sm:text-sm text-slate-400 mt-1">service sectors</div></div>
      <div><div class="text-3xl font-bold text-sky-300">3</div><div class="text-xs sm:text-sm text-slate-400 mt-1">GST modes per invoice</div></div>
      <div><div class="text-3xl font-bold text-amber-300">1</div><div class="text-xs sm:text-sm text-slate-400 mt-1">login for Trade + Service</div></div>
    </div>
  </section>

  <!-- Two engines -->
  <section id="engines" class="max-w-6xl mx-auto px-4 py-14 md:py-20 scroll-mt-16">
    <div class="max-w-2xl mb-10">
      <div class="text-xs font-semibold tracking-widest uppercase text-indigo-300">One platform, two engines</div>
      <h2 class="mt-2 text-3xl md:text-4xl font-bold tracking-tight">Pick the engine that fits your business</h2>
      <p class="mt-3 text-slate-400">Run a shop, a service business, or both under one login.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="card-trade bg-white/[0.03] border border-white/10 rounded-2xl p-6 md:p-8 flex flex-col transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-11 h-11 rounded-xl bg-indigo-500/15 text-indigo-300 flex items-center justify-center"><?= uiIcon('cart', 'w-6 h-6') ?></div>
          <div><h3 class="text-xl font-semibold">TradeERP</h3><div class="text-xs text-slate-500"><?= count($tradeSectors) ?> trades supported</div></div>
        </div>
        <p class="text-sm text-slate-400 mb-5">For any kind of trading or retail business — kirana stores, hardware shops, wholesalers, and more.</p>
        <ul class="space-y-2.5 mb-6 flex-1">
          <?php foreach ($tradeFeatures as $f): ?><li class="flex items-start gap-2.5 text-sm text-slate-300"><span class="mt-0.5 text-indigo-400"><?= uiIcon('check', 'w-4 h-4') ?></span><span><?= $f ?></span></li><?php endforeach; ?>
        </ul>
        <label class="block text-xs font-medium text-slate-500 mb-1.5">Find your trade</label>
        <select class="w-full bg-white/5 border border-white/10 text-slate-300 text-base md:text-sm rounded-lg px-3 py-2.5 mb-4"><?php foreach ($tradeSectors as $s): ?><option class="bg-slate-900"><?= h($s) ?></option><?php endforeach; ?></select>
        <a href="/login" class="text-center bg-indigo-600 hover:bg-indigo-500 rounded-xl px-4 py-3 text-sm font-semibold">Start with TradeERP</a>
      </div>
      <div class="card-service bg-white/[0.03] border border-white/10 rounded-2xl p-6 md:p-8 flex flex-col transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-300 flex items-center justify-center"><?= uiIcon('wrench', 'w-6 h-6') ?></div>
          <div><h3 class="text-xl font-semibold">ServiceERP</h3><div class="text-xs text-slate-500"><?= count($serviceSectors) ?> services supported</div></div>
        </div>
        <p class="text-sm text-slate-400 mb-5">For any kind of service business — clinics, salons, repair shops, agencies, and more.</p>
        <ul class="space-y-2.5 mb-6 flex-1">
          <?php foreach ($serviceFeatures as $f): ?><li class="flex items-start gap-2.5 text-sm text-slate-300"><span class="mt-0.5 text-emerald-400"><?= uiIcon('check', 'w-4 h-4') ?></span><span><?= $f ?></span></li><?php endforeach; ?>
        </ul>
        <label class="block text-xs font-medium text-slate-500 mb-1.5">Find your service</label>
        <select class="w-full bg-white/5 border border-white/10 text-slate-300 text-base md:text-sm rounded-lg px-3 py-2.5 mb-4"><?php foreach ($serviceSectors as $s): ?><option class="bg-slate-900"><?= h($s) ?></option><?php endforeach; ?></select>
        <a href="/login" class="text-center bg-emerald-600 hover:bg-emerald-500 rounded-xl px-4 py-3 text-sm font-semibold">Start with ServiceERP</a>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section id="features" class="border-t border-white/5 bg-white/[0.015] scroll-mt-16">
    <div class="max-w-6xl mx-auto px-4 py-14 md:py-20">
      <div class="max-w-2xl mb-10">
        <div class="text-xs font-semibold tracking-widest uppercase text-emerald-300">Features</div>
        <h2 class="mt-2 text-3xl md:text-4xl font-bold tracking-tight">Replace the notebook, the calculator and the WhatsApp reminders</h2>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($featureCards as $f): ?>
          <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 hover:border-white/20 transition">
            <div class="w-10 h-10 rounded-xl <?= $tints[$f[1]] ?> flex items-center justify-center mb-4"><?= uiIcon($f[0], 'w-5 h-5') ?></div>
            <h3 class="font-semibold"><?= h($f[2]) ?></h3>
            <p class="mt-1.5 text-sm text-slate-400 leading-relaxed"><?= h($f[3]) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- How it works -->
  <section id="how" class="max-w-6xl mx-auto px-4 py-14 md:py-20 scroll-mt-16">
    <div class="max-w-2xl mb-10">
      <div class="text-xs font-semibold tracking-widest uppercase text-sky-300">How it works</div>
      <h2 class="mt-2 text-3xl md:text-4xl font-bold tracking-tight">From sign-up to first bill in an afternoon</h2>
    </div>
    <ol class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <?php foreach ($steps as $i => $s): ?>
        <li class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
          <div class="text-xs font-semibold text-slate-500">Step <?= $i + 1 ?></div>
          <h3 class="mt-1 font-semibold"><?= h($s[0]) ?></h3>
          <p class="mt-1.5 text-sm text-slate-400 leading-relaxed"><?= h($s[1]) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <!-- Demo band -->
  <section class="max-w-6xl mx-auto px-4 pb-14 md:pb-20">
    <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-indigo-600/25 via-slate-900 to-emerald-600/20 p-8 md:p-12 text-center">
      <h2 class="text-2xl md:text-4xl font-bold tracking-tight">See it with a real-looking business</h2>
      <p class="mt-3 text-slate-300 max-w-xl mx-auto">One tap opens a sample shop with products, customers, sales and job cards. Switch between TradeERP and ServiceERP and try the phone POS.</p>
      <div class="mt-7 flex flex-col sm:flex-row gap-3 justify-center">
        <a href="/login/demo" class="inline-flex items-center justify-center gap-2 bg-white text-slate-900 hover:bg-slate-100 rounded-xl px-7 py-3.5 font-semibold">Open the live demo <?= uiIcon('arrow', 'w-5 h-5') ?></a>
        <a href="/login" class="inline-flex items-center justify-center border border-white/20 hover:bg-white/5 rounded-xl px-7 py-3.5 font-semibold">Create my business</a>
      </div>
      <p class="mt-4 text-xs text-slate-500">Demo data is shared and may be reset. Please don't enter real customer information.</p>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="max-w-3xl mx-auto px-4 pb-16 md:pb-24 scroll-mt-16">
    <div class="text-xs font-semibold tracking-widest uppercase text-amber-300">FAQ</div>
    <h2 class="mt-2 mb-8 text-3xl md:text-4xl font-bold tracking-tight">Questions owners ask</h2>
    <div class="space-y-3">
      <?php foreach ($faqs as $q): ?>
        <details class="group rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-4 open:bg-white/[0.05]">
          <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-semibold">
            <?= h($q[0]) ?><span class="text-slate-400 transition group-open:rotate-180"><?= uiIcon('chevron', 'w-5 h-5') ?></span>
          </summary>
          <p class="mt-3 text-sm text-slate-400 leading-relaxed"><?= h($q[1]) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Footer -->
  <footer class="border-t border-white/10 bg-slate-950">
    <div class="max-w-6xl mx-auto px-4 pt-12 pb-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

        <div>
          <div class="text-xs font-bold tracking-widest uppercase text-indigo-300">Operated &amp; developed by</div>
          <div class="mt-3 text-xl font-bold">M/s Deinrim Solutionss (P) Ltd.</div>
          <div class="mt-3 text-sm text-slate-400">Kolkata, West Bengal (WB), India</div>
          <div class="mt-4 text-sm"><span class="font-semibold text-white">Corporate Contact:</span> <a href="tel:+919836130393" class="font-semibold text-emerald-300 hover:text-emerald-200">+91 98361-30393</a></div>
          <div class="mt-1 text-sm"><a href="https://denrinsolutionss.com/" target="_blank" rel="noopener" class="text-slate-400 hover:text-white underline underline-offset-4 decoration-white/20">denrinsolutionss.com</a></div>
          <div class="mt-5 pt-4 border-t border-white/10 text-[11px] leading-relaxed text-slate-500 uppercase tracking-wide"><?= h(APP_NAME) ?> · TradeERP + ServiceERP · Mobile-first POS &amp; business management with one separate workspace for every business</div>
        </div>

        <div>
          <div class="text-xs font-bold tracking-widest uppercase text-indigo-300">Policies &amp; help</div>
          <p class="mt-3 text-sm text-slate-400 leading-relaxed">How we handle your data, the terms of using <?= h(APP_NAME) ?>, where to get help, and how to ask for your data to be deleted.</p>
          <div class="mt-4 flex flex-wrap items-center gap-x-1 gap-y-2 text-sm font-semibold">
            <a href="/privacy" class="text-emerald-300 hover:text-emerald-200 underline underline-offset-4 decoration-emerald-300/30">Privacy Policy</a><span class="text-slate-600">|</span>
            <a href="/terms" class="text-emerald-300 hover:text-emerald-200 underline underline-offset-4 decoration-emerald-300/30">Terms of Service</a><span class="text-slate-600">|</span>
            <a href="/support" class="text-emerald-300 hover:text-emerald-200 underline underline-offset-4 decoration-emerald-300/30">App Support Page</a><span class="text-slate-600">|</span>
            <a href="/deletion" class="text-rose-300 hover:text-rose-200 underline underline-offset-4 decoration-rose-300/30">Data Deletion request</a>
          </div>
        </div>

        <div>
          <div class="text-xs font-bold tracking-widest uppercase text-indigo-300">Your data &amp; security</div>
          <ul class="mt-3 space-y-3 text-sm text-slate-400 leading-relaxed">
            <li class="flex gap-2.5"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span><span><b class="text-white font-semibold">Separate workspace:</b> every business sees only its own products, customers and sales.</span></li>
            <li class="flex gap-2.5"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span><span><b class="text-white font-semibold">Role-based access:</b> owner, admin and staff roles, with an activity log of who changed what.</span></li>
            <li class="flex gap-2.5"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span><span><b class="text-white font-semibold">Your data stays yours:</b> download a full backup of your business any time from Settings.</span></li>
          </ul>
        </div>
      </div>

      <div class="mt-10 pt-6 border-t border-white/10 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
        <div class="flex flex-wrap items-center justify-center md:justify-start gap-x-3 gap-y-1">
          <span>&copy; <?= date('Y') ?> <b class="text-slate-300"><?= h(APP_NAME) ?></b> (tradeposx.com)</span>
          <span class="hidden sm:inline text-slate-700">&bull;</span>
          <span class="font-semibold text-indigo-300">Bill faster. Track everything. Grow steadier.</span>
        </div>
        <div class="flex items-center gap-4">
          <span class="flex items-center gap-1.5 font-semibold text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Encrypted connection (HTTPS)</span>
          <span class="hidden sm:inline text-slate-700">&bull;</span>
          <span>Made for Indian businesses</span>
        </div>
      </div>
    </div>
  </footer>

</body>
</html>
