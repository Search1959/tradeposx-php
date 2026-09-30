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

/** Renders a Prev/Next + numbered-page control, reused by every paginated list
 * screen across TradeERP + ServiceERP (each capped at LIST_PAGE_SIZE). $extraParams
 * (e.g. ['q' => $search]) are preserved on every page link so paging doesn't drop
 * an active search/filter. Returns '' when there's nothing to page through. */
function renderPagination(int $page, int $totalPages, string $baseUrl, array $extraParams = []): string {
    if ($totalPages <= 1) return '';
    $extraParams = array_filter($extraParams, fn($v) => $v !== null && $v !== '');
    $buildUrl = function (int $p) use ($baseUrl, $extraParams): string {
        return $baseUrl . '?' . http_build_query(array_merge($extraParams, ['page' => $p]));
    };
    $link = fn(int $p, string $label, string $class = '') =>
        '<a href="' . h($buildUrl($p)) . '" class="px-3 py-1.5 rounded text-sm ' . $class . '">' . $label . '</a>';

    $html = '<div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-t border-slate-100 text-sm">';
    $html .= '<div class="text-slate-500">Page ' . $page . ' of ' . $totalPages . '</div>';
    $html .= '<div class="flex items-center gap-1">';
    if ($page > 1) {
        $html .= $link($page - 1, '&larr; Prev', 'border border-slate-300 text-slate-600 hover:bg-slate-50');
    }
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) {
        $html .= $link(1, '1', 'text-slate-600 hover:bg-slate-100');
        if ($start > 2) $html .= '<span class="px-1 text-slate-400">&hellip;</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= $i === $page
            ? '<span class="px-3 py-1.5 rounded bg-indigo-600 text-white font-medium text-sm">' . $i . '</span>'
            : $link($i, (string) $i, 'text-slate-600 hover:bg-slate-100');
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="px-1 text-slate-400">&hellip;</span>';
        $html .= $link($totalPages, (string) $totalPages, 'text-slate-600 hover:bg-slate-100');
    }
    if ($page < $totalPages) {
        $html .= $link($page + 1, 'Next &rarr;', 'border border-slate-300 text-slate-600 hover:bg-slate-50');
    }
    $html .= '</div></div>';
    return $html;
}

/** Groups line items by their own GST% so a sale/invoice mixing rates (e.g. 5% and
 * 18% items on one bill) reports each slab's taxable amount and tax separately —
 * a single blended CGST/SGST total hides which rate produced how much tax, which
 * is both confusing to read and not how a GST-compliant rate-wise summary works.
 * $items rows need $gstRateKey/$gstAmountKey and $totalKey (sale_items uses
 * total_price, service_invoice_items uses total); zero/blank-rate items are
 * skipped since there's no tax to report for them. */
function gstBreakdownByRate(array $items, bool $inclusive, string $totalKey, string $gstRateKey = 'gst_rate', string $gstAmountKey = 'gst_amount'): array {
    $groups = [];
    foreach ($items as $item) {
        $rate = (float) ($item[$gstRateKey] ?? 0);
        if ($rate <= 0) continue;
        $total = (float) ($item[$totalKey] ?? 0);
        $tax = (float) ($item[$gstAmountKey] ?? 0);
        $taxable = $inclusive ? $total - $tax : $total;
        if (!isset($groups[$rate])) $groups[$rate] = ['rate' => $rate, 'taxable' => 0.0, 'tax' => 0.0];
        $groups[$rate]['taxable'] += $taxable;
        $groups[$rate]['tax'] += $tax;
    }
    ksort($groups);
    return array_values($groups);
}

/** "9" for 18, "2.5" for 5 — the CGST/SGST half-rate label, trimmed of a trailing ".0". */
function halfGstRate(float $rate): string {
    return rtrim(rtrim(number_format($rate / 2, 2), '0'), '.');
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
 * from udhaarReminderMessage() which only nags about an outstanding balance. Mirrors
 * views/trade/sales/receipt.php's own GST computation line-for-line (same isGst check,
 * same taxable/CGST/SGST fallback for pre-GST-tracking rows) so the two never disagree —
 * the earlier version only listed pre-tax line totals and then jumped straight to a
 * GST-inclusive grand total with no breakdown, which is what made the WhatsApp total
 * look "wrong" next to the item lines. $sale is the full row from Sale::find()
 * (needs 'items'), $org is Tenant::current(). */
