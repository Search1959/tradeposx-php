<?php
class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /** GET/POST /{org-slug}/login (existing client, this org only) or
     * /login (platform-level — unified page: Login / New Client / Demo / Admin tabs). */
    public function indexAction(): void {
        $org = Tenant::current();

        if (Auth::check()) {
            $this->redirectHomeAction();
            return;
        }

        $error = null;
        $activeTab = null;
        $form = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $identifier = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $loginAs = $_POST['login_as'] ?? 'tenant';
            $form = ['email' => $identifier];
            $activeTab = $org ? null : $loginAs;

            if ($org) {
                $user = $this->userModel->findByLoginInOrg($org['id'], $identifier);
            } elseif ($loginAs === 'admin') {
                $user = $this->userModel->findSuperAdminByEmail($identifier);
            } else {
                $user = $this->userModel->findByEmailAcrossOrgs($identifier);
            }

            if ($user && $user['status'] === 'active' && $this->userModel->verifyPassword($user, $password)) {
                Auth::login($user);
                $this->redirectHomeAction();
                return;
            }
            $error = 'Invalid email or password.';
        }

        require __DIR__ . '/../views/auth/' . ($org ? 'login.php' : 'unified-login.php');
    }

    /** POST /login/register — public self-service signup: creates the org
     * and its first owner account, then logs straight in. Platform-level only. */
    public function registerAction(): void {
        if (Auth::check()) {
            $this->redirectHomeAction();
            return;
        }

        $error = null;
        $activeTab = 'new';
        $form = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $form = $_POST;
            $name = trim($_POST['name'] ?? '');
            $ownerName = trim($_POST['owner_name'] ?? '');
            $ownerEmail = trim($_POST['owner_email'] ?? '');
            $ownerPassword = $_POST['owner_password'] ?? '';
            $businessType = in_array($_POST['business_type'] ?? '', ['trade', 'service', 'both'], true) ? $_POST['business_type'] : 'both';
            $tradingSector = trim($_POST['trading_sector'] ?? '') !== '' ? $_POST['trading_sector'] : null;
            $orgModel = new Organization();

            if ($name === '' || $ownerName === '' || $ownerEmail === '' || strlen($ownerPassword) < 6) {
                $error = 'Business name, your name/email, and a password of at least 6 characters are required.';
            } elseif (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif ($this->userModel->findByEmailAcrossOrgs($ownerEmail)) {
                $error = 'An account with this email already exists — try logging in instead.';
            } else {
                $slug = $this->slugify($name);
                $baseSlug = $slug;
                $suffix = 1;
                while ($orgModel->slugExists($slug)) {
                    $slug = $baseSlug . '-' . (++$suffix);
                }
                $orgId = $orgModel->create([
                    'slug' => $slug, 'name' => $name, 'business_type' => $businessType,
                    'trading_sector' => $tradingSector, 'status' => 'active',
                ]);
                $userId = $this->userModel->create([
                    'org_id' => $orgId, 'name' => $ownerName, 'email' => $ownerEmail,
                    'password' => $ownerPassword, 'role' => 'owner', 'status' => 'active',
                ]);
                Auth::login($this->userModel->find($userId));
                $this->redirectHomeAction();
                return;
            }
        }

        require __DIR__ . '/../views/auth/unified-login.php';
    }

    /** GET /login/demo — instant one-click login to the seeded showcase business. */
    public function demoAction(): void {
        $user = $this->userModel->findByEmailAcrossOrgs('demo@tradeposx.com');
        if ($user && $user['status'] === 'active') {
            Auth::login($user);
            $this->redirectHomeAction();
            return;
        }
        redirect('login');
    }

    private function slugify(string $name): string {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-') ?: 'store';
    }

    public function redirectHomeAction(): void {
        if (Auth::isSuperAdmin() && !Tenant::current()) {
            redirect('superadmin');
            return;
        }
        // On a platform-level route (bare /login, no org slug in the URL),
        // Tenant::current() is never resolved even though the session already
        // has an org — falling back to Auth::orgId() avoids redirecting a
        // logged-in tenant user to a slug-less /dashboard that 404s.
        $org = Tenant::current();
        if (!$org && Auth::orgId()) {
            $org = (new Organization())->find(Auth::orgId());
        }
        // A "both" business picks TradeERP or ServiceERP right after logging in
        // (matching how the old app kept them as genuinely separate logins) —
        // trade-only/service-only orgs have nothing to choose, so they go
        // straight to the dashboard as before.
        $landingPage = ($org && $org['business_type'] === 'both') ? 'choose' : 'dashboard';
        redirect(($org ? $org['slug'] . '/' : '') . $landingPage);
    }

    public function logoutAction(): void {
        $org = Tenant::current();
        Auth::logout();
        redirect($org ? $org['slug'] . '/login' : 'login');
    }
}
