<?php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

date_default_timezone_set(APP_TIMEZONE);

error_reporting(E_ALL);
ini_set('display_errors', 0); // flip to 1 only while developing locally
ini_set('log_errors', 1);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/models/' . $class . '.php',
        ROOT_PATH . '/includes/' . $class . '.php',
        ROOT_PATH . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// ── Global helpers ──────────────────────────────────────────────────────

function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function formatCurrency(float $amount): string {
    return '₹' . number_format($amount, 2);
}

function formatDate(?string $date): string {
    return $date ? date('d M Y', strtotime($date)) : '-';
}

function formatDateTime(?string $dt): string {
    return $dt ? date('d M Y, h:i A', strtotime($dt)) : '-';
}

/** Random client-generated reference for idempotent offline-sale sync — see
 * sales.client_ref / service_invoices.client_ref in sql/schema.sql. */
function generateClientRef(): string {
    return bin2hex(random_bytes(16));
}

function logActivity(int $orgId, string $action, string $entityType = '', int $entityId = 0, string $desc = ''): void {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'INSERT INTO activity_log (org_id, user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([$orgId, Auth::user()['id'] ?? null, $action, $entityType, $entityId, $desc, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Non-fatal — never let audit logging break the actual request.
    }
}
