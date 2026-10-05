<?php
class SuperAdminController {
    private Organization $orgModel;
    private User $userModel;

    public function __construct() {
        $this->orgModel = new Organization();
        $this->userModel = new User();
    }

    public function indexAction(): void {
        Auth::requireSuperAdmin();
        $organizations = $this->orgModel->allWithOwner();
        $users = $this->userModel->allWithOrg();
        $subs = (new OrgSubscription())->allByOrg();

        // Billable = live paying-type accounts: active only (demo and suspended are excluded).
        $ledger = [];
        $totals = ['billed' => 0.0, 'collected' => 0.0, 'outstanding' => 0.0];
        foreach ($organizations as $o) {
            if ($o['status'] !== 'active') continue;
            $s = $subs[(int) $o['id']] ?? ['monthly_fee' => 0.0, 'billing_start' => null, 'paid' => 0.0];
            $months = OrgSubscription::monthsBilled($s['billing_start'] ?: $o['created_at']);
            $due = $s['monthly_fee'] * $months;
            $balance = $due - $s['paid'];
            $ledger[] = $o + ['fee' => $s['monthly_fee'], 'months' => $months, 'due' => $due, 'paid' => $s['paid'], 'balance' => $balance];
            $totals['billed'] += $due;
            $totals['collected'] += $s['paid'];
            $totals['outstanding'] += max(0, $balance);
        }

        $stats = [
            'orgs' => count($organizations),
            'users' => count($users),
            'new30' => count(array_filter($organizations, fn($o) => strtotime($o['created_at']) >= strtotime('-30 days'))),
            'suspended' => count(array_filter($organizations, fn($o) => $o['status'] === 'suspended')),
        ];
        $tab = in_array($_GET['tab'] ?? '', ['firms', 'users', 'new', 'ledger'], true) ? $_GET['tab'] : 'firms';
        require __DIR__ . '/../views/superadmin/index.php';
    }

    public function setFeeAction(?string $id = null): void {
        Auth::requireSuperAdmin();
        CsrfHelper::validate();
        (new OrgSubscription())->setFee((int) $id, (float) ($_POST['fee'] ?? 0));
        redirect('superadmin?tab=ledger');
    }

    /** System Admin sets a new password for any non-admin account (passwords are hashed, so they can only be
     * replaced, never read back). The new password is shown once in the confirmation so it can be shared. */
    public function resetPasswordAction(?string $id = null): void {
        Auth::requireSuperAdmin();
        CsrfHelper::validate();
        $user = $this->userModel->find((int) $id);
        $new = $_POST['new_password'] ?? '';
        if (!$user || $user['role'] === 'superadmin') {
            $_SESSION['flash_success'] = 'That account cannot be reset here.';
        } elseif (strlen($new) < 6) {
            $_SESSION['flash_success'] = 'Password must be at least 6 characters.';
        } else {
            $this->userModel->updatePassword((int) $id, $new);
            $_SESSION['flash_success'] = "Password reset for {$user['name']} ({$user['email']}). New password: {$new} - share it with them; they can change it in Settings.";
        }
        redirect('superadmin?tab=users');
    }
    public function recordPaymentAction(?string $id = null): void {
        Auth::requireSuperAdmin();
        CsrfHelper::validate();
        $ok = (new OrgSubscription())->recordPayment((int) $id, (float) ($_POST['amount'] ?? 0), $_POST['note'] ?? null);
        if ($ok) $_SESSION['flash_success'] = 'Payment recorded.';
        redirect('superadmin?tab=ledger');
    }

    private function slugify(string $name): string {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-') ?: 'store';
    }

    public function newOrgAction(): void {
        Auth::requireSuperAdmin();
        $error = null;
        $form = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $form = $_POST;
            $name = trim($_POST['name'] ?? '');
            $ownerName = trim($_POST['owner_name'] ?? '');
            $ownerEmail = trim($_POST['owner_email'] ?? '');
            $ownerPassword = $_POST['owner_password'] ?? '';
            $slug = trim($_POST['slug'] ?? '') !== '' ? $this->slugify($_POST['slug']) : $this->slugify($name);

            if ($name === '' || $ownerName === '' || $ownerEmail === '' || strlen($ownerPassword) < 6) {
                $error = 'Business name, owner name/email, and a password of at least 6 characters are required.';
            } elseif ($this->orgModel->slugExists($slug)) {
                $error = "The URL slug \"{$slug}\" is already taken — try a different business name or set a custom slug.";
            } else {
                $orgId = $this->orgModel->create([
                    'slug' => $slug, 'name' => $name, 'business_type' => $_POST['business_type'] ?? 'both',
                    'trading_sector' => $_POST['trading_sector'] ?? null, 'status' => 'active',
                ]);
                $this->userModel->create([
                    'org_id' => $orgId, 'name' => $ownerName, 'email' => $ownerEmail,
                    'password' => $ownerPassword, 'role' => 'owner', 'status' => 'active',
                ]);
                redirect('superadmin');
                return;
            }
        }

        require __DIR__ . '/../views/superadmin/new-org.php';
    }

    public function suspendAction(?string $id = null): void {
        Auth::requireSuperAdmin();
        CsrfHelper::validate();
        $this->orgModel->updateStatus((int) $id, 'suspended');
        redirect('superadmin');
    }

    public function activateAction(?string $id = null): void {
        Auth::requireSuperAdmin();
        CsrfHelper::validate();
        $this->orgModel->updateStatus((int) $id, 'active');
        redirect('superadmin');
    }

    /** Generates (or regenerates) the sales-demo organization — every id is
     * assigned by this database's own AUTO_INCREMENT via lastInsertId(), so
     * it's safe to run here regardless of what else already exists — unlike
     * a hand-crafted SQL dump with hardcoded ids carried over from a
     * different database, which can collide with real rows. */
    public function seedDemoAction(): void {
        Auth::requireSuperAdmin();
        CsrfHelper::validate();
        $db = Database::getInstance()->getConnection();
        $result = DemoSeeder::seed($db);
        $_SESSION['flash_success'] = "Demo org ready — {$result['salesCreated']} sales, {$result['invoicesCreated']} service invoices."
            . ($result['removed'] ? ' (previous demo data replaced.)' : '')
            . ' Login: demo@tradeposx.com / Demo@2026';
        redirect('superadmin');
    }

    /** Full-platform disaster-recovery backup — every organization's data in
     * one file. SuperAdmin only; never exposed at the tenant level. */
    public function backupAction(): void {
        Auth::requireSuperAdmin();
        $db = Database::getInstance()->getConnection();
        $sql = BackupHelper::exportFullDatabase($db);

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="tradeposx-full-backup-' . date('Y-m-d-His') . '.sql"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
    }

    /** Backing up a single org on a tenant's behalf (e.g. support request) —
     * still SuperAdmin-only, and still scoped to that one org's data only. */
    public function backupOrgAction(?string $id = null): void {
        Auth::requireSuperAdmin();
        $org = $this->orgModel->find((int) $id);
        if (!$org) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $db = Database::getInstance()->getConnection();
        $sql = BackupHelper::exportOrg($db, (int) $id);

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $org['slug'] . '-backup-' . date('Y-m-d') . '.sql"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
    }
}
