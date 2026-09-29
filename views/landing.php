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
$serviceSectors = [
    'Healthcare Clinic','Doctor Clinic','Diagnostic Centre','Dental Clinic','Hospital',
    'Salon & Beauty','Spa & Wellness','Gym & Fitness','Yoga Centre','Legal Services / Lawyer',
    'CA & Accounting','Business Consultant','Tuition & Home Tutor','Coaching Centre','Repair Shop',
    'AC Service & Repair','RO & Water Purifier Service','Automobile Garage','Car Wash & Detailing','Electrician Services',
    'Plumbing Services','Pest Control','Software & IT Services','Digital Marketing Agency','Advertising & Creative Agency',
    'Computer AMC & Support','Photography & Videography','Printing Press','Event Management','Hotel & Guesthouse',
    'Restaurant & Cafe','Travel Agency','Real Estate','Construction & Contractor','Laundry & Dry Cleaning',
    'Security Agency','Agriculture Services','Courier & Logistics','Finance & Loans','Govt CSC / e-Seva Centre',
    'Religious Trust / Temple Services','Entertainment & Sports','General Service','Interior Design','Architecture Services',
    'Tailoring & Boutique','Driving School','Veterinary Clinic','Home Cleaning Services','Catering Services',
];

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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(APP_NAME) ?> — POS &amp; Business Management for Every Trade</title>
<link rel="manifest" href="/assets/manifest.webmanifest">
<link rel="icon" href="/assets/img/icons/icon-192.png">
<meta name="theme-color" content="#4f46e5">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  .card-trade:hover { border-color: rgba(99,102,241,0.5); }
  .card-service:hover { border-color: rgba(16,185,129,0.5); }
</style>
</head>
<body class="bg-slate-950 text-white">

  <!-- Nav -->
  <header class="border-b border-white/5">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="font-bold text-lg tracking-tight"><?= h(APP_NAME) ?></div>
      <a href="/login" class="text-sm font-medium bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg px-4 py-2">Platform Login</a>
    </div>
  </header>

  <!-- Hero -->
  <div class="max-w-6xl mx-auto px-4 pt-14 pb-4 text-center">
    <div class="inline-block text-xs font-medium tracking-wide uppercase text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full mb-5">
      One Platform, Two Engines
    </div>
    <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight">Run Any Trading or Service Business</h1>
    <p class="text-lg text-slate-400 max-w-2xl mx-auto mb-12">
      Desktop &amp; mobile point-of-sale, inventory, GST billing, Udhaar/credit tracking, appointments, and job cards —
      pick the engine that matches your business below.
    </p>
  </div>

  <!-- TradeERP / ServiceERP split -->
  <div class="max-w-6xl mx-auto px-4 pb-16">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <!-- TradeERP -->
      <div class="card-trade bg-white/[0.03] border border-white/10 rounded-2xl p-6 md:p-8 flex flex-col transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-11 h-11 rounded-xl bg-indigo-500/15 flex items-center justify-center text-2xl">🛒</div>
          <div>
            <h2 class="text-xl font-semibold">TradeERP</h2>
            <div class="text-xs text-slate-500"><?= count($tradeSectors) ?> trades supported</div>
          </div>
        </div>
        <p class="text-sm text-slate-400 mb-5">For any kind of trading or retail business — kirana stores, hardware shops, wholesalers, and more.</p>
        <ul class="space-y-2.5 mb-6 flex-1">
          <?php foreach ($tradeFeatures as $f): ?>
          <li class="flex items-start gap-2.5 text-sm text-slate-300">
            <span class="mt-0.5 text-indigo-400">✓</span><span><?= $f ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <label class="block text-xs font-medium text-slate-500 mb-1.5">Find your trade</label>
        <select class="w-full bg-white/5 border border-white/10 text-slate-300 text-sm rounded-lg px-3 py-2.5 mb-4">
          <?php foreach ($tradeSectors as $s): ?>
            <option class="bg-slate-900"><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
        <a href="/login" class="text-center bg-indigo-600 hover:bg-indigo-500 rounded-lg px-4 py-2.5 text-sm font-semibold">Start with TradeERP</a>
      </div>

      <!-- ServiceERP -->
      <div class="card-service bg-white/[0.03] border border-white/10 rounded-2xl p-6 md:p-8 flex flex-col transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-11 h-11 rounded-xl bg-emerald-500/15 flex items-center justify-center text-2xl">🛠️</div>
          <div>
            <h2 class="text-xl font-semibold">ServiceERP</h2>
            <div class="text-xs text-slate-500"><?= count($serviceSectors) ?> services supported</div>
          </div>
        </div>
        <p class="text-sm text-slate-400 mb-5">For any kind of service business — clinics, salons, repair shops, agencies, and more.</p>
        <ul class="space-y-2.5 mb-6 flex-1">
          <?php foreach ($serviceFeatures as $f): ?>
          <li class="flex items-start gap-2.5 text-sm text-slate-300">
            <span class="mt-0.5 text-emerald-400">✓</span><span><?= $f ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <label class="block text-xs font-medium text-slate-500 mb-1.5">Find your service</label>
        <select class="w-full bg-white/5 border border-white/10 text-slate-300 text-sm rounded-lg px-3 py-2.5 mb-4">
          <?php foreach ($serviceSectors as $s): ?>
            <option class="bg-slate-900"><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
        <a href="/login" class="text-center bg-emerald-600 hover:bg-emerald-500 rounded-lg px-4 py-2.5 text-sm font-semibold">Start with ServiceERP</a>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <div class="max-w-6xl mx-auto px-4 py-10 border-t border-white/5 text-center">
    <a href="/login" class="inline-block bg-indigo-600 hover:bg-indigo-500 rounded-lg px-6 py-3 text-sm font-semibold">Platform Login</a>
    <p class="text-xs text-slate-600 mt-6">&copy; <?= date('Y') ?> <?= h(APP_NAME) ?></p>
  </div>

</body>
</html>
