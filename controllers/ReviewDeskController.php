<?php
/** Owner/admin area of ReviewDesk: Google link(s), what the customer page asks, logo, and printable QR codes. */
class ReviewDeskController {
    private ReviewDesk $rd;

    /** Words that would make a review request look like it is paid for. Google bans incentivised reviews. */
    private const INCENTIVE = ['discount', 'offer', 'free', 'gift', 'reward', 'coupon', 'cashback', 'voucher', 'prize', 'lucky draw', 'छूट', 'डिस्काउंट', 'उपहार', 'इनाम', 'मुफ़्त', 'मुफ्त', 'ছাড়', 'উপহার', 'পুরস্কার', 'বিনামূল্যে'];

    public function __construct() { $this->rd = new ReviewDesk(); }

    private function guard(): array {
        $org = Tenant::current();
        Auth::requireRole(['owner', 'admin']);
        Auth::requireOrgAccess($org['id']);
        return $org;
    }
    private function flash(string $kind, string $msg): void { $_SESSION['flash_' . $kind] = $msg; }
    private function incentive(string $text): ?string {
        $low = mb_strtolower($text);
        foreach (self::INCENTIVE as $w) if (mb_strpos($low, $w) !== false) return $w;
        return null;
    }
    /** Returns the cleaned value, null when blank, or false when invalid. No ": ?string" return type on purpose —
     * PHP would silently turn false into "" and an invalid entry would then be saved as a blank, wiping the old link. */
    private function placeId(string $raw) {
        $raw = trim($raw);
        if ($raw === '') return null;
        // Google's own "Get more reviews" short link (g.page/r/…/review) is the most reliable on phones — keep it as is.
        if (preg_match('#^https://g\.page/[A-Za-z0-9_\-/]+(\?[A-Za-z0-9_=&\-]*)?$#', $raw) && mb_strlen($raw) <= 190) return $raw;
        if (preg_match('/placeid=([A-Za-z0-9_\-]+)/', $raw, $m)) $raw = $m[1];        // a pasted write-review link works too
        return preg_match('/^[A-Za-z0-9_\-]{10,200}$/', $raw) ? $raw : false;
    }
    private function whatsapp(string $raw) {   // null = blank, false = invalid (see placeId)
        $d = preg_replace('/\D/', '', $raw);
        if ($d === '') return null;
        if (strlen($d) === 10) $d = '91' . $d;
        return preg_match('/^\d{11,15}$/', $d) ? $d : false;
    }
    private function lines(string $raw, int $max = 12): array {
        $out = [];
        foreach (preg_split('/\R/', $raw) as $l) {
            $l = trim(strip_tags($l));
            if ($l !== '') $out[mb_strtolower($l)] = mb_substr($l, 0, 40);
        }
        return array_slice(array_values($out), 0, $max);
    }

    public function indexAction(): void {
        $org = $this->guard();
        $branches = $this->rd->ensureDefaultBranch($org);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $form = $_POST['form'] ?? '';
            if ($form === 'branch') $this->saveBranch($org);
            elseif ($form === 'branch_add') $this->addBranch($org);
            elseif ($form === 'branch_delete') $this->deleteBranch($org);
            elseif ($form === 'page') $this->savePage($org);
            elseif ($form === 'logo') $this->saveLogo($org);
            redirect($org['slug'] . '/reviewdesk');
            return;
        }

