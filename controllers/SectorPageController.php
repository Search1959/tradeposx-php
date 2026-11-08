<?php
/**
 * Public, no-login landing pages per business type:
 *   /pos/{slug}       TradeERP sectors      /pos       hub
 *   /software/{slug}  ServiceERP sectors    /software  hub
 *   /sitemap          HTML sitemap (Title + URL)
 *   /sitemap.xml, /robots.txt
 * What is live is decided by config/sector-pages.php ('live_phase' + 'content').
 */
class SectorPageController {
    private static ?array $cfg = null;

    private static function cfg(): array {
        if (self::$cfg === null) self::$cfg = require __DIR__ . '/../config/sector-pages.php';
        return self::$cfg;
    }

    private static function slugify(string $s): string {
        $s = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', str_replace('&', ' and ', $s)), '-'));
        return $s !== '' ? $s : 'sector';
    }

    private static function siteUrl(): string {
        $host = $_SERVER['HTTP_HOST'] ?? 'tradeposx.com';
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || preg_match('/(^|\.)tradeposx\.com$/', $host);
        return ($https ? 'https://' : 'http://') . $host;
    }

    /**
     * Every sector with its page data. Only sectors whose phase is live AND that have content are 'live'.
     * @return array<string,array> code => [code,type,label,slug,path,phase,live,content]
     */
    public static function sectors(): array {
        $cfg = self::cfg();
        $phaseOf = [];
        foreach ($cfg['phases'] as $phase => $codes) foreach ($codes as $c) $phaseOf[$c] = (int) $phase;
        $out = [];
        foreach (['trade' => SectorHelper::tradeSectors(), 'service' => SectorHelper::serviceSectors()] as $type => $list) {
            foreach ($list as $code => $label) {
                $content = $cfg['content'][$code] ?? null;
                $slug = $content['slug'] ?? self::slugify($label);
                $phase = $phaseOf[$code] ?? 99;
                $out[$code] = [
                    'code' => $code, 'type' => $type, 'label' => $label, 'noun' => $content['noun'] ?? $label, 'slug' => $slug,
                    'path' => ($type === 'trade' ? '/pos/' : '/software/') . $slug,
                    'phase' => $phase, 'content' => $content,
                    'live' => $content !== null && $phase <= (int) $cfg['live_phase'],
                ];
            }
        }
        return $out;
    }

    /** code => path for every live page (used by the home page pickers). */
    public static function liveMap(): array {
        $m = [];
        foreach (self::sectors() as $s) if ($s['live']) $m[$s['code']] = $s['path'];
        return $m;
    }

    private static function pageTitle(array $s): string { return $s['content']['title'] ?? ($s['label'] . ' Software'); }

    private function notFound(): void {
        http_response_code(404);
        include __DIR__ . '/../views/404.php';
    }

    // ───────── pages ─────────
    public function tradeAction(?string $slug = null): void { $this->render('trade', $slug); }
    public function serviceAction(?string $slug = null): void { $this->render('service', $slug); }

