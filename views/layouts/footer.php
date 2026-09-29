    </div>
  </main>

  <!-- Mobile bottom tab bar — must stay inside the x-data wrapper (closed below,
       not above) so the "More" button's @click can reach mobileNavOpen at all.
       The same $base/$workspace/$mobileTab computed in header.php are still in
       scope here (both are part of the same request). -->
<?php
$tabs = $workspace === 'trade'
    ? [
        ['key' => 'home', 'href' => $base . '/dashboard', 'icon' => '🏠', 'label' => 'Home'],
        ['key' => 'primary', 'href' => $base . '/sales', 'icon' => '🛒', 'label' => 'Sales'],
        ['key' => 'second', 'href' => $base . '/products', 'icon' => '📦', 'label' => 'Products'],
        ['key' => 'third', 'href' => $base . '/customers', 'icon' => '👥', 'label' => 'Customers'],
    ]
    : [
        ['key' => 'home', 'href' => $base . '/service-dashboard', 'icon' => '🏠', 'label' => 'Home'],
        ['key' => 'primary', 'href' => $base . '/service-invoices/new', 'icon' => '🧾', 'label' => 'New Bill'],
        ['key' => 'second', 'href' => $base . '/job-cards', 'icon' => '🛠️', 'label' => 'Jobs'],
        ['key' => 'third', 'href' => $base . '/appointments', 'icon' => '📅', 'label' => 'Appts'],
    ];
?>
<nav class="md:hidden fixed inset-x-0 bottom-0 z-30 bg-slate-900 border-t border-slate-800 flex items-stretch"
     style="padding-bottom: env(safe-area-inset-bottom)">
  <?php foreach ($tabs as $t): $active = $mobileTab === $t['key']; ?>
    <a href="<?= $t['href'] ?>" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[11px] <?= $active ? 'text-amber-400' : 'text-slate-400' ?>">
      <span class="text-lg leading-none"><?= $t['icon'] ?></span>
      <span class="<?= $active ? 'font-semibold' : '' ?>"><?= h($t['label']) ?></span>
    </a>
  <?php endforeach; ?>
  <button type="button" @click="mobileNavOpen = true" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[11px] text-slate-400">
    <span class="text-lg leading-none">☰</span>
    <span>More</span>
  </button>
</nav>
</div>

</body>
</html>
