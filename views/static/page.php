<?php
/** Public policy / help pages. Expects $pageTitle and $content ('privacy' | 'terms' | 'support' | 'deletion'). */
require_once __DIR__ . '/../layouts/icons.php';
$company  = 'M/s Apex7tech';
$phone    = '+91 98361-30393';
$waNumber = '919836130393';
$updated  = '10 October 2026';
$waDelete = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode('Data deletion request for my TradePOSX workspace. Business name: ____  Workspace URL: tradeposx.com/____  Registered owner mobile/email: ____');
$waHelp   = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode('Hello, I need help with TradePOSX.');

// Each page is a list of [heading, html] blocks.
$pages = [];

$pages['privacy'] = [
  'intro' => APP_NAME . ' (tradeposx.com) is a point-of-sale and business-management service operated by ' . $company . ', Kolkata, West Bengal, India (“we”, “us”). This page explains what information the service holds, why, and what choices you have.',
  'blocks' => [
    ['Information we hold', '<ul>
      <li><b>Account details</b> — your name, email or username, mobile number and a one-way hash of your password (we never store the password itself).</li>
      <li><b>Business details you enter</b> — business name, address, GSTIN, phone and UPI ID.</li>
      <li><b>Business records you create</b> — products, stock, customers and their balances, suppliers, sales, purchases, expenses, appointments, job cards, invoices and staff.</li>
      <li><b>Activity log</b> — which team member changed what, and when.</li>
      <li><b>Technical data</b> — a session cookie to keep you signed in, and standard server logs (such as IP address and time of request).</li>
    </ul>'],
    ['How we use it', '<p>To run your workspace, show your data only to your own team, produce your invoices and reports, keep the service secure, and give you support. We do not sell your data and we do not use your business records for advertising.</p>'],
    ['Your customers’ information', '<p>Customer names, mobile numbers and balances that you enter belong to your business. We process them only on your behalf, to provide the service. You are responsible for having a proper basis to keep them and to message them.</p>'],
    ['Other services involved', '<ul>
      <li><b>Hosting</b> — the service runs on a third-party web hosting provider.</li>
      <li><b>WhatsApp</b> — buttons marked WhatsApp open a pre-filled message in WhatsApp on your own device. You send it yourself from there.</li>
      <li><b>AI photo scan</b> — if you use it, the photo you choose is sent to a third-party AI service to read the text and return items for you to review. Use it only on images you are happy to share.</li>
      <li><b>UPI QR</b> — the QR code is drawn in your browser from the UPI ID you save. Payments go directly between your customer and your UPI account; we do not see or process them.</li>
    </ul>'],
    ['Separation and security', '<p>Each business has its own workspace: every record is tied to your business and is shown only to your team. Access is controlled by roles (owner, admin, staff), and an activity log records changes. Passwords are stored as one-way hashes and the site is served over an encrypted (HTTPS) connection. No online service can promise perfect security, so please keep your password private and use the backup option regularly.</p>'],
    ['Keeping and deleting data', '<p>We keep your workspace while your account is active. You can download a full copy of your business data any time from <b>Settings → Download Full Backup</b>. To have your workspace deleted, use the <a href="/deletion">Data Deletion Request</a> page.</p>'],
    ['Cookies and device storage', '<p>We use a session cookie to keep you signed in. The mobile Quick Sale screen stores your product list and any sales not yet synced on your own device so it can work without internet; that storage stays on your device.</p>'],
    ['Children', '<p>The service is for businesses and is not meant for people under 18.</p>'],
    ['Changes and contact', '<p>We may update this page and will change the date above when we do. Questions: call or WhatsApp <b>' . $phone . '</b>.</p>'],
  ],
];

$pages['terms'] = [
  'intro' => 'These terms apply when you use ' . APP_NAME . ' (tradeposx.com), operated by ' . $company . ', Kolkata, West Bengal, India. By creating an account or using the service you agree to them.',
  'blocks' => [
    ['The service', '<p>' . APP_NAME . ' provides TradeERP (shops and trading) and ServiceERP (service businesses): point-of-sale, GST invoices, stock, customer credit (Udhaar), appointments, job cards, reports and related tools, delivered over the web.</p>'],
    ['Your account', '<p>You are responsible for the accuracy of the details you give, for keeping your password private, and for everything done by people you add to your team. Tell us promptly if you think your account has been misused.</p>'],
    ['Your data', '<p>The business records you enter remain yours. You allow us to store and process them only to provide the service to you. You can export a full backup at any time from Settings.</p>'],
    ['Acceptable use', '<p>Do not use the service for anything unlawful, to harass or spam people, to attempt to access another business’s data, to interfere with the service, or to upload material you have no right to use.</p>'],
    ['Tax and accounting', '<p>The service helps you prepare invoices and see your numbers, but you remain responsible for the correctness of your GST rates, invoices, returns and books. Please confirm your rates and filings with your accountant. Deleting an invoice removes it from the service, so keep your own records as the law requires.</p>'],
    ['Payments', '<p>The UPI QR feature only displays a QR code for your own UPI ID. Payments are between your customer and your bank or UPI app. We are not a payment processor and cannot confirm, hold or refund payments.</p>'],
    ['Availability and backups', '<p>We work to keep the service available, but it may be interrupted for maintenance or reasons beyond our control. Take regular backups with <b>Settings → Download Full Backup</b>.</p>'],
    ['Fees', '<p>If any plan or fee applies to your account, it will be communicated to you before you are charged.</p>'],
    ['Ending use', '<p>You may stop using the service at any time and may request deletion of your workspace. We may suspend an account that breaks these terms or puts the service or other users at risk.</p>'],
    ['Limits of liability', '<p>The service is provided “as is”. To the extent allowed by law, we are not liable for indirect or consequential loss, or for loss caused by incorrect data entered by you or your team, or by events outside our reasonable control.</p>'],
    ['Law and contact', '<p>These terms are governed by the laws of India, and the courts at Kolkata, West Bengal have jurisdiction. We may update these terms and will change the date above. Questions: <b>' . $phone . '</b>.</p>'],
  ],
];

$pages['support'] = [
  'intro' => 'Need a hand with ' . APP_NAME . '? Reach the ' . $company . ' support team directly.',
  'blocks' => [
    ['Talk to us', '<p>Call or WhatsApp <b>' . $phone . '</b>.</p>
      <p class="mt-3"><a href="' . htmlspecialchars($waHelp) . '" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2.5 no-underline">Chat on WhatsApp</a></p>
      <p class="mt-3 text-sm text-slate-400">Please send your business name and workspace URL (tradeposx.com/your-name) so we can find your account quickly. Never send your password.</p>'],
    ['Quick answers', '<ul>
      <li><b>Forgot or want to change your password?</b> Signed in: <b>Settings → Your Account</b>. If you cannot sign in, ask your business owner or contact us.</li>
      <li><b>Install on your phone:</b> open the site in Chrome, then choose “Add to Home screen”. The Quick Sale screen keeps working without internet and syncs when you are back online.</li>
      <li><b>Show a UPI QR at checkout:</b> save your UPI ID in <b>Settings → UPI Payments</b>, then pick UPI as the payment method.</li>
      <li><b>Bring your items in:</b> use the CSV import on the Products or Service Items screen, or scan a price list with the camera.</li>
      <li><b>Take a backup:</b> <b>Settings → Download Full Backup</b>.</li>
    </ul>'],
    ['Delete your data', '<p>See the <a href="/deletion">Data Deletion Request</a> page.</p>'],
  ],
];

$pages['deletion'] = [
  'intro' => 'You can ask us to delete your ' . APP_NAME . ' workspace and the data in it. This is how.',
  'blocks' => [
    ['Before you ask', '<p>Deletion cannot be undone. If you want to keep a copy, sign in and use <b>Settings → Download Full Backup</b> first.</p>'],
    ['How to request deletion', '<ol>
      <li>Message or call us on <b>' . $phone . '</b> from the owner’s registered mobile number (or write to us from the registered email).</li>
      <li>Tell us your <b>business name</b> and <b>workspace URL</b> (tradeposx.com/your-name) and that you want the workspace deleted.</li>
      <li>We will confirm that the request really comes from the owner, then delete the workspace.</li>
    </ol>
    <p class="mt-3"><a href="' . htmlspecialchars($waDelete) . '" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold px-4 py-2.5 no-underline">Send a deletion request on WhatsApp</a></p>'],
    ['What is deleted', '<p>The workspace’s users, products, customers, suppliers, sales, purchases, expenses, appointments, job cards, invoices and other records. We aim to complete this within 30 days of confirming your request. Copies in routine server backups are removed as those backups age out, and we may keep minimal records where the law requires us to.</p>'],
    ['If you are a customer of a business', '<p>The shop or service that billed you holds your details in its own workspace. Please ask that business to remove them; if you cannot reach them, contact us and we will pass your request on.</p>'],
  ],
];

$page = $pages[$content] ?? null;
if (!$page) { http_response_code(404); include __DIR__ . '/../404.php'; return; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h($pageTitle) ?> — <?= h(APP_NAME) ?></title>
<meta name="description" content="<?= h($pageTitle) ?> for <?= h(APP_NAME) ?>, operated by <?= h($company) ?>.">
<link rel="icon" href="/assets/img/icons/icon-192.png">
<meta name="theme-color" content="#020617">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  .doc h2 { font-size: 1.05rem; font-weight: 700; color: #fff; margin: 2rem 0 .5rem; }
  .doc p, .doc li { color: #cbd5e1; line-height: 1.7; font-size: .95rem; }
  .doc ul { list-style: disc; padding-left: 1.25rem; display: grid; gap: .5rem; }
  .doc ol { list-style: decimal; padding-left: 1.25rem; display: grid; gap: .5rem; }
  .doc b { color: #fff; font-weight: 600; }
  .doc a:not([class]) { color: #a5b4fc; text-decoration: underline; text-underline-offset: 3px; }
</style>
</head>
<body class="bg-slate-950 text-white antialiased min-h-screen flex flex-col">
  <header class="border-b border-white/5">
    <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-2.5 font-bold text-lg tracking-tight">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-emerald-500 flex items-center justify-center text-white"><?= uiIcon('cart', 'w-5 h-5') ?></span>
        <?= h(APP_NAME) ?>
      </a>
      <a href="/login" class="text-sm font-medium bg-white text-slate-900 hover:bg-slate-200 rounded-lg px-4 py-2">Log in</a>
    </div>
  </header>

  <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-10 md:py-14">
    <a href="/" class="text-sm text-slate-400 hover:text-white">← Back to home</a>
    <h1 class="mt-4 text-3xl md:text-4xl font-bold tracking-tight"><?= h($pageTitle) ?></h1>
    <p class="mt-2 text-xs text-slate-500">Last updated <?= h($updated) ?></p>
    <div class="doc mt-6">
      <p><?= $page['intro'] ?></p>
      <?php foreach ($page['blocks'] as [$heading, $html]): ?>
        <h2><?= h($heading) ?></h2>
        <?= $html ?>
      <?php endforeach; ?>
    </div>
  </main>

  <footer class="border-t border-white/5">
    <div class="max-w-3xl mx-auto px-4 py-6 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
      <div>&copy; <?= date('Y') ?> <?= h(APP_NAME) ?> · <?= h($company) ?></div>
      <div class="flex items-center gap-4">
        <a href="/privacy" class="hover:text-slate-300">Privacy</a><a href="/terms" class="hover:text-slate-300">Terms</a><a href="/support" class="hover:text-slate-300">Support</a><a href="/deletion" class="hover:text-slate-300">Data deletion</a>
      </div>
    </div>
  </footer>
</body>
</html>
