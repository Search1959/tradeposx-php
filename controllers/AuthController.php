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
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = $org
                ? $this->userModel->findByEmailInOrg($org['id'], $email)
                : $this->userModel->findSuperAdminByEmail($email);

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
        $org = Tenant::current();
        redirect(($org ? $org['slug'] . '/' : '') . 'dashboard');
    }

    public function logoutAction(): void {
        $org = Tenant::current();
        Auth::logout();
        redirect($org ? $org['slug'] . '/login' : 'login');
    }
}
