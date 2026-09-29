<?php
/** Included by every tenant-scoped view. Expects $pageTitle to be set by the caller. */
$org = Tenant::current();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h($pageTitle ?? 'Dashboard') ?> · <?= h($org['name'] ?? APP_NAME) ?></title>
<link rel="manifest" href="/assets/manifest.webmanifest">
<meta name="theme-color" content="<?= h($org['theme_color'] ?? '#4f46e5') ?>">
<script src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  [x-cloak] { display: none !important; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
</style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen flex">
  <aside class="hidden md:flex md:w-56 md:flex-col bg-slate-900 text-slate-200">
    <div class="px-4 py-4 text-lg font-semibold text-white border-b border-slate-800"><?= h($org['name'] ?? APP_NAME) ?></div>
    <nav class="flex-1 px-2 py-3 space-y-1 text-sm">
      <?php $base = $org ? '/' . h($org['slug']) : ''; ?>
      <a href="<?= $base ?>/dashboard" class="block px-3 py-2 rounded hover:bg-slate-800">Dashboard</a>
      <a href="<?= $base ?>/pos-mobile" class="block px-3 py-2 rounded hover:bg-slate-800">📱 Quick Sale (Mobile)</a>
      <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-wide text-slate-500">TradeERP</div>
      <a href="<?= $base ?>/sales" class="block px-3 py-2 rounded hover:bg-slate-800">POS / Sales</a>
      <a href="<?= $base ?>/products" class="block px-3 py-2 rounded hover:bg-slate-800">Products</a>
      <a href="<?= $base ?>/customers" class="block px-3 py-2 rounded hover:bg-slate-800">Customers / Udhaar</a>
      <a href="<?= $base ?>/purchases" class="block px-3 py-2 rounded hover:bg-slate-800">Purchases</a>
      <a href="<?= $base ?>/suppliers" class="block px-3 py-2 rounded hover:bg-slate-800">Suppliers</a>
      <a href="<?= $base ?>/reports" class="block px-3 py-2 rounded hover:bg-slate-800">Reports</a>
      <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-wide text-slate-500">ServiceERP</div>
      <a href="<?= $base ?>/appointments" class="block px-3 py-2 rounded hover:bg-slate-800">Appointments</a>
      <a href="<?= $base ?>/job-cards" class="block px-3 py-2 rounded hover:bg-slate-800">Job Cards</a>
      <a href="<?= $base ?>/service-invoices" class="block px-3 py-2 rounded hover:bg-slate-800">Service Invoices</a>
      <a href="<?= $base ?>/service-customers" class="block px-3 py-2 rounded hover:bg-slate-800">Service Customers</a>
      <a href="<?= $base ?>/service-staff" class="block px-3 py-2 rounded hover:bg-slate-800">Staff</a>
    </nav>
    <div class="px-3 py-3 border-t border-slate-800 text-xs">
      <div class="text-slate-300"><?= h($user['name'] ?? '') ?></div>
      <a href="<?= $base ?>/logout" class="text-slate-500 hover:text-slate-300">Logout</a>
    </div>
  </aside>
  <main class="flex-1 min-w-0">
    <header class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900 text-white">
      <span class="font-semibold"><?= h($org['name'] ?? APP_NAME) ?></span>
      <a href="<?= isset($base) ? $base : '' ?>/logout" class="text-xs text-slate-300">Logout</a>
    </header>
    <div class="p-4 md:p-6">
