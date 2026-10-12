    </div>
  </main>

  <!-- Mobile bottom bar: Home · 2 key screens · centre action button · More.
       Must stay inside the x-data wrapper (closed below) so "More" can reach mobileNavOpen.
       $base/$isTrade/$mobileTab/$accent* come from header.php (same request). -->
<?php
$tabs = $isTrade
    ? [
        ['key' => 'home',   'href' => $base . '/dashboard', 'icon' => 'home', 'label' => 'Home'],
        ['key' => 'second', 'href' => $base . '/products', 'icon' => 'box', 'label' => 'Products'],
        ['key' => 'third',  'href' => $base . '/customers', 'icon' => 'users', 'label' => 'Customers'],
    ]
    : [
        ['key' => 'home',   'href' => $base . '/service-dashboard', 'icon' => 'home', 'label' => 'Home'],
        ['key' => 'second', 'href' => $base . '/job-cards', 'icon' => 'wrench', 'label' => 'Jobs'],
        ['key' => 'third',  'href' => $base . '/appointments', 'icon' => 'calendar', 'label' => 'Appts'],
    ];
$fabHref  = $isTrade ? $base . '/sales' : $base . '/service-invoices/new';
$fabLabel = $isTrade ? 'New Sale' : 'New Bill';
$tabLink = function (array $t) use ($mobileTab, $accentText) {
    $active = $mobileTab === $t['key'];
    return '<a href="' . $t['href'] . '" class="flex-1 flex flex-col items-center justify-center gap-0.5 pt-2 pb-1.5 text-[11px] '
        . ($active ? $accentText . ' font-semibold' : 'text-slate-500') . '">' . uiIcon($t['icon'], 'w-6 h-6') . '<span>' . h($t['label']) . '</span></a>';
};
?>
<nav class="md:hidden fixed inset-x-0 bottom-0 z-30 bg-white border-t border-slate-200 flex items-stretch shadow-[0_-4px_12px_rgba(15,23,42,0.06)]"
     style="padding-bottom: env(safe-area-inset-bottom)">
  <?= $tabLink($tabs[0]) ?>
  <?= $tabLink($tabs[1]) ?>
  <a href="<?= $fabHref ?>" class="flex-1 flex flex-col items-center justify-end pb-1.5 text-[11px] font-semibold <?= $accentText ?>" aria-label="<?= h($fabLabel) ?>">
    <span class="-mt-6 mb-0.5 w-14 h-14 rounded-full <?= $accentBg ?> text-white flex items-center justify-center shadow-lg ring-4 ring-slate-50"><?= uiIcon('plus', 'w-7 h-7') ?></span>
    <span><?= h($fabLabel) ?></span>
  </a>
  <?= $tabLink($tabs[2]) ?>
  <button type="button" @click="mobileNavOpen = true" class="flex-1 flex flex-col items-center justify-center gap-0.5 pt-2 pb-1.5 text-[11px] text-slate-500">
    <?= uiIcon('menu', 'w-6 h-6') ?><span>More</span>
  </button>
</nav>
</div>

<?php require __DIR__ . '/../shared/password-toggle.php'; ?>
</body>
</html>
