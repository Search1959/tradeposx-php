<?php
/** /pos or /software: lists the live sector pages of one engine. Expects $type, $engine, $hubPath, $live, $total, $hubTitle, $hubDesc, $canonical. */
$title = $hubTitle; $desc = $hubDesc;
$jsonLd = [['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => rtrim(substr($canonical, 0, -strlen($hubPath)), '/') . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => $engine, 'item' => $canonical]]]];
require __DIR__ . '/layout-top.php';
?>
  <main class="max-w-6xl mx-auto px-4 py-10 md:py-14 w-full">
    <nav aria-label="Breadcrumb" class="text-xs text-slate-400 flex items-center gap-1.5"><a href="/" class="hover:text-white">Home</a><span>›</span><span class="text-slate-300"><?= h($engine) ?></span></nav>
    <h1 class="mt-5 text-3xl md:text-5xl font-extrabold tracking-tight text-balance"><?= h($title) ?></h1>
    <p class="mt-4 max-w-2xl text-slate-300"><?= h($desc) ?></p>

    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($live as $s): ?>
        <a href="<?= h($s['path']) ?>" class="group rounded-2xl border border-white/10 bg-white/[0.03] hover:bg-white/[0.06] hover:border-white/20 p-5 transition">
          <h2 class="font-semibold"><?= h($s['content']['noun']) ?></h2>
          <p class="mt-1.5 text-sm text-slate-400 leading-relaxed"><?= h($s['content']['sub']) ?></p>
          <div class="mt-3 text-sm font-medium text-indigo-300 group-hover:text-indigo-200">See how it works →</div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="mt-10 rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-sm text-slate-300">
      Do not see your business here? <?= h(APP_NAME) ?> supports <b class="text-white"><?= (int) $total ?></b> <?= $type === 'trade' ? 'kinds of shop' : 'kinds of service business' ?>. Pick yours when you
      <a class="text-emerald-300 font-semibold underline underline-offset-4" href="/login?type=<?= h($type) ?>">register</a>, or
      <a class="text-emerald-300 font-semibold underline underline-offset-4" href="/login/demo">try the live demo</a>.
    </div>
  </main>
<?php require __DIR__ . '/layout-bottom.php'; ?>
