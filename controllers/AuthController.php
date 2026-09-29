<?php
class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /** GET/POST /{org-slug}/login or /login (platform superadmin). */
    public function indexAction(): void {
        $org = Tenant::current();

        if (Auth::check()) {
            $this->redirectHomeAction();
            return;
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $identifier = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = $org
                ? $this->userModel->findByLoginInOrg($org['id'], $identifier)
                : $this->userModel->findSuperAdminByEmail($identifier);

            if ($user && $user['status'] === 'active' && $this->userModel->verifyPassword($user, $password)) {
                Auth::login($user);
                $this->redirectHomeAction();
                return;
            }
            $error = 'Invalid email or password.';
        }

        require __DIR__ . '/../views/auth/login.php';
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
        redirect(($org ? $org['slug'] . '/' : '') . 'dashboard');
    }

    public function logoutAction(): void {
        $org = Tenant::current();
        Auth::logout();
        redirect($org ? $org['slug'] . '/login' : 'login');
    }
}
