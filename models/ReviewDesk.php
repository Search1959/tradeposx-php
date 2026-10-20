<?php
/**
 * ReviewDesk data layer — branches (one Google Place ID each), per-shop page settings and an event log
 * (scan / draft / post_click) that the dashboard will read. Tables are created on first use so a live
 * database needs no manual migration.
 */
class ReviewDesk extends BaseModel {
    private static bool $ready = false;

    public const DEFAULT_POS_TRADE   = ['Good price', 'Quality product', 'Wide variety', 'Polite staff', 'Clean shop', 'Quick billing', 'Genuine products', 'Easy to find'];
    public const DEFAULT_POS_SERVICE = ['Fast service', 'Polite staff', 'Good price', 'Quality work', 'On-time', 'Clean workshop', 'Honest advice', 'Easy booking'];
    public const DEFAULT_NEG         = ['Long waiting time', 'Price felt high', 'Staff behaviour', 'Product quality', 'Service quality', 'Cleanliness'];

    public function __construct() {
        parent::__construct();
        if (self::$ready) return;
        self::$ready = true;
        $this->db->exec("CREATE TABLE IF NOT EXISTS review_branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            org_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            place_id VARCHAR(200) NULL,
            alert_whatsapp VARCHAR(20) NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_review_slug (slug),
            KEY idx_review_branch_org (org_id)
        ) DEFAULT CHARSET=utf8mb4");
        $this->db->exec("CREATE TABLE IF NOT EXISTS review_settings (
            org_id INT PRIMARY KEY,
            intro VARCHAR(160) NULL,
            chips_pos TEXT NULL,
            chips_neg TEXT NULL,
            langs VARCHAR(20) NOT NULL DEFAULT 'en,hi,bn',
            default_lang CHAR(2) NOT NULL DEFAULT 'en',
            ai_enabled TINYINT(1) NOT NULL DEFAULT 1,
            logo VARCHAR(120) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) DEFAULT CHARSET=utf8mb4");
        $this->db->exec("CREATE TABLE IF NOT EXISTS review_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            org_id INT NOT NULL,
            branch_id INT NOT NULL,
            staff_id INT NULL,
            event VARCHAR(20) NOT NULL,
            rating TINYINT NULL,
            lang CHAR(2) NULL,
            chips VARCHAR(255) NULL,
            ip_hash CHAR(16) NOT NULL,
            device_hash CHAR(16) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_review_ev_org (org_id, created_at),
            KEY idx_review_ev_ip (ip_hash, event, created_at),
            KEY idx_review_ev_dev (device_hash, event, created_at)
        ) DEFAULT CHARSET=utf8mb4");
    }

