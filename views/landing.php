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

      <!-- Product tour: ten real screens of the app in a phone frame (sample data) -->
      <?php
        $tour = [
          ['Dashboard',        'Today’s sales, Udhaar due and low stock at a glance.'],
          ['Stock',            'Live stock with low-stock alerts — import by CSV or scan a bill.'],
          ['Cash memo',        'Speak or type each item, check the box, tap Add.'],
          ['The bill',         'GST split, discounts and part payments in one cart.'],
          ['Pay by UPI',       'Customer scans the QR, you confirm — the bill is generated.'],
          ['Final GST bill',   'A proper tax invoice, ready to print or send on WhatsApp.'],
          ['Udhaar ledger',    'Dues by customer, with one-tap WhatsApp reminders.'],
          ['Job cards',        'Track every repair from pending to delivered.'],
          ['Reports & profit', 'Sales, expenses and real profit from your own data.'],
          ['Quick Sale',       'One-handed mobile POS that keeps working offline.'],
        ];
        // Illustrative QR-style graphic for the UPI slide (not a scannable code).
        mt_srand(11); $qrN = 25; $qr = [];
        for ($y = 0; $y < $qrN; $y++) for ($x = 0; $x < $qrN; $x++) {
            $inFinder = ($x < 8 && $y < 8) || ($x >= $qrN - 8 && $y < 8) || ($x < 8 && $y >= $qrN - 8);
            $qr[$y][$x] = $inFinder ? 0 : (mt_rand(0, 99) < 48 ? 1 : 0);
        }
        $finder = function (int $ox, int $oy) {
            $s = '<rect x="' . $ox . '" y="' . $oy . '" width="7" height="7" fill="#0f172a"/><rect x="' . ($ox + 1) . '" y="' . ($oy + 1) . '" width="5" height="5" fill="#fff"/><rect x="' . ($ox + 2) . '" y="' . ($oy + 2) . '" width="3" height="3" fill="#0f172a"/>';
            return $s;
        };
        $qrSvg = '<svg viewBox="0 0 ' . $qrN . ' ' . $qrN . '" class="w-full h-full" shape-rendering="crispEdges" aria-hidden="true">';
        for ($y = 0; $y < $qrN; $y++) for ($x = 0; $x < $qrN; $x++) if ($qr[$y][$x]) $qrSvg .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1" fill="#0f172a"/>';
        $qrSvg .= $finder(0, 0) . $finder($qrN - 7, 0) . $finder(0, $qrN - 7) . '</svg>';
      ?>
      <div class="relative mx-auto w-full max-w-[19rem] md:max-w-sm"
           x-data="{ i: 0, n: <?= count($tour) ?>, paused: false, timer: null,
                     go(k) { this.i = (k + this.n) % this.n; },
                     init() { if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return; this.timer = setInterval(() => { if (!this.paused) this.go(this.i + 1); }, 4500); } }"
           @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
           @keydown.left.prevent="go(i - 1)" @keydown.right.prevent="go(i + 1)" role="region" aria-roledescription="carousel" aria-label="TradePOSX screens">
        <div class="absolute -inset-6 rounded-[3rem] bg-gradient-to-br from-indigo-500/20 to-emerald-500/10 blur-2xl"></div>

        <div class="relative rounded-[2.2rem] border-[7px] border-slate-800 bg-slate-100 text-slate-900 shadow-2xl overflow-hidden h-[34rem]">

          <!-- 1 · Dashboard -->
          <div :class="i === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 0" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">Dashboard</div><div class="text-[9px] text-slate-500 mt-1">Sharma Traders</div></div>
              <div class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">S</div>
            </div>
            <div class="flex-1 p-3 space-y-2.5 overflow-hidden">
              <div class="flex gap-2"><div class="flex-1 rounded-lg bg-indigo-600 text-white text-[10px] font-semibold text-center py-2">New Sale</div><div class="flex-1 rounded-lg bg-white border border-slate-200 text-[10px] font-semibold text-center py-2">Quick Sale</div></div>
              <div class="grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-white border border-slate-200 p-2.5"><div class="text-[8px] font-semibold text-slate-500 tracking-wide">TODAY'S SALES</div><div class="text-base font-bold mt-0.5">₹18,450</div><div class="text-[8px] text-slate-500">24 sales</div></div>
                <div class="rounded-xl bg-white border border-slate-200 p-2.5"><div class="text-[8px] font-semibold text-slate-500 tracking-wide">UDHAAR PENDING</div><div class="text-base font-bold mt-0.5 text-amber-600">₹42,300</div><div class="text-[8px] text-slate-500">9 customers</div></div>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5">
                <div class="text-[9px] font-semibold mb-2">Sales · last 7 days</div>
                <div class="flex items-end gap-1.5 h-20">
                  <?php foreach ([35, 52, 44, 70, 58, 88, 64] as $k => $v): ?><div class="flex-1 rounded-t bg-indigo-500 <?= $k === 5 ? '' : 'opacity-70' ?>" style="height: <?= $v ?>%"></div><?php endforeach; ?>
                </div>
                <div class="flex justify-between text-[7px] text-slate-400 mt-1"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5 text-[9px] space-y-1.5">
                <div class="font-semibold">Top sellers today</div>
                <div class="flex justify-between"><span>Aashirvaad Atta 5kg</span><span class="font-semibold">38 sold</span></div>
                <div class="flex justify-between"><span>Fortune Oil 1L</span><span class="font-semibold">27 sold</span></div>
                <div class="flex justify-between"><span>Parle-G 100g</span><span class="font-semibold">24 sold</span></div>
              </div>
              <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[9px] px-2.5 py-2">3 items low on stock · ₹42,300 Udhaar to collect</div>
            </div>
            <div class="bg-white border-t border-slate-200 grid grid-cols-5 text-[8px] text-slate-500 text-center pt-1.5 pb-2 relative">
              <span class="text-indigo-600 font-semibold">Home</span><span>Products</span><span class="text-indigo-600 font-semibold"><span class="absolute left-1/2 -translate-x-1/2 -top-4 w-9 h-9 rounded-full bg-indigo-600 text-white text-lg flex items-center justify-center shadow">+</span><span class="block pt-4"></span>New Sale</span><span>Customers</span><span>More</span>
            </div>
          </div>

          <!-- 2 · Stock -->
          <div :class="i === 1 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 1" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">Products &amp; stock</div><div class="text-[9px] text-slate-500 mt-1">128 items · 3 running low</div></div>
              <div class="rounded-lg bg-indigo-600 text-white text-[9px] font-semibold px-2.5 py-1.5">+ Add</div>
            </div>
            <div class="flex-1 p-3 space-y-2.5 overflow-hidden">
              <div class="rounded-lg bg-white border border-slate-200 px-2.5 py-2 text-[9px] text-slate-400">Search by name, SKU or scan a barcode…</div>
              <div class="flex gap-1.5 text-[8px] font-semibold"><span class="rounded-full bg-indigo-600 text-white px-2.5 py-1">All</span><span class="rounded-full bg-white border border-slate-200 px-2.5 py-1">Low stock</span><span class="rounded-full bg-white border border-slate-200 px-2.5 py-1">Grocery</span><span class="rounded-full bg-white border border-slate-200 px-2.5 py-1">Dairy</span></div>
              <div class="rounded-xl bg-white border border-slate-200 divide-y divide-slate-100">
                <?php foreach ([
                    ['Aashirvaad Atta 5kg', 'SKU ATA-5 · pack', '42', 'emerald', '₹245'],
                    ['Fortune Soya Oil 1L', 'SKU OIL-1 · pouch', '8', 'amber', '₹138'],
                    ['Tata Salt 1kg', 'SKU SLT-1 · pack', '120', 'emerald', '₹28'],
                    ['Surf Excel 500g', 'SKU SRF-5 · pack', '3', 'rose', '₹118'],
                    ['Parle-G 100g', 'SKU PLG-1 · pack', '64', 'emerald', '₹10'],
                    ['Amul Butter 100g', 'SKU AMB-1 · pack', '17', 'emerald', '₹58'],
                ] as $r): ?>
                <div class="flex items-center justify-between px-2.5 py-2">
                  <div><div class="text-[10px] font-semibold"><?= $r[0] ?></div><div class="text-[8px] text-slate-500"><?= $r[1] ?></div></div>
                  <div class="text-right"><div class="text-[10px] font-semibold"><?= $r[4] ?></div><div class="text-[8px] font-semibold rounded px-1.5 py-0.5 mt-0.5 bg-<?= $r[3] ?>-100 text-<?= $r[3] ?>-700"><?= $r[2] ?> in stock</div></div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[9px] px-2.5 py-2">Surf Excel is almost out — reorder from your supplier.</div>
            </div>
          </div>

          <!-- 3 · Cash memo (voice) -->
          <div :class="i === 2 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 2" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">New Sale</div><div class="text-[9px] text-slate-500 mt-1">Cash memo</div></div>
              <div class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">S</div>
            </div>
            <div class="flex-1 p-3 space-y-2.5 overflow-hidden">
              <div class="flex gap-1.5 text-[9px] font-semibold"><span class="flex-1 text-center rounded-lg bg-white border border-slate-200 py-1.5 text-slate-500">Stock available</span><span class="flex-1 text-center rounded-lg bg-indigo-600 text-white py-1.5">🎤 Cash memo</span></div>
              <div class="rounded-xl border border-indigo-200 bg-gradient-to-b from-indigo-50 to-white p-2.5">
                <div class="flex gap-1.5 items-stretch">
                  <span class="rounded-lg bg-red-600 text-white text-[9px] font-semibold px-2.5 flex items-center">⏹ Listening</span>
                  <span class="flex-1 rounded-lg bg-white border border-slate-300 px-2 py-2 text-[10px]">Cold coffee 2 50<span class="inline-block w-px h-3 bg-slate-500 align-middle ml-0.5"></span></span>
                  <span class="rounded-lg bg-emerald-600 text-white text-[9px] font-semibold px-3 flex items-center">Add</span>
                </div>
                <div class="text-[8px] text-slate-500 mt-1.5">Say <b>item, quantity, rate</b> — “Coffee 2 50” — then tap Add.</div>
              </div>
              <div class="text-[9px] text-emerald-700">Added: “biscuit 12 25” — say the next item, or tap Add to cart when done.</div>
              <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
                <div class="grid grid-cols-[1fr_2rem_2.4rem_3rem] gap-1 px-2.5 py-1.5 bg-slate-50 text-[7px] font-semibold tracking-wide text-slate-500"><span>ITEM</span><span class="text-right">QTY</span><span class="text-right">RATE</span><span class="text-right">AMOUNT</span></div>
                <?php foreach ([['Rice (kg)', '0.5', '60', '30.00'], ['Biscuit', '12', '25', '300.00'], ['Cold coffee', '2', '50', '100.00'], ['Basin mixer', '2', '2,500', '5,000.00']] as $r): ?>
                <div class="grid grid-cols-[1fr_2rem_2.4rem_3rem] gap-1 px-2.5 py-1.5 text-[9px] border-t border-slate-100"><span class="font-medium"><?= $r[0] ?></span><span class="text-right"><?= $r[1] ?></span><span class="text-right"><?= $r[2] ?></span><span class="text-right font-semibold">₹<?= $r[3] ?></span></div>
                <?php endforeach; ?>
                <div class="flex justify-between px-2.5 py-2 bg-slate-50 border-t border-slate-200 text-[9px]"><span class="text-indigo-600 font-semibold">+ Add row</span><span>Memo total <b>₹5,430.00</b></span></div>
              </div>
              <div class="rounded-lg bg-indigo-600 text-white text-[10px] font-semibold text-center py-2.5">Add to cart →</div>
              <div class="text-[8px] text-slate-500 text-center">Units too: pcs, kg, gram, litre, dozen, metre…</div>
            </div>
          </div>

          <!-- 4 · The bill -->
          <div :class="i === 3 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 3" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">Cart</div><div class="text-[9px] text-slate-500 mt-1">3 items</div></div>
              <div class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">S</div>
            </div>
            <div class="flex-1 p-3 space-y-2.5 overflow-hidden">
              <div class="rounded-xl bg-white border border-slate-200 divide-y divide-slate-100">
                <?php foreach ([['Aashirvaad Atta 5kg', '2', '245.00', '490.00'], ['Fortune Soya Oil 1L', '1', '138.00', '138.00'], ['Parle-G 100g', '6', '10.00', '60.00']] as $r): ?>
                <div class="flex items-center justify-between px-2.5 py-2">
                  <div><div class="text-[10px] font-semibold"><?= $r[0] ?></div><div class="text-[8px] text-slate-500">₹<?= $r[2] ?> each · price editable</div></div>
                  <div class="flex items-center gap-2"><span class="rounded border border-slate-300 w-8 text-center text-[10px] py-0.5"><?= $r[1] ?></span><span class="text-[10px] font-semibold w-14 text-right">₹<?= $r[3] ?></span></div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5 text-[9px] space-y-1">
                <div class="flex justify-between text-slate-600"><span>Taxable amount</span><span>₹688.00</span></div>
                <div class="flex justify-between text-slate-600"><span>CGST @ 2.5%</span><span>₹17.20</span></div>
                <div class="flex justify-between text-slate-600"><span>SGST @ 2.5%</span><span>₹17.20</span></div>
                <div class="flex justify-between text-slate-600"><span>Discount</span><span>– ₹0.00</span></div>
                <div class="flex justify-between text-[13px] font-bold pt-1.5 border-t border-slate-200"><span>Grand total</span><span>₹722.40</span></div>
              </div>
              <div class="grid grid-cols-2 gap-1.5 text-[9px] font-semibold">
                <span class="rounded-lg border border-slate-300 bg-white px-2 py-2">Ravi Kumar · Walk-in</span><span class="rounded-lg border border-slate-300 bg-white px-2 py-2">Paid in full ▾</span>
              </div>
              <div class="flex gap-1.5 text-[9px] font-semibold"><span class="flex-1 text-center rounded-lg border border-slate-300 bg-white py-1.5 text-slate-500">Cash</span><span class="flex-1 text-center rounded-lg bg-indigo-600 text-white py-1.5">UPI</span><span class="flex-1 text-center rounded-lg border border-slate-300 bg-white py-1.5 text-slate-500">Bank</span></div>
              <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[9px] px-2.5 py-1.5">Part payment? Take ₹200 now — the balance goes to Udhaar.</div>
              <div class="rounded-lg bg-indigo-600 text-white text-[10px] font-semibold text-center py-2.5">Complete Sale</div>
            </div>
          </div>

          <!-- 5 · Pay by UPI -->
          <div :class="i === 4 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 4" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-300">
            <div class="bg-white/80 px-4 pt-4 pb-3 border-b border-slate-200 opacity-60"><div class="text-[11px] font-semibold leading-none">Cart</div><div class="text-[9px] text-slate-500 mt-1">UPI selected</div></div>
            <div class="flex-1 flex items-end justify-center p-2.5">
              <div class="w-full rounded-2xl bg-white shadow-2xl p-3.5 text-center">
                <div class="text-[9px] font-medium text-slate-500">Scan to pay with any UPI app</div>
                <div class="text-[26px] leading-none font-bold mt-1">₹722.40</div>
                <div class="mx-auto mt-2.5 w-36 h-36 p-1.5 border border-slate-200 rounded-lg"><?= $qrSvg ?></div>
                <div class="text-[8px] text-slate-500 mt-1.5">Paying <b class="text-slate-700">Sharma Traders</b><br><span class="font-mono">sharmatraders@okhdfcbank</span></div>
                <div class="mt-2 rounded-lg border border-indigo-300 text-indigo-700 text-[9px] font-medium py-1.5">Open in my UPI app</div>
                <div class="mt-1.5 rounded-lg bg-emerald-600 text-white text-[10px] font-semibold py-2.5">✓ Payment received — Generate bill</div>
                <div class="text-[8px] text-slate-400 mt-1.5">Cancel</div>
              </div>
            </div>
          </div>

          <!-- 6 · Final GST bill -->
          <div :class="i === 5 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 5" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">Tax Invoice</div><div class="text-[9px] text-slate-500 mt-1">SL-00034 · 10 Oct 2026</div></div>
              <div class="rounded-full bg-emerald-100 text-emerald-700 text-[8px] font-bold px-2 py-1">PAID · UPI</div>
            </div>
            <div class="flex-1 p-3 overflow-hidden">
              <div class="rounded-xl bg-white border border-slate-200 p-3 text-[9px] space-y-2">
                <div class="text-center"><div class="text-[12px] font-bold">Sharma Traders</div><div class="text-[8px] text-slate-500">Bara Bazar, Kolkata · GSTIN 19ABCDE1234F1Z5</div></div>
                <div class="flex justify-between text-[8px] text-slate-500"><span>Billed to: <b class="text-slate-700">Ravi Kumar</b></span><span>Mode: UPI</span></div>
                <div class="border-t border-dashed border-slate-300 pt-2 space-y-1">
                  <div class="flex justify-between"><span>2 × Aashirvaad Atta 5kg</span><span>₹490.00</span></div>
                  <div class="flex justify-between"><span>1 × Fortune Soya Oil 1L</span><span>₹138.00</span></div>
                  <div class="flex justify-between"><span>6 × Parle-G 100g</span><span>₹60.00</span></div>
                </div>
                <div class="border-t border-dashed border-slate-300 pt-2 space-y-1 text-slate-600">
                  <div class="flex justify-between"><span>Taxable value</span><span>₹688.00</span></div>
                  <div class="flex justify-between"><span>CGST 2.5%</span><span>₹17.20</span></div>
                  <div class="flex justify-between"><span>SGST 2.5%</span><span>₹17.20</span></div>
                </div>
                <div class="flex justify-between text-[13px] font-bold border-t border-slate-300 pt-2"><span>Total</span><span>₹722.40</span></div>
                <div class="flex justify-between text-slate-600"><span>Received via UPI</span><span class="text-emerald-600 font-semibold">₹722.40</span></div>
                <div class="flex justify-between text-slate-600"><span>Balance due</span><span>₹0.00</span></div>
                <div class="text-center text-[8px] text-slate-500">Thank you for shopping with us!</div>
              </div>
              <div class="grid grid-cols-2 gap-2 mt-2.5 text-[10px] font-semibold">
                <span class="rounded-lg bg-green-600 text-white text-center py-2.5">💬 WhatsApp bill</span><span class="rounded-lg bg-white border border-slate-300 text-center py-2.5">Print / PDF</span>
              </div>
              <div class="text-[8px] text-slate-500 text-center mt-2">Stock updated automatically · Edit or delete from the invoice list</div>
            </div>
          </div>

          <!-- 7 · Udhaar -->
          <div :class="i === 6 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 6" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">Customers · Udhaar</div><div class="text-[9px] text-slate-500 mt-1">9 customers owe you</div></div>
              <div class="text-[11px] font-bold text-amber-600">₹42,300</div>
            </div>
            <div class="flex-1 p-3 space-y-2.5 overflow-hidden">
              <div class="rounded-xl bg-white border border-slate-200 divide-y divide-slate-100">
                <?php foreach ([['Ravi Kumar', '12 days overdue', '₹8,400'], ['Meena Stores', 'Due this week', '₹12,950'], ['Anil Sharma', '3 days overdue', '₹4,200'], ['Gupta Traders', 'Due this week', '₹9,800'], ['Sneha Reddy', 'Part-paid', '₹2,430'], ['Pooja Iyer', 'Due in 5 days', '₹3,520']] as $r): ?>
                <div class="flex items-center justify-between px-2.5 py-2">
                  <div><div class="text-[10px] font-semibold"><?= $r[0] ?></div><div class="text-[8px] text-slate-500"><?= $r[1] ?></div></div>
                  <div class="flex items-center gap-2"><span class="text-[10px] font-bold text-amber-600"><?= $r[2] ?></span><span class="rounded-md bg-green-600 text-white text-[8px] font-semibold px-1.5 py-1">💬 Remind</span></div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5 text-[9px] space-y-1">
                <div class="font-semibold">Ravi Kumar · statement</div>
                <div class="flex justify-between text-slate-600"><span>Sale SL-00031 · 28 Sep</span><span>+ ₹5,000</span></div>
                <div class="flex justify-between text-slate-600"><span>Payment received</span><span class="text-emerald-600">– ₹1,200</span></div>
                <div class="flex justify-between text-slate-600"><span>Sale SL-00033 · 01 Oct</span><span>+ ₹4,600</span></div>
              </div>
              <div class="grid grid-cols-2 gap-2 text-[10px] font-semibold"><span class="rounded-lg bg-white border border-slate-300 text-center py-2.5">Record a payment</span><span class="rounded-lg bg-green-600 text-white text-center py-2.5">💬 Remind all</span></div>
            </div>
          </div>

          <!-- 8 · Job cards -->
          <div :class="i === 7 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 7" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">Job cards</div><div class="text-[9px] text-slate-500 mt-1">ServiceERP · Mobile repair</div></div>
              <div class="rounded-lg bg-emerald-600 text-white text-[9px] font-semibold px-2.5 py-1.5">+ New job</div>
            </div>
            <div class="flex-1 p-3 space-y-2.5 overflow-hidden">
              <div class="grid grid-cols-4 gap-1.5 text-center">
                <?php foreach ([['Pending', '4', 'slate'], ['Working', '3', 'sky'], ['Ready', '2', 'emerald'], ['Delivered', '31', 'indigo']] as $r): ?>
                <div class="rounded-lg bg-white border border-slate-200 py-1.5"><div class="text-[12px] font-bold text-<?= $r[2] ?>-600"><?= $r[1] ?></div><div class="text-[7px] text-slate-500"><?= $r[0] ?></div></div>
                <?php endforeach; ?>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 divide-y divide-slate-100">
                <?php foreach ([
                    ['JC-0042', 'Redmi Note 11 · Screen replacement', 'Ready', 'emerald', '₹1,800'],
                    ['JC-0041', 'Samsung M31 · Battery', 'Working', 'sky', '₹900'],
                    ['JC-0040', 'iPhone 11 · Charging port', 'Pending', 'slate', '₹600'],
                    ['JC-0039', 'Vivo Y20 · Water damage', 'Working', 'sky', '₹1,500'],
                    ['JC-0038', 'OnePlus Nord · Speaker repair', 'Ready', 'emerald', '₹700'],
                    ['JC-0037', 'Realme 8 · Software reinstall', 'Delivered', 'indigo', '₹500'],
                ] as $r): ?>
                <div class="px-2.5 py-2">
                  <div class="flex items-center justify-between"><span class="text-[9px] font-semibold"><?= $r[0] ?></span><span class="text-[8px] font-bold rounded-full px-2 py-0.5 bg-<?= $r[3] ?>-100 text-<?= $r[3] ?>-700"><?= $r[2] ?></span></div>
                  <div class="flex items-center justify-between mt-0.5"><span class="text-[9px] text-slate-600"><?= $r[1] ?></span><span class="text-[9px] font-semibold"><?= $r[4] ?></span></div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5 text-[9px] space-y-1">
                <div class="font-semibold">JC-0042 · Ready for pickup</div>
                <div class="text-slate-600">Technician: Imran · 90-day warranty</div>
                <div class="rounded-md bg-green-600 text-white text-[9px] font-semibold text-center py-1.5 mt-1">💬 Tell customer it is ready</div>
              </div>
            </div>
          </div>

          <!-- 9 · Reports -->
          <div :class="i === 8 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 8" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-white px-4 pt-4 pb-3 flex items-center justify-between border-b border-slate-200">
              <div><div class="text-[11px] font-semibold leading-none">Reports</div><div class="text-[9px] text-slate-500 mt-1">This month</div></div>
              <div class="rounded-full bg-slate-100 text-[8px] font-semibold px-2.5 py-1">Oct 2026 ▾</div>
            </div>
            <div class="flex-1 p-3 space-y-2.5 overflow-hidden">
              <div class="grid grid-cols-3 gap-1.5">
                <div class="rounded-xl bg-white border border-slate-200 p-2"><div class="text-[7px] font-semibold text-slate-500 tracking-wide">SALES</div><div class="text-[12px] font-bold">₹4.62L</div></div>
                <div class="rounded-xl bg-white border border-slate-200 p-2"><div class="text-[7px] font-semibold text-slate-500 tracking-wide">EXPENSES</div><div class="text-[12px] font-bold text-rose-600">₹1.08L</div></div>
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-2"><div class="text-[7px] font-semibold text-emerald-700 tracking-wide">PROFIT</div><div class="text-[12px] font-bold text-emerald-700">₹1.94L</div></div>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5">
                <div class="text-[9px] font-semibold mb-2">Sales vs expenses</div>
                <div class="flex items-end gap-2 h-24">
                  <?php foreach ([[60, 22], [72, 26], [55, 20], [84, 30], [78, 24]] as $k => $p): ?>
                  <div class="flex-1 flex items-end gap-0.5 h-full"><div class="flex-1 rounded-t bg-indigo-500" style="height: <?= $p[0] ?>%"></div><div class="flex-1 rounded-t bg-rose-400" style="height: <?= $p[1] ?>%"></div></div>
                  <?php endforeach; ?>
                </div>
                <div class="flex justify-between text-[7px] text-slate-400 mt-1 px-1"><span>W1</span><span>W2</span><span>W3</span><span>W4</span><span>W5</span></div>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5 text-[9px] space-y-1">
                <div class="font-semibold mb-1">Expenses by head</div>
                <div class="flex justify-between"><span>Shop rent</span><span class="font-semibold">₹45,000</span></div>
                <div class="flex justify-between"><span>Staff salary</span><span class="font-semibold">₹38,000</span></div>
                <div class="flex justify-between"><span>Electricity</span><span class="font-semibold">₹9,400</span></div>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-2.5 text-[9px] space-y-2">
                <div class="font-semibold">Top products by profit</div>
                <?php foreach ([['Aashirvaad Atta 5kg', 88], ['Fortune Soya Oil 1L', 64], ['Amul Butter 100g', 47]] as $r): ?>
                <div><div class="flex justify-between"><span><?= $r[0] ?></span><span class="font-semibold">₹<?= $r[1] ?>00</span></div><div class="h-1.5 rounded bg-slate-100 mt-0.5"><div class="h-1.5 rounded bg-emerald-500" style="width: <?= $r[1] ?>%"></div></div></div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- 10 · Quick Sale (offline) -->
          <div :class="i === 9 ? 'opacity-100' : 'opacity-0 pointer-events-none'" :aria-hidden="i !== 9" class="transition-opacity duration-300 absolute inset-0 flex flex-col bg-slate-100">
            <div class="bg-slate-900 text-white px-4 pt-4 pb-3 flex items-center justify-between">
              <div class="text-[11px] font-semibold">Quick Sale</div>
              <div class="text-[9px] flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Offline</div>
            </div>
            <div class="flex-1 p-2.5 space-y-2 overflow-hidden">
              <div class="rounded-lg bg-amber-100 text-amber-800 text-[9px] font-medium px-2.5 py-1.5">No internet — 2 sales saved, they sync automatically.</div>
              <div class="rounded-lg bg-white border border-slate-300 px-2.5 py-2 text-[10px] text-slate-400">Search items…</div>
              <div class="grid grid-cols-2 gap-2">
                <?php foreach ([['Aashirvaad Atta 5kg', '₹245', '42 left'], ['Fortune Soya Oil 1L', '₹138', '8 left'], ['Tata Salt 1kg', '₹28', '120 left'], ['Surf Excel 500g', '₹118', '3 left'], ['Parle-G 100g', '₹10', '64 left'], ['Amul Butter 100g', '₹58', '17 left'], ['Maggi Noodles 70g', '₹14', '52 left'], ['Colgate 100g', '₹55', '30 left'], ['Amul Milk 500ml', '₹29', '24 left'], ['Britannia Bread', '₹45', '11 left']] as $k => $r): ?>
                <div class="rounded-xl bg-white border <?= $k === 0 ? 'border-indigo-400 ring-2 ring-indigo-200' : 'border-slate-200' ?> p-2.5">
                  <div class="text-[10px] font-semibold leading-tight"><?= $r[0] ?></div>
                  <div class="flex items-center justify-between mt-1.5"><span class="text-[11px] font-bold text-indigo-700"><?= $r[1] ?></span><span class="text-[8px] text-slate-500"><?= $r[2] ?></span></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="m-2.5 rounded-xl bg-indigo-600 text-white flex items-center justify-between px-3 py-2.5 shadow-lg">
              <span class="text-[10px] font-semibold">3 items · ₹411</span><span class="text-[10px] font-bold">View cart →</span>
            </div>
          </div>
        </div>

        <!-- Caption + controls -->
        <div class="mt-5 text-center min-h-[3.5rem]" aria-live="polite">
          <?php foreach ($tour as $k => $t): ?>
            <div x-show="i === <?= $k ?>" <?= $k ? 'x-cloak' : '' ?>>
              <div class="text-sm font-semibold"><span class="text-indigo-300"><?= $k + 1 ?>/<?= count($tour) ?></span> · <?= h($t[0]) ?></div>
              <div class="text-xs text-slate-400 mt-0.5"><?= h($t[1]) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3 flex items-center justify-center gap-3">
          <button type="button" @click="go(i - 1)" class="w-8 h-8 rounded-full border border-white/15 hover:bg-white/10 text-slate-300 flex items-center justify-center" aria-label="Previous screen">‹</button>
          <div class="flex items-center gap-1.5">
            <?php foreach ($tour as $k => $t): ?>
              <button type="button" @click="go(<?= $k ?>)" :class="i === <?= $k ?> ? 'w-5 bg-indigo-400' : 'w-1.5 bg-white/25 hover:bg-white/50'" class="h-1.5 rounded-full transition-all" aria-label="Show screen <?= $k + 1 ?>: <?= h($t[0]) ?>"></button>
            <?php endforeach; ?>
          </div>
          <button type="button" @click="go(i + 1)" class="w-8 h-8 rounded-full border border-white/15 hover:bg-white/10 text-slate-300 flex items-center justify-center" aria-label="Next screen">›</button>
        </div>
        <div class="mt-2 text-center text-[10px] text-slate-600">Sample business and sample data</div>
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
