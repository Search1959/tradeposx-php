<?php
/** One sector landing page. Expects $current, $c (content), $type, $engine, $hubPath, $related, $sample, $title, $desc, $canonical, $signupUrl, $faqs, $jsonLd. */
require __DIR__ . '/layout-top.php';
$tints = ['bg-indigo-500/15 text-indigo-300', 'bg-emerald-500/15 text-emerald-300', 'bg-amber-500/15 text-amber-300'];
$accent = $type === 'trade' ? ['text-indigo-300', 'bg-indigo-600 hover:bg-indigo-500'] : ['text-emerald-300', 'bg-emerald-600 hover:bg-emerald-500'];
$money = fn($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
?>
  <main>
    <!-- Hero -->
    <section class="relative overflow-hidden">
      <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(60% 50% at 85% 0%, rgba(99,102,241,.22), transparent 70%), radial-gradient(45% 40% at 0% 35%, rgba(16,185,129,.12), transparent 70%)"></div>
      <div class="relative max-w-6xl mx-auto px-4 pt-8 pb-14 md:pt-12 md:pb-20">
        <nav aria-label="Breadcrumb" class="text-xs text-slate-400 flex flex-wrap items-center gap-1.5">
          <a href="/" class="hover:text-white">Home</a><span>›</span><a href="<?= h($hubPath) ?>" class="hover:text-white"><?= h($engine) ?></a><span>›</span><span class="text-slate-300"><?= h($current['noun']) ?></span>
        </nav>
        <div class="mt-6 grid md:grid-cols-[1.05fr_0.95fr] gap-10 lg:gap-14 items-start">
          <div>
            <span class="inline-flex items-center gap-2 text-[11px] font-semibold tracking-widest uppercase <?= $accent[0] ?> bg-white/5 border border-white/10 rounded-full px-3 py-1"><?= h($engine) ?> · <?= h($current['noun']) ?></span>
            <h1 class="mt-5 text-3xl sm:text-5xl font-extrabold leading-[1.1] tracking-tight text-balance"><?= h($c['headline']) ?></h1>
            <p class="mt-5 text-base sm:text-lg text-slate-300 max-w-xl"><?= h($c['sub']) ?></p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3">
              <a href="/login/demo" class="inline-flex items-center justify-center gap-2 <?= $accent[1] ?> rounded-xl px-6 py-3.5 text-base font-semibold shadow-lg shadow-black/30">Try the live demo <?= uiIcon('arrow', 'w-5 h-5') ?></a>
              <a href="<?= h($signupUrl) ?>" class="inline-flex items-center justify-center border border-white/15 hover:bg-white/5 rounded-xl px-6 py-3.5 text-base font-semibold">Start with <?= h($current['label']) ?></a>
            </div>
            <p class="mt-4 text-xs text-slate-500">No signup needed to explore the demo. It is a shared sample business and its data may be reset.</p>
          </div>

          <?php if ($sample['rows']): ?>
          <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <div class="flex items-center justify-between gap-3">
              <div class="text-sm font-semibold">Ready-made list for <?= h($current['noun']) ?></div>
              <span class="text-[11px] font-semibold rounded-full px-2.5 py-1 <?= $tints[1] ?>"><?= (int) $sample['total'] ?> items</span>
            </div>
            <div class="mt-3 divide-y divide-white/5 text-sm">
              <?php foreach ($sample['rows'] as $r): ?>
                <div class="flex items-center justify-between gap-3 py-2">
                  <div class="min-w-0"><div class="truncate text-slate-100"><?= h($r['name']) ?></div><?php if ($r['category'] !== ''): ?><div class="text-xs text-slate-500 truncate"><?= h($r['category']) ?></div><?php endif; ?></div>
                  <div class="shrink-0 font-semibold text-slate-200"><?= h($money($r['price'])) ?><?php if ($r['unit'] !== ''): ?><span class="text-xs font-normal text-slate-500"> / <?= h($r['unit']) ?></span><?php endif; ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <p class="mt-3 text-[11px] text-slate-500">Sample items from the starter list you can import and edit. Prices, stock and GST are placeholders. Set your own.</p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Pains -->
    <section class="border-t border-white/5 bg-white/[0.015]">
      <div class="max-w-6xl mx-auto px-4 py-14 md:py-16">
        <div class="max-w-2xl">
          <div class="text-xs font-semibold tracking-widest uppercase <?= $accent[0] ?>">Sound familiar?</div>
          <h2 class="mt-2 text-2xl md:text-3xl font-bold tracking-tight">What <?= h($current['noun']) ?> owners deal with every day</h2>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
          <?php foreach ($c['pains'] as $i => [$pt, $px]): ?>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
              <div class="w-9 h-9 rounded-lg flex items-center justify-center <?= $tints[$i % 3] ?>"><?= uiIcon('check', 'w-5 h-5') ?></div>
              <h3 class="mt-4 font-semibold"><?= h($pt) ?></h3>
              <p class="mt-1.5 text-sm text-slate-400 leading-relaxed"><?= h($px) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section>
      <div class="max-w-6xl mx-auto px-4 py-14 md:py-16">
        <div class="max-w-2xl">
          <div class="text-xs font-semibold tracking-widest uppercase <?= $accent[0] ?>">What you get</div>
          <h2 class="mt-2 text-2xl md:text-3xl font-bold tracking-tight">Built for a <?= h(strtolower($current['noun'])) ?></h2>
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($c['features'] as [$ft, $fx]): ?>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
              <h3 class="font-semibold flex items-start gap-2.5"><span class="mt-0.5 text-emerald-400 shrink-0"><?= uiIcon('check', 'w-5 h-5') ?></span><?= h($ft) ?></h3>
              <p class="mt-1.5 text-sm text-slate-400 leading-relaxed pl-[1.9rem]"><?= h($fx) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- How it starts -->
    <section class="border-t border-white/5 bg-white/[0.015]">
      <div class="max-w-6xl mx-auto px-4 py-14 md:py-16">
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight">Start in three steps</h2>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
          <?php foreach ([
              ['Pick ' . $current['label'], 'Choose ' . $engine . ' and your type of business when you register. It takes a minute.'],
              ['Add your items', $sample['total'] ? 'Import the ready-made list of ' . $sample['total'] . ' items and edit the prices, or import your own CSV or a photo of your price list.' : 'Import a CSV, or photograph your price list and review it before saving.'],
              ['Start billing', 'Bill from your phone or computer, take cash, UPI or credit, and send the bill on WhatsApp.'],
          ] as $i => [$st, $sx]): ?>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
              <div class="w-8 h-8 rounded-full bg-indigo-600 text-sm font-bold flex items-center justify-center"><?= $i + 1 ?></div>
              <h3 class="mt-4 font-semibold"><?= h($st) ?></h3>
              <p class="mt-1.5 text-sm text-slate-400 leading-relaxed"><?= h($sx) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section>
      <div class="max-w-3xl mx-auto px-4 py-14 md:py-16">
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight">Questions <?= h($current['noun']) ?> owners ask</h2>
        <div class="mt-6 grid gap-3">
          <?php foreach ($faqs as [$q, $a]): ?>
            <details class="rounded-xl border border-white/10 bg-white/[0.03] px-5 py-4">
              <summary class="flex items-center justify-between gap-4 font-semibold"><span><?= h($q) ?></span><span class="chev text-slate-400 transition-transform shrink-0">⌄</span></summary>
              <p class="mt-3 text-sm text-slate-400 leading-relaxed"><?= h($a) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="max-w-6xl mx-auto px-4 pb-14">
      <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-indigo-600/20 to-emerald-600/10 p-8 md:p-10 text-center">
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight">See it with a sample <?= h(strtolower($current['noun'])) ?></h2>
        <p class="mt-2 text-slate-300">Open the live demo, tap through a bill, and decide for yourself.</p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
          <a href="/login/demo" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl px-6 py-3.5 font-semibold">Try the live demo <?= uiIcon('arrow', 'w-5 h-5') ?></a>
          <a href="<?= h($signupUrl) ?>" class="inline-flex items-center justify-center border border-white/15 hover:bg-white/5 rounded-xl px-6 py-3.5 font-semibold">Start with <?= h($current['label']) ?></a>
        </div>
        <p class="mt-4 text-sm text-slate-400">Questions? Call or WhatsApp <a class="text-emerald-300 font-semibold" href="tel:+919836130393">+91 98361-30393</a></p>
      </div>
    </section>

    <!-- Related -->
    <?php if ($related): ?>
    <section class="border-t border-white/5">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <h2 class="text-sm font-semibold text-slate-300">Other <?= $type === 'trade' ? 'kinds of shop' : 'service businesses' ?> using <?= h($engine) ?></h2>
        <div class="mt-4 flex flex-wrap gap-2">
          <?php foreach ($related as $rs): ?>
            <a href="<?= h($rs['path']) ?>" class="text-sm rounded-full border border-white/10 hover:border-white/30 hover:bg-white/5 px-3.5 py-1.5 text-slate-300"><?= h($rs['content']['noun']) ?></a>
          <?php endforeach; ?>
          <a href="<?= h($hubPath) ?>" class="text-sm rounded-full border border-indigo-400/30 text-indigo-300 hover:bg-indigo-500/10 px-3.5 py-1.5">All <?= h($engine) ?> pages →</a>
        </div>
      </div>
    </section>
    <?php endif; ?>
  </main>
<?php require __DIR__ . '/layout-bottom.php'; ?>
