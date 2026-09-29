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

/** "Rupees X and Y Paise Only" — the amount-in-words line expected on an Indian tax invoice. */
function amountInWordsINR(float $amount): string {
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $twoDigits = function (int $n) use ($ones, $tens): string {
        if ($n < 20) return $ones[$n];
        return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
    };
    $threeDigits = function (int $n) use ($twoDigits): string {
        if ($n < 100) return $twoDigits($n);
        $rest = $n % 100;
        return trim($twoDigits(intdiv($n, 100)) . ' Hundred' . ($rest ? ' ' . $twoDigits($rest) : ''));
    };

    $rupees = (int) floor($amount);
    $paise = (int) round(($amount - $rupees) * 100);

    if ($rupees === 0) {
        $words = 'Zero';
    } else {
        $parts = [];
        $crore = intdiv($rupees, 10000000); $rupees %= 10000000;
        $lakh = intdiv($rupees, 100000); $rupees %= 100000;
        $thousand = intdiv($rupees, 1000); $rupees %= 1000;
        $hundred = $rupees;
        if ($crore) $parts[] = $threeDigits($crore) . ' Crore';
        if ($lakh) $parts[] = $threeDigits($lakh) . ' Lakh';
        if ($thousand) $parts[] = $threeDigits($thousand) . ' Thousand';
        if ($hundred) $parts[] = $threeDigits($hundred);
        $words = implode(' ', $parts);
    }

    $result = "Rupees {$words}";
    if ($paise > 0) $result .= ' and ' . $twoDigits($paise) . ' Paise';
    return $result . ' Only';
}

/** A wa.me click-to-chat link — opens WhatsApp with the message pre-filled, no
 * API/credentials needed (unlike an automated send via Green API/Meta Cloud,
 * which config/constants.php has placeholders for once that's set up). Assumes
 * a bare 10-digit Indian mobile number if no country code is already present,
 * matching how every number in this app is stored (see customers.mobile etc). */
function whatsappLink(string $mobile, string $message): string {
    $digits = preg_replace('/\D/', '', $mobile);
    if (strlen($digits) === 10) {
        $digits = '91' . $digits;
    }
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
}

/** A bill-style Udhaar reminder using WhatsApp's own text formatting (*bold*, monospace
 * ``` blocks for an aligned mini-table) instead of one plain sentence. $recentCredits is
 * CustomerTransaction::recentCredits() output — each entry becomes one line item; pass
 * [] to omit that section (e.g. if the caller didn't load it). */
function udhaarReminderMessage(string $businessName, string $customerName, float $balance, array $recentCredits = [], ?string $businessPhone = null): string {
    $lines = [];
    $lines[] = "🧾 *{$businessName}*";
    $lines[] = "_Udhaar Reminder Statement_";
    $lines[] = "";
    $lines[] = "Dear {$customerName},";
    $lines[] = "";

    if (!empty($recentCredits)) {
        $lines[] = "Recent entries on your account:";
        $lines[] = "```";
        $lines[] = str_pad('Date', 12) . str_pad('Ref#', 12) . 'Amount';
        foreach ($recentCredits as $t) {
            $date = str_pad(date('d-M-y', strtotime($t['created_at'])), 12);
            $ref = str_pad(substr($t['reference_id'] ?? '—', 0, 10), 12);
            $amt = number_format((float) $t['amount'], 2);
            $lines[] = $date . $ref . $amt;
        }
        $lines[] = "```";
        $lines[] = "";
    }

    $lines[] = "*Total Outstanding: " . formatCurrency($balance) . "*";
    $lines[] = "";
    $lines[] = "Kindly clear your dues at your earliest convenience.";
    if ($businessPhone) {
        $lines[] = "📞 {$businessPhone}";
    }
    $lines[] = "🙏 Thank you for your business — {$businessName}";

    return implode("\n", $lines);
}

/** Same bill-style formatting as udhaarReminderMessage(), for a single ServiceERP
 * job card or invoice balance — there's no aggregate customer ledger on that side
 * (see CustomerTransaction vs. job_cards/service_invoices' own balance_amount), so
 * this reminds against one specific reference rather than a running total. */