    // ───────── branches ─────────
    public function branches(int $orgId): array {
        return $this->query('SELECT * FROM review_branches WHERE org_id = ? ORDER BY id', [$orgId]);
    }
    public function branch(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM review_branches WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }
    public function branchBySlug(string $slug): ?array {
        return $this->queryOne('SELECT * FROM review_branches WHERE slug = ? AND active = 1', [$slug]);
    }
    /** Every shop starts with one branch whose page address is the shop's own slug. */
    public function ensureDefaultBranch(array $org): array {
        $list = $this->branches((int) $org['id']);
        if ($list) return $list;
        $this->insert('INSERT INTO review_branches (org_id, name, slug) VALUES (?,?,?)', [(int) $org['id'], mb_substr((string) $org['name'], 0, 100), $this->freeSlug((string) $org['slug'])]);
        return $this->branches((int) $org['id']);
    }
    public function freeSlug(string $base, ?int $exceptId = null): string {
        $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($base)), '-') ?: 'shop';
        $slug = $base; $n = 1;
        while (true) {
            $row = $this->queryOne('SELECT id FROM review_branches WHERE slug = ?', [$slug]);
            if (!$row || ($exceptId !== null && (int) $row['id'] === $exceptId)) return $slug;
            $slug = $base . '-' . (++$n);
        }
    }
    public function addBranch(array $org, string $name, ?string $placeId, ?string $alertWa): int {
        $slug = $this->freeSlug((string) $org['slug'] . '-' . $name);
        return $this->insert('INSERT INTO review_branches (org_id, name, slug, place_id, alert_whatsapp) VALUES (?,?,?,?,?)', [(int) $org['id'], mb_substr($name, 0, 100), $slug, $placeId, $alertWa]);
    }
    public function updateBranch(int $orgId, int $id, string $name, ?string $placeId, ?string $alertWa, bool $active): bool {
        return $this->execute('UPDATE review_branches SET name = ?, place_id = ?, alert_whatsapp = ?, active = ? WHERE org_id = ? AND id = ?', [mb_substr($name, 0, 100), $placeId, $alertWa, $active ? 1 : 0, $orgId, $id]);
    }
    public function deleteBranch(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM review_branches WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    // ───────── page settings ─────────
    public function settings(array $org): array {
        $row = $this->queryOne('SELECT * FROM review_settings WHERE org_id = ?', [(int) $org['id']]) ?: [];
        $type = (string) ($org['business_type'] ?? 'both');
        $pos = self::DEFAULT_POS_TRADE; if ($type === 'service') $pos = self::DEFAULT_POS_SERVICE;
        $lines = fn(?string $s, array $d) => ($s !== null && trim($s) !== '') ? array_values(array_filter(array_map('trim', preg_split('/\R/', $s)))) : $d;
        $langs = array_values(array_intersect(explode(',', (string) ($row['langs'] ?? 'en,hi,bn')), ['en', 'hi', 'bn'])) ?: ['en'];
        $def = in_array($row['default_lang'] ?? 'en', $langs, true) ? $row['default_lang'] : $langs[0];
        return ['intro' => (string) ($row['intro'] ?? ''), 'chips_pos' => $lines($row['chips_pos'] ?? null, $pos), 'chips_neg' => $lines($row['chips_neg'] ?? null, self::DEFAULT_NEG),
                'langs' => $langs, 'default_lang' => $def, 'ai_enabled' => (int) ($row['ai_enabled'] ?? 1) === 1, 'logo' => (string) ($row['logo'] ?? '')];
    }
    public function saveSettings(int $orgId, array $d): bool {
        return $this->execute(
            'INSERT INTO review_settings (org_id, intro, chips_pos, chips_neg, langs, default_lang, ai_enabled) VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE intro = VALUES(intro), chips_pos = VALUES(chips_pos), chips_neg = VALUES(chips_neg), langs = VALUES(langs), default_lang = VALUES(default_lang), ai_enabled = VALUES(ai_enabled)',
            [$orgId, $d['intro'], $d['chips_pos'], $d['chips_neg'], $d['langs'], $d['default_lang'], $d['ai_enabled'] ? 1 : 0]
        );
    }
    public function setLogo(int $orgId, ?string $file): bool {
        return $this->execute('INSERT INTO review_settings (org_id, logo) VALUES (?, ?) ON DUPLICATE KEY UPDATE logo = VALUES(logo)', [$orgId, $file]);
    }

    // ───────── events & abuse limits ─────────
    public function log(int $orgId, int $branchId, ?int $staffId, string $event, ?int $rating, ?string $lang, ?string $chips, string $ipHash, string $deviceHash): void {
        $this->execute('INSERT INTO review_events (org_id, branch_id, staff_id, event, rating, lang, chips, ip_hash, device_hash) VALUES (?,?,?,?,?,?,?,?,?)',
            [$orgId, $branchId, $staffId, $event, $rating, $lang, $chips !== null ? mb_substr($chips, 0, 255) : null, $ipHash, $deviceHash]);
    }
    /** Seconds-window counts, used to stop one phone or address from spending the AI budget. */
    public function recent(string $event, string $col, string $hash, int $seconds): int {
        if (!in_array($col, ['ip_hash', 'device_hash'], true)) return 0;
        $r = $this->queryOne("SELECT COUNT(*) AS c FROM review_events WHERE event = ? AND {$col} = ? AND created_at >= (NOW() - INTERVAL {$seconds} SECOND)", [$event, $hash]);
        return (int) ($r['c'] ?? 0);
    }
    public function orgRecent(int $orgId, string $event, int $seconds): int {
        $r = $this->queryOne("SELECT COUNT(*) AS c FROM review_events WHERE org_id = ? AND event = ? AND created_at >= (NOW() - INTERVAL {$seconds} SECOND)", [$orgId, $event]);
        return (int) ($r['c'] ?? 0);
    }
    public function counts(int $orgId, int $days = 30): array {
        $rows = $this->query("SELECT event, COUNT(*) AS c FROM review_events WHERE org_id = ? AND created_at >= (NOW() - INTERVAL {$days} DAY) GROUP BY event", [$orgId]);
        $out = ['scan' => 0, 'draft' => 0, 'post_click' => 0];
        foreach ($rows as $r) $out[$r['event']] = (int) $r['c'];
        return $out;
    }
}