        $settings = $this->rd->settings($org);
        $stats = $this->rd->counts((int) $org['id']);
        $base = '/' . $org['slug'];
        $host = ($this->isHttps() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'tradeposx.com');
        $pageTitle = 'Google Reviews';
        require __DIR__ . '/../views/reviewdesk/index.php';
    }

    private function isHttps(): bool { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'); }

    private function saveBranch(array $org): void {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$this->rd->branch((int) $org['id'], $id)) return;
        $name = trim((string) ($_POST['name'] ?? ''));
        $place = $this->placeId((string) ($_POST['place_id'] ?? ''));
        $wa = $this->whatsapp((string) ($_POST['alert_whatsapp'] ?? ''));
        if ($name === '') { $this->flash('error', 'Please give the branch a name.'); return; }
        if ($place === false) { $this->flash('error', 'That does not look like a Google Place ID. It starts with “ChIJ…”. You can also paste the full “write a review” link.'); return; }
        if ($wa === false) { $this->flash('error', 'Please enter a valid WhatsApp number for alerts (10 digits).'); return; }
        $this->rd->updateBranch((int) $org['id'], $id, $name, $place, $wa, !empty($_POST['active']));
        $this->flash('success', 'Branch saved.');
    }
    private function addBranch(array $org): void {
        $name = trim((string) ($_POST['name'] ?? ''));
        $place = $this->placeId((string) ($_POST['place_id'] ?? ''));
        if ($name === '') { $this->flash('error', 'Please give the new branch a name.'); return; }
        if ($place === false) { $this->flash('error', 'That does not look like a Google Place ID.'); return; }
        if (count($this->rd->branches((int) $org['id'])) >= 20) { $this->flash('error', 'You can add up to 20 branches.'); return; }
        $this->rd->addBranch($org, $name, $place, null);
        $this->flash('success', 'Branch added.');
    }
    private function deleteBranch(array $org): void {
        $branches = $this->rd->branches((int) $org['id']);
        if (count($branches) <= 1) { $this->flash('error', 'Keep at least one branch.'); return; }
        $this->rd->deleteBranch((int) $org['id'], (int) ($_POST['id'] ?? 0));
        $this->flash('success', 'Branch removed.');
    }

    private function savePage(array $org): void {
        $intro = mb_substr(trim(strip_tags((string) ($_POST['intro'] ?? ''))), 0, 160);
        $pos = $this->lines((string) ($_POST['chips_pos'] ?? ''));
        $neg = $this->lines((string) ($_POST['chips_neg'] ?? ''));
        foreach (array_merge([$intro], $pos, $neg) as $t) {
            if ($t !== '' && ($w = $this->incentive($t)) !== null) {
                $this->flash('error', "“{$t}” mentions “{$w}”. Google does not allow rewards, discounts or gifts in return for reviews, so this was not saved. Please reword it.");
                return;
            }
        }
        if (count($pos) < 2) { $this->flash('error', 'Please keep at least 2 “what did you like” options.'); return; }
        $langs = array_values(array_intersect((array) ($_POST['langs'] ?? []), ['en', 'hi', 'bn']));
        if (!$langs) $langs = ['en'];
        $def = in_array($_POST['default_lang'] ?? '', $langs, true) ? $_POST['default_lang'] : $langs[0];
        $this->rd->saveSettings((int) $org['id'], ['intro' => $intro, 'chips_pos' => implode("\n", $pos), 'chips_neg' => implode("\n", $neg), 'langs' => implode(',', $langs), 'default_lang' => $def, 'ai_enabled' => !empty($_POST['ai_enabled'])]);
        $this->flash('success', 'Customer page saved.');
    }

    private function saveLogo(array $org): void {
        if (!empty($_POST['remove_logo'])) {
            $cur = $this->rd->settings($org)['logo'];
            if ($cur !== '') @unlink(__DIR__ . '/../uploads/reviewdesk/' . basename($cur));
            $this->rd->setLogo((int) $org['id'], null);
            $this->flash('success', 'Logo removed.');
            return;
        }
        $f = $_FILES['logo'] ?? null;
        if (!$f || ($f['error'] ?? 4) !== UPLOAD_ERR_OK) { $this->flash('error', 'Please choose a logo image first.'); return; }
        if ($f['size'] > 1500000) { $this->flash('error', 'The logo is too big — please use an image under 1.5 MB.'); return; }
        $info = @getimagesize($f['tmp_name']);
        $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if (!$info || !$ext || $info[0] > 3000 || $info[1] > 3000) { $this->flash('error', 'Please upload a PNG, JPG or WebP logo (up to 3000 px).'); return; }
        $dir = __DIR__ . '/../uploads/reviewdesk';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $name = 'org' . (int) $org['id'] . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { $this->flash('error', 'Could not save the logo on the server.'); return; }
        $old = $this->rd->settings($org)['logo'];
        if ($old !== '') @unlink($dir . '/' . basename($old));
        $this->rd->setLogo((int) $org['id'], $name);
        $this->flash('success', 'Logo saved.');
    }

    // ───────── printable QR (A6 counter stand + sticker sheet) ─────────
    public function qrAction(): void {
        $org = $this->guard();
        $branches = $this->rd->ensureDefaultBranch($org);
        $branch = $this->rd->branch((int) $org['id'], (int) ($_GET['branch'] ?? 0)) ?: $branches[0];
        $staffId = null; $staffName = '';
        if (!empty($_GET['staff'])) {
            $u = (new User())->find((int) $_GET['staff']);
            if ($u && (int) $u['org_id'] === (int) $org['id']) { $staffId = (int) $u['id']; $staffName = (string) $u['name']; }
        }
        $kind = ($_GET['kind'] ?? 'stand') === 'sticker' ? 'sticker' : 'stand';
        $settings = $this->rd->settings($org);
        $host = ($this->isHttps() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'tradeposx.com');
        $url = $host . '/review/' . $branch['slug'] . ($staffId ? '?staff=' . $staffId : '');
        $logo = $settings['logo'] !== '' ? '/uploads/reviewdesk/' . $settings['logo'] : '';
        $base = '/' . $org['slug'];
        require __DIR__ . '/../views/reviewdesk/qr.php';
    }
}