function serviceBalanceReminderMessage(string $businessName, string $customerName, string $refNo, float $balance, ?string $businessPhone = null): string {
    $lines = [];
    $lines[] = "🧾 *{$businessName}*";
    $lines[] = "_Payment Reminder_";
    $lines[] = "";
    $lines[] = "Dear {$customerName},";
    $lines[] = "";
    $lines[] = "This is a reminder for *{$refNo}*:";
    $lines[] = "*Balance Due: " . formatCurrency($balance) . "*";
    $lines[] = "";
    $lines[] = "Kindly clear your dues at your earliest convenience.";
    if ($businessPhone) {
        $lines[] = "📞 {$businessPhone}";
    }
    $lines[] = "🙏 Thank you for your business — {$businessName}";

    return implode("\n", $lines);
}

/** Full itemized bill as a WhatsApp message — for the "📤 Send Bill" button, distinct
 * from udhaarReminderMessage() which only nags about an outstanding balance. $items
 * is $sale['items'] (product_name, quantity, unit_price, total_price). */
function saleBillWhatsAppMessage(string $businessName, string $saleNumber, string $customerName, array $items, float $grandTotal, float $paid, float $balance, ?string $businessPhone = null): string {
    $lines = [];
    $lines[] = "🧾 *{$businessName}*";
    $lines[] = "_Bill No: {$saleNumber}_";
    $lines[] = "";
    $lines[] = "Dear {$customerName},";
    $lines[] = "Thank you for your purchase! Here's your bill:";
    $lines[] = "";
    $lines[] = "```";
    foreach ($items as $item) {
        $qty = rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.');
        $lines[] = $item['product_name'];
        $lines[] = '  ' . $qty . ' x ' . number_format((float) $item['unit_price'], 2) . ' = ' . number_format((float) $item['total_price'], 2);
    }
    $lines[] = "```";
    $lines[] = "";
    $lines[] = "*Grand Total: " . formatCurrency($grandTotal) . "*";
    $lines[] = "Paid: " . formatCurrency($paid);
    if ($balance > 0) {
        $lines[] = "*Balance (Udhaar): " . formatCurrency($balance) . "*";
    }
    $lines[] = "";
    if ($businessPhone) {
        $lines[] = "📞 {$businessPhone}";
    }
    $lines[] = "🙏 Thank you for your business — {$businessName}";

    return implode("\n", $lines);
}

/** Same bill-style formatting as saleBillWhatsAppMessage(), for a ServiceERP invoice.
 * $items is $invoice['items'] (name, quantity, price, total). */
function serviceInvoiceWhatsAppMessage(string $businessName, string $invoiceNo, string $customerName, array $items, float $grandTotal, float $paid, float $balance, ?string $businessPhone = null): string {
    $lines = [];
    $lines[] = "🧾 *{$businessName}*";
    $lines[] = "_Invoice No: {$invoiceNo}_";
    $lines[] = "";
    $lines[] = "Dear {$customerName},";
    $lines[] = "Thank you for your business! Here's your invoice:";
    $lines[] = "";
    $lines[] = "```";
    foreach ($items as $item) {
        $qty = rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.');
        $lines[] = $item['name'];
        $lines[] = '  ' . $qty . ' x ' . number_format((float) $item['price'], 2) . ' = ' . number_format((float) $item['total'], 2);
    }
    $lines[] = "```";
    $lines[] = "";
    $lines[] = "*Grand Total: " . formatCurrency($grandTotal) . "*";
    $lines[] = "Paid: " . formatCurrency($paid);
    if ($balance > 0) {
        $lines[] = "*Balance Due: " . formatCurrency($balance) . "*";
    }
    $lines[] = "";
    if ($businessPhone) {
        $lines[] = "📞 {$businessPhone}";
    }
    $lines[] = "🙏 Thank you for your business — {$businessName}";

    return implode("\n", $lines);
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
