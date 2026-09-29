<?php
class Auth {
    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array {
        if (!self::check()) return null;
        $userModel = new User();
        return $userModel->find((int) $_SESSION['user_id']);
    }

    public static function role(): ?string {
        return $_SESSION['user_role'] ?? null;
    }

    public static function orgId(): ?int {
        return isset($_SESSION['org_id']) ? (int) $_SESSION['org_id'] : null;
    }

    public static function isSuperAdmin(): bool {
        return self::role() === 'superadmin';
    }

    public static function login(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['org_id']    = $user['org_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void {
        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            self::redirectToLogin();
        }
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            self::logout();
            self::redirectToLogin();
        }
        $_SESSION['last_activity'] = time();
    }

    /** Gates a feature to specific roles. superadmin always passes. */
    public static function requireRole(array $roles): void {
        self::requireLogin();
        if (self::isSuperAdmin()) return;
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            include __DIR__ . '/../views/403.php';
            exit;
        }
    }

    // The tenant-isolation guard: every tenant-scoped controller action calls this
    // with Tenant::id() before touching any data. superadmin bypasses (platform
    // support access); every other logged-in user must belong to THIS org, not
    // just be logged in somewhere — this is what stops the cross-tenant data leaks
    // that showed up post-launch on the School ERP project.
    public static function requireOrgAccess(int $orgId): void {
        self::requireLogin();
        if (self::isSuperAdmin()) return;
        if (self::orgId() !== $orgId) {
            http_response_code(403);
            include __DIR__ . '/../views/403.php';
            exit;
        }
    }

    public static function requireSuperAdmin(): void {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            http_response_code(403);
            include __DIR__ . '/../views/403.php';
            exit;
        }
    }

    private static function redirectToLogin(): void {
        $org = Tenant::current();
        $target = $org ? $org['slug'] . '/login' : 'login';
        header('Location: ' . BASE_URL . '/index.php?url=' . $target);
        exit;
    }
}