function saleBillWhatsAppMessage(array $org, array $sale): string {
    $isGst = $sale['tax_type'] !== 'EXEMPT' && (float) $sale['total_tax_amount'] > 0;
    $docTitle = $isGst ? 'Tax Invoice' : 'Bill of Supply';
    $taxable = $sale['taxable_amount'] !== null ? (float) $sale['taxable_amount'] : (float) $sale['subtotal'];
    $cgst = $sale['cgst_amount'] !== null ? (float) $sale['cgst_amount'] : round((float) $sale['total_tax_amount'] / 2, 2);
    $sgst = $sale['sgst_amount'] !== null ? (float) $sale['sgst_amount'] : round((float) $sale['total_tax_amount'] - $cgst, 2);
    $gstBreakdown = $isGst ? gstBreakdownByRate($sale['items'], $sale['tax_type'] === 'INCLUSIVE', 'total_price') : [];
    $grandTotal = (float) $sale['grand_total'];
    $paid = (float) $sale['received_amount'];
    $balance = max(0, round($grandTotal - $paid, 2));

    $lines = [];
    $lines[] = "🧾 *{$org['name']}*";
    if (!empty($org['address'])) $lines[] = $org['address'];
    if (!empty($org['gstin'])) $lines[] = "GSTIN: {$org['gstin']}";
    $lines[] = "";
    $lines[] = "_{$docTitle}_ · Bill No: {$sale['sale_number']}";
    $lines[] = formatDateTime($sale['created_at']);
    $lines[] = "";
    $lines[] = "Dear " . ($sale['customer_name'] ?: 'Customer') . ",";
    $lines[] = "Thank you for your purchase! Here's your bill:";
    $lines[] = "";
    $lines[] = "```";
    foreach ($sale['items'] as $item) {
        $qty = rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.');
        $gstNote = $isGst && (float) ($item['gst_rate'] ?? 0) > 0 ? ' (' . rtrim(rtrim(number_format((float) $item['gst_rate'], 2), '0'), '.') . '% GST)' : '';
        $lines[] = $item['product_name'] . $gstNote;
        $lines[] = '  ' . $qty . ' x ' . number_format((float) $item['unit_price'], 2) . ' = ' . number_format((float) $item['total_price'], 2);
    }
    $lines[] = "```";
    $lines[] = "";
    if ($isGst && !empty($gstBreakdown)) {
        $lines[] = "Taxable Amount: " . formatCurrency($taxable);
        foreach ($gstBreakdown as $g) {
            $lines[] = "CGST @ " . halfGstRate($g['rate']) . "%: " . formatCurrency($g['tax'] / 2);
            $lines[] = "SGST @ " . halfGstRate($g['rate']) . "%: " . formatCurrency($g['tax'] / 2);
        }
    } elseif ($isGst) {
        $lines[] = "Taxable Amount: " . formatCurrency($taxable);
        $lines[] = "CGST: " . formatCurrency($cgst);
        $lines[] = "SGST: " . formatCurrency($sgst);
    }
    if ((float) $sale['discount'] > 0) {
        $lines[] = "Discount: -" . formatCurrency((float) $sale['discount']);
    }
    $lines[] = "*Grand Total: " . formatCurrency($grandTotal) . "*";
    $lines[] = "_" . amountInWordsINR($grandTotal) . "_";
    $lines[] = "";
    $lines[] = "Paid (" . $sale['payment_method'] . "): " . formatCurrency($paid);
    if ($balance > 0) {
        $lines[] = "*Balance (Udhaar): " . formatCurrency($balance) . "*";
    }
    $lines[] = "";
    if (!empty($org['phone'])) {
        $lines[] = "📞 {$org['phone']}";
    }
    $lines[] = "🙏 Thank you for your business — {$org['name']}";

    return implode("\n", $lines);
}

