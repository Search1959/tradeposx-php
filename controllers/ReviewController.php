<?php
/**
 * Public ReviewDesk page (no login): /review/{slug}[?staff=ID]
 * Customer picks stars + aspects (+ optional note) → drafts are suggested → the customer edits, copies and posts on Google
 * from their own account. Built to Google's review policy:
 *   - every star rating can reach Google, nothing is gated or redirected away from it;
 *   - drafts use only what the customer chose, and nothing is ever posted for them;
 *   - nothing on the page offers or implies a reward for reviewing.
 */
class ReviewController {
    private ReviewDesk $rd;
    private Organization $orgModel;

    // Abuse limits for draft generation (each draft may cost an AI call).
    private const DEVICE_10MIN = 6;
    private const IP_10MIN     = 12;
    private const IP_DAY       = 60;
    private const ORG_DAY      = 400;

    public function __construct() {
        $this->rd = new ReviewDesk();
        $this->orgModel = new Organization();
    }

    // ───────── helpers ─────────
    private function ipHash(): string { return substr(hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), 'reviewdesk-v1'), 0, 16); }

    private function deviceHash(): string {
        $id = $_COOKIE['rd_dev'] ?? '';
        if (!preg_match('/^[a-f0-9]{24}$/', $id)) {
            $id = bin2hex(random_bytes(12));
            $this->cookie('rd_dev', $id, time() + 86400 * 365, true);
        }
        return substr(hash('sha256', $id), 0, 16);
    }

    private function cookie(string $name, string $value, int $expires, bool $httpOnly): void {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie($name, $value, ['expires' => $expires, 'path' => '/review', 'secure' => $https, 'httponly' => $httpOnly, 'samesite' => 'Lax']);
    }

    private function json(array $a, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($a, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function unavailable(string $msg): void {
        http_response_code(404);
        $message = $msg;
        require __DIR__ . '/../views/review/unavailable.php';
        exit;
    }

    /** @return array{0: array, 1: array, 2: array} branch, organisation, page settings */
    private function load(?string $slug): array {
        $b = $this->rd->branchBySlug(strtolower(trim((string) $slug)));
        if (!$b) $this->unavailable('This review page was not found.');
        $org = $this->orgModel->find((int) $b['org_id']);
        if (!$org || ($org['status'] ?? '') !== 'active') $this->unavailable('This review page is not available right now.');
        return [$b, $org, $this->rd->settings($org)];
    }

    private function staffId(int $orgId, $raw): ?int {
        $id = (int) $raw;
        if ($id <= 0) return null;
        $r = (new User())->find($id);
        return ($r && (int) $r['org_id'] === $orgId) ? $id : null;
    }

    private function checkToken(): void {
        $hdr = (string) ($_SERVER['HTTP_X_RD_TOKEN'] ?? '');
        $cook = (string) ($_COOKIE['rd_tok'] ?? '');
        if ($hdr === '' || !hash_equals($cook, $hdr)) $this->json(['ok' => false, 'error' => 'Please reload the page and try again.'], 403);
    }

    private function body(): array {
        $in = json_decode((string) file_get_contents('php://input'), true);
        return is_array($in) ? $in : [];
    }

    // ───────── the page ─────────
    public function showAction(?string $slug = null): void {
        [$b, $org, $s] = $this->load($slug);
        $orgId = (int) $org['id'];
        $isPreview = isset($_GET['preview']) && Auth::check() && Auth::orgId() === $orgId;
        $placeId = trim((string) ($b['place_id'] ?? ''));
        if ($placeId === '' && !$isPreview) $this->unavailable('This shop has not finished setting up its review page yet.');

        $staff = $this->staffId($orgId, $_GET['staff'] ?? null);
        $device = $this->deviceHash();
        $token = bin2hex(random_bytes(16));
        $this->cookie('rd_tok', $token, 0, true);
        if (!$isPreview && $this->rd->recent('scan', 'device_hash', $device, 1800) === 0) {
            $this->rd->log($orgId, (int) $b['id'], $staff, 'scan', null, null, null, $this->ipHash(), $device);
        }
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex');
        $cfg = [
            'shop' => (string) $b['name'], 'orgName' => (string) $org['name'], 'slug' => (string) $b['slug'], 'staff' => $staff,
            'intro' => $s['intro'], 'logo' => $s['logo'] !== '' ? '/uploads/reviewdesk/' . $s['logo'] : '',
            'chipsPos' => $s['chips_pos'], 'chipsNeg' => $s['chips_neg'], 'langs' => $s['langs'], 'lang' => $s['default_lang'], 'ai' => $s['ai_enabled'],
            'writeUrl' => $placeId !== '' ? 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId) : '',
            'preview' => $isPreview, 'token' => $token,
        ];
        require __DIR__ . '/../views/review/public.php';
    }

    // ───────── POST /review/{slug}/draft ─────────
    public function draftAction(?string $slug = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'error' => 'POST only.'], 405);
        $this->checkToken();
        [$b, $org, $s] = $this->load($slug);
        $orgId = (int) $org['id'];
        $in = $this->body();

        $rating = (int) ($in['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) $this->json(['ok' => false, 'error' => 'Please choose a star rating first.'], 422);
        $lang = (string) ($in['lang'] ?? 'en');
        if (!in_array($lang, $s['langs'], true)) $lang = $s['default_lang'];

        // Only aspects that this shop actually offers are accepted — nothing free-form reaches the AI except the short note.
        $allowed = [];
        foreach (array_merge($s['chips_pos'], $s['chips_neg']) as $c) $allowed[mb_strtolower($c)] = $c;
        $chips = [];
        foreach ((array) ($in['chips'] ?? []) as $c) { $k = mb_strtolower(trim((string) $c)); if (isset($allowed[$k])) $chips[$allowed[$k]] = $allowed[$k]; }
        $chips = array_values($chips);
        $note = ReviewAi::cleanNote((string) ($in['note'] ?? ''));
        if (!$chips && $note === '') $this->json(['ok' => false, 'error' => 'Please pick at least one thing, or write a short note, so the suggestion reflects your visit.'], 422);
        if (!$s['ai_enabled']) $this->json(['ok' => false, 'error' => 'Suggestions are switched off for this shop. You can still write your own review on Google.'], 403);

        $ip = $this->ipHash(); $device = $this->deviceHash();
        if ($this->rd->recent('draft', 'device_hash', $device, 600) >= self::DEVICE_10MIN || $this->rd->recent('draft', 'ip_hash', $ip, 600) >= self::IP_10MIN
            || $this->rd->recent('draft', 'ip_hash', $ip, 86400) >= self::IP_DAY || $this->rd->orgRecent($orgId, 'draft', 86400) >= self::ORG_DAY) {
            $this->json(['ok' => false, 'error' => 'That was a lot of suggestions in a short time. Please wait a few minutes — or write your own review on Google.'], 429);
        }

        $res = ReviewAi::drafts((string) $b['name'], $rating, $chips, $note, $lang);
        $isPreview = !empty($in['preview']) && Auth::check() && Auth::orgId() === $orgId;
        if (!$isPreview) $this->rd->log($orgId, (int) $b['id'], $this->staffId($orgId, $in['staff'] ?? null), 'draft', $rating, $lang, implode(', ', $chips), $ip, $device);
        $this->json(['ok' => true, 'drafts' => $res['drafts'], 'source' => $res['source']]);
    }

    // ───────── POST /review/{slug}/click ─────────  (the customer tapped "Copy & Post on Google")
    public function clickAction(?string $slug = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'error' => 'POST only.'], 405);
        $this->checkToken();
        [$b, $org] = $this->load($slug);
        $in = $this->body();
        if (!empty($in['preview'])) $this->json(['ok' => true]);
        $rating = (int) ($in['rating'] ?? 0);
        $this->rd->log((int) $org['id'], (int) $b['id'], $this->staffId((int) $org['id'], $in['staff'] ?? null), 'post_click', $rating >= 1 && $rating <= 5 ? $rating : null,
            in_array($in['lang'] ?? '', ['en', 'hi', 'bn'], true) ? $in['lang'] : null, ($in['mode'] ?? '') === 'draft' ? 'draft' : 'own', $this->ipHash(), $this->deviceHash());
        $this->json(['ok' => true]);
    }
}
