<?php
/** Shared top of the public sector pages. Expects $title, $desc, $canonical; optional $jsonLd (array of schema.org objects). */
require_once __DIR__ . '/../layouts/icons.php';
$fullTitle = $title . ' | ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h($fullTitle) ?></title>
<meta name="description" content="<?= h($desc) ?>">
<link rel="canonical" href="<?= h($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= h(APP_NAME) ?>">
<meta property="og:title" content="<?= h($fullTitle) ?>">
<meta property="og:description" content="<?= h($desc) ?>">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta name="twitter:card" content="summary">
<link rel="manifest" href="/assets/manifest.webmanifest">
<link rel="icon" href="/assets/img/icons/icon-192.png">
<meta name="theme-color" content="#020617">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  details > summary { list-style: none; cursor: pointer; }
  details > summary::-webkit-details-marker { display: none; }
  details[open] .chev { transform: rotate(180deg); }
</style>
<?php foreach (($jsonLd ?? []) as $ld): ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body class="bg-slate-950 text-white antialiased min-h-screen flex flex-col">
  <header class="sticky top-0 z-40 bg-slate-950/85 backdrop-blur border-b border-white/5">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
      <a href="/" class="flex items-center gap-2.5 font-bold text-lg tracking-tight">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-emerald-500 flex items-center justify-center text-white"><?= uiIcon('cart', 'w-5 h-5') ?></span>
        <?= h(APP_NAME) ?>
      </a>
      <nav class="hidden md:flex items-center gap-7 text-sm text-slate-300">
        <a href="/pos" class="hover:text-white">TradeERP</a>
        <a href="/software" class="hover:text-white">ServiceERP</a>
        <a href="/#reviews" class="hover:text-white">Google Reviews</a>
        <a href="/sitemap" class="hover:text-white">All pages</a>
      </nav>
      <div class="flex items-center gap-2">
        <a href="/login/demo" class="hidden sm:inline-flex text-sm font-medium border border-white/15 hover:bg-white/5 rounded-lg px-4 py-2">Live demo</a>
        <a href="/login" class="text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 rounded-lg px-4 py-2">Login</a>
      </div>
    </div>
  </header>
