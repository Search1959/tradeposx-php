<?php
/** HTML sitemap: every public page with its Title and URL. Expects $main, $groups, $site, $canonical. */
$title = 'Sitemap'; $desc = 'Every page on ' . APP_NAME . ': POS and billing software pages for each kind of shop and service business.';
require __DIR__ . '/layout-top.php';
$table = function (string $heading, array $rows) use ($site) { ?>
    <h2 class="mt-10 text-lg font-bold"><?= h($heading) ?></h2>
    <div class="mt-3 rounded-xl border border-white/10 overflow-hidden">
      <div class="hidden sm:flex gap-6 bg-white/[0.04] px-4 py-2.5 text-xs uppercase tracking-wide text-slate-400 font-semibold"><div class="flex-1">Title</div><div class="flex-1">URL</div></div>
      <div class="divide-y divide-white/5">
        <?php foreach ($rows as [$t, $path]): ?>
          <div class="px-4 py-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-6">
            <a class="sm:flex-1 text-sm text-slate-200 hover:text-white" href="<?= h($path) ?>"><?= h($t) ?></a>
            <a class="sm:flex-1 font-mono text-xs text-indigo-300 hover:text-indigo-200 break-all" href="<?= h($path) ?>"><?= h(preg_replace("#^https?://#", "", $site) . $path) ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
<?php };
$tradeRows = array_map(fn($s) => [$s['content']['title'] . ' | ' . APP_NAME, $s['path']], $groups['trade']);
$serviceRows = array_map(fn($s) => [$s['content']['title'] . ' | ' . APP_NAME, $s['path']], $groups['service']);
?>
  <main class="max-w-4xl mx-auto px-4 py-10 md:py-14 w-full">
    <nav aria-label="Breadcrumb" class="text-xs text-slate-400 flex items-center gap-1.5"><a href="/" class="hover:text-white">Home</a><span>›</span><span class="text-slate-300">Sitemap</span></nav>
    <h1 class="mt-5 text-3xl md:text-4xl font-extrabold tracking-tight">Sitemap</h1>
    <p class="mt-3 text-slate-400">Every public page with its title and address. The same list for search engines is at <a class="text-indigo-300 underline underline-offset-4" href="/sitemap.xml">/sitemap.xml</a>.</p>
    <?php $table('Main pages', $main); ?>
    <?php $table('TradeERP — POS & billing for shops (' . count($tradeRows) . ')', $tradeRows); ?>
    <?php $table('ServiceERP — billing & booking for service businesses (' . count($serviceRows) . ')', $serviceRows); ?>
  </main>
<?php require __DIR__ . '/layout-bottom.php'; ?>
