<?php /** Shared footer of the public sector pages. */ ?>
  <footer class="border-t border-white/10 bg-slate-950 mt-auto">
    <div class="max-w-6xl mx-auto px-4 py-8 grid gap-6 md:grid-cols-3 text-sm">
      <div>
        <div class="font-bold"><?= h(APP_NAME) ?></div>
        <p class="mt-2 text-slate-400">POS, GST billing, stock, Udhaar, appointments and job cards for Indian shops and service businesses.</p>
        <p class="mt-3 text-slate-500 text-xs">Operated by M/s Apex7tech, Kolkata · <a class="hover:text-white" href="tel:+919836130393">+91 98361-30393</a></p>
      </div>
      <div>
        <div class="text-xs font-bold tracking-widest uppercase text-indigo-300">Explore</div>
        <div class="mt-3 grid gap-1.5 text-slate-300">
          <a class="hover:text-white" href="/pos">TradeERP — for shops</a>
          <a class="hover:text-white" href="/software">ServiceERP — for services</a>
          <a class="hover:text-white" href="/sitemap">Sitemap</a>
        </div>
      </div>
      <div>
        <div class="text-xs font-bold tracking-widest uppercase text-indigo-300">Policies &amp; help</div>
        <div class="mt-3 grid gap-1.5 text-slate-300">
          <a class="hover:text-white" href="/privacy">Privacy Policy</a>
          <a class="hover:text-white" href="/terms">Terms of Service</a>
          <a class="hover:text-white" href="/support">App Support</a>
        </div>
      </div>
    </div>
    <div class="border-t border-white/5 py-4 text-center text-xs text-slate-600">&copy; <?= date('Y') ?> <?= h(APP_NAME) ?></div>
  </footer>
</body>
</html>