    private function render(string $type, ?string $slug): void {
        $site = self::siteUrl();
        $live = array_values(array_filter(self::sectors(), fn($s) => $s['live'] && $s['type'] === $type));
        $engine = $type === 'trade' ? 'TradeERP' : 'ServiceERP';
        $hubPath = $type === 'trade' ? '/pos' : '/software';

        if ($slug === null || $slug === '') {            // hub: /pos or /software
            $hubTitle = $type === 'trade' ? 'POS & Billing Software for Every Kind of Shop' : 'Billing & Booking Software for Every Service Business';
            $hubDesc = $type === 'trade'
                ? 'TradeERP by TradePOSX: POS, GST billing, stock and Udhaar for kirana, pharmacy, hardware, bakery and many more kinds of shop.'
                : 'ServiceERP by TradePOSX: appointments, job cards, quotations and billing for salons, clinics, repair shops, printing presses and more.';
            $canonical = $site . $hubPath;
            $total = count($type === 'trade' ? SectorHelper::tradeSectors() : SectorHelper::serviceSectors());
            require __DIR__ . '/../views/sector/hub.php';
            return;
        }

        $current = null;
        foreach ($live as $s) if ($s['slug'] === strtolower($slug)) { $current = $s; break; }
        if (!$current) { $this->notFound(); return; }

        $c = $current['content'];
        $related = array_values(array_filter($live, fn($s) => $s['code'] !== $current['code']));
        $sample = $this->sample($type, $current['code']);
        $title = $c['title'];
        $desc = $c['d'];
        $canonical = $site . $current['path'];
        $signupUrl = '/login?type=' . $type . '&sector=' . $current['code'];
        $faqs = array_merge($c['faqs'], [
            ['Can I try it before I sign up?', 'Yes. The live demo opens a sample business with products or services, customers and sales. No signup is needed, and demo data is shared and may be reset.'],
        ]);
        $jsonLd = [
            ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication',
             'name' => APP_NAME . ' ' . $engine . ' for ' . $current['noun'], 'applicationCategory' => 'BusinessApplication',
             'operatingSystem' => 'Web (installable on Android and iOS home screens)', 'url' => $canonical, 'description' => $desc,
             'publisher' => ['@type' => 'Organization', 'name' => 'Apex7tech']],
            ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $site . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $engine, 'item' => $site . $hubPath],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $current['noun'], 'item' => $canonical]]],
            ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => [
                '@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)],
        ];
        require __DIR__ . '/../views/sector/page.php';
    }

    /** A few real rows from the sector's starter list (demodata), preferring one row per category. */
    private function sample(string $type, string $code): array {
        $file = ROOT_PATH . '/demodata/' . ($type === 'trade' ? 'tradeerp' : 'service-items') . '/' . $code . '.csv';
        if (!is_file($file) || ($h = fopen($file, 'r')) === false) return ['rows' => [], 'total' => 0];
        $head = array_map(fn($x) => strtolower(trim((string) $x)), (array) fgetcsv($h));
        $priceCol = $type === 'trade' ? 'selling_price' : 'price';
        $all = [];
        while (($r = fgetcsv($h)) !== false) {
            $row = [];
            foreach ($head as $i => $col) $row[$col] = trim((string) ($r[$i] ?? ''));
            if (($row['name'] ?? '') === '') continue;
            $all[] = ['name' => $row['name'], 'category' => $row['category'] ?? '', 'price' => (float) ($row[$priceCol] ?? 0), 'unit' => $row['unit'] ?? ''];
        }
        fclose($h);
        $picked = []; $seen = [];
        foreach ($all as $i => $r) { if (!isset($seen[$r['category']])) { $seen[$r['category']] = true; $picked[$i] = $r; } if (count($picked) >= 8) break; }
        foreach ($all as $i => $r) { if (count($picked) >= 8) break; if (!isset($picked[$i])) $picked[$i] = $r; }
        ksort($picked);
        return ['rows' => array_values($picked), 'total' => count($all)];
    }

    // ───────── sitemap + robots ─────────
    public function sitemapAction(): void {
        $site = self::siteUrl();
        $groups = ['trade' => [], 'service' => []];
        foreach (self::sectors() as $s) if ($s['live']) $groups[$s['type']][] = $s;
        $canonical = $site . '/sitemap';
        $main = [
            ['Home', '/'], ['TradeERP — POS & billing for shops', '/pos'], ['ServiceERP — billing & booking for services', '/software'],
            ['Privacy Policy', '/privacy'], ['Terms of Service', '/terms'], ['App Support', '/support'], ['Data Deletion Request', '/deletion'],
        ];
        require __DIR__ . '/../views/sector/sitemap.php';
    }

    public function sitemapxmlAction(): void {
        $site = self::siteUrl();
        $mod = self::cfg()['updated'];
        $urls = [['/', 'weekly', '1.0'], ['/pos', 'weekly', '0.8'], ['/software', 'weekly', '0.8'], ['/sitemap', 'monthly', '0.3'],
                 ['/privacy', 'yearly', '0.2'], ['/terms', 'yearly', '0.2'], ['/support', 'yearly', '0.3']];
        foreach (self::sectors() as $s) if ($s['live']) $urls[] = [$s['path'], 'monthly', '0.7'];
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$path, $freq, $prio]) {
            echo "  <url><loc>" . htmlspecialchars($site . $path, ENT_XML1) . "</loc><lastmod>{$mod}</lastmod><changefreq>{$freq}</changefreq><priority>{$prio}</priority></url>\n";
        }
        echo "</urlset>\n";
    }

    public function robotsAction(): void {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\n"
           . "Disallow: /login\nDisallow: /superadmin\nDisallow: /review/\nDisallow: /smarttech-demo/\n\n"
           . "Sitemap: " . self::siteUrl() . "/sitemap.xml\n";
    }
}
