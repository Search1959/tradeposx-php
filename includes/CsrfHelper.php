<?php
class CsrfHelper {
    public static function generateToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getField(): string {
        return '<input type="hidden" name="csrf_token" value="' . h(self::generateToken()) . '">';
    }

    public static function validate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Invalid CSRF token. Please go back and try again.');
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