/** Same bill-style formatting as saleBillWhatsAppMessage(), mirroring
 * views/service/invoices/view.php's GST computation. $invoice is the full row from
 * ServiceInvoice::find() (needs 'items'), $org is Tenant::current(). */
function serviceInvoiceWhatsAppMessage(array $org, array $invoice): string {
    $isGst = $invoice['tax_type'] !== 'EXEMPT' && (float) $invoice['gst_amount'] > 0;
    $docTitle = $isGst ? 'Tax Invoice' : 'Bill of Supply';
    $itemsTotal = array_sum(array_map(fn($i) => (float) $i['total'], $invoice['items']));
    $taxable = $isGst && $invoice['taxable_amount'] !== null ? (float) $invoice['taxable_amount'] : $itemsTotal;
    $cgst = (float) ($invoice['cgst_amount'] ?? $invoice['gst_amount'] / 2);
    $sgst = (float) ($invoice['sgst_amount'] ?? $invoice['gst_amount'] / 2);
    $gstBreakdown = $isGst ? gstBreakdownByRate($invoice['items'], $invoice['tax_type'] === 'INCLUSIVE', 'total') : [];
    $grandTotal = (float) $invoice['grand_total'];
    $paid = (float) $invoice['paid_amount'];
    $balance = (float) $invoice['balance_amount'];

    $lines = [];
    $lines[] = "🧾 *{$org['name']}*";
    if (!empty($org['address'])) $lines[] = $org['address'];
    if (!empty($org['gstin'])) $lines[] = "GSTIN: {$org['gstin']}";
    $lines[] = "";
    $lines[] = "_{$docTitle}_ · Invoice No: {$invoice['invoice_no']}";
    $lines[] = formatDate($invoice['invoice_date']);
    $lines[] = "";
    $lines[] = "Dear " . ($invoice['customer_name'] ?: 'Customer') . ",";
    $lines[] = "Thank you for your business! Here's your invoice:";
    $lines[] = "";
    $lines[] = "```";
    foreach ($invoice['items'] as $item) {
        $qty = rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.');
        $gstNote = $isGst && (float) ($item['gst_rate'] ?? 0) > 0 ? ' (' . rtrim(rtrim(number_format((float) $item['gst_rate'], 2), '0'), '.') . '% GST)' : '';
        $lines[] = $item['name'] . $gstNote;
        $lines[] = '  ' . $qty . ' x ' . number_format((float) $item['price'], 2) . ' = ' . number_format((float) $item['total'], 2);
    }
    $lines[] = "```";
    $lines[] = "";
    if ($isGst && !empty($gstBreakdown)) {
        $lines[] = "Taxable Amount: " . formatCurrency($taxable);
        foreach ($gstBreakdown as $g) {
            $lines[] = "CGST @ " . halfGstRate($g['rate']) . "%: " . formatCurrency($g['tax'] / 2);
            $lines[] = "SGST @ " . halfGstRate($g['rate']) . "%: " . formatCurrency($g['tax'] / 2);
        }
    } elseif ($isGst) {
        $lines[] = "Taxable Amount: " . formatCurrency($taxable);
        $lines[] = "CGST: " . formatCurrency($cgst);
        $lines[] = "SGST: " . formatCurrency($sgst);
    }
    if ((float) $invoice['labour_charges'] > 0) {
        $lines[] = "Labour: " . formatCurrency((float) $invoice['labour_charges']);
    }
    if ((float) $invoice['discount'] > 0) {
        $lines[] = "Discount: -" . formatCurrency((float) $invoice['discount']);
    }
    $lines[] = "*Grand Total: " . formatCurrency($grandTotal) . "*";
    $lines[] = "_" . amountInWordsINR($grandTotal) . "_";
    $lines[] = "";
    $lines[] = "Paid" . ($invoice['payment_method'] ? ' (' . $invoice['payment_method'] . ')' : '') . ": " . formatCurrency($paid);
    if ($balance > 0) {
        $lines[] = "*Balance Due: " . formatCurrency($balance) . "*";
    }
    $lines[] = "";
    if (!empty($org['phone'])) {
        $lines[] = "📞 {$org['phone']}";
    }
    $lines[] = "🙏 Thank you for your business — {$org['name']}";

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
