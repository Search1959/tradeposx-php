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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(APP_NAME) ?> — POS &amp; Business Management for Every Trade</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .pill { transition: all 0.15s ease; }
  .pill:hover { transform: translateY(-1px); }
</style>
</head>
<body class="bg-slate-950 text-white">

  <!-- Hero -->
  <div class="max-w-5xl mx-auto px-4 pt-16 pb-12 text-center">
    <div class="inline-block text-xs font-medium tracking-wide uppercase text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full mb-5">
      TradeERP + ServiceERP, in one platform
    </div>
    <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight"><?= h(APP_NAME) ?></h1>
    <p class="text-lg text-slate-400 max-w-2xl mx-auto mb-8">
      Desktop &amp; mobile point-of-sale, inventory, Udhaar/credit tracking, appointments, job cards, and billing —
      built to run any kind of trading or service business, from a kirana store to a repair shop.
    </p>
    <a href="/login" class="inline-block bg-indigo-600 hover:bg-indigo-500 rounded-lg px-6 py-3 text-sm font-semibold">Platform Login</a>
  </div>

  <!-- TradeERP sectors -->
  <div class="max-w-5xl mx-auto px-4 py-10 border-t border-white/5">
    <div class="flex items-baseline justify-between mb-1">
      <h2 class="text-xl font-semibold">TradeERP</h2>
      <span class="text-xs text-slate-500"><?= count($tradeSectors) ?> trades supported</span>
    </div>
    <p class="text-sm text-slate-500 mb-5">Products, stock, purchases, GST billing, and Udhaar — for any kind of trading business.</p>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($tradeSectors as $s): ?>
        <span class="pill text-xs px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-slate-300"><?= h($s) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ServiceERP sectors -->
  <div class="max-w-5xl mx-auto px-4 py-10 border-t border-white/5">
    <div class="flex items-baseline justify-between mb-1">
      <h2 class="text-xl font-semibold">ServiceERP</h2>
      <span class="text-xs text-slate-500"><?= count($serviceSectors) ?> services supported</span>
    </div>
    <p class="text-sm text-slate-500 mb-5">Appointments, job cards, staff commissions, quotations, and invoicing — for any kind of service business.</p>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($serviceSectors as $s): ?>
        <span class="pill text-xs px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-slate-300"><?= h($s) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="max-w-5xl mx-auto px-4 py-12 border-t border-white/5 text-center">
    <a href="/login" class="inline-block bg-indigo-600 hover:bg-indigo-500 rounded-lg px-6 py-3 text-sm font-semibold">Platform Login</a>
    <p class="text-xs text-slate-600 mt-6">&copy; <?= date('Y') ?> <?= h(APP_NAME) ?></p>
  </div>

</body>
</html>
