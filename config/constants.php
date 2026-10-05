<?php
define('BASE_URL', '');
define('SITE_URL', 'https://tradeposx.com');
define('APP_NAME', 'TradePOSX');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Asia/Kolkata');

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

define('SESSION_TIMEOUT', 1800); // 30 minutes

define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB

// Every list screen across TradeERP + ServiceERP paginates at this size.
define('LIST_PAGE_SIZE', 20);

// ── AI purchase-bill scanning ──
// Gemini's free tier caps out at 20-50 requests/day, which kept getting
// exhausted between testing and real use — switched the active pipeline to
// OCR.space (free, 25,000 pages/month) + Groq (free, fast text model), with
// Claude vision as an automatic paid fallback ONLY when the free path fails
// outright (e.g. OCR.space's 3-page free-tier cap) or comes back with no
// usable quantity/price data (common on a cramped or photographed table,
// where OCR flattens the 2D layout and loses which number belongs to which
// row). See includes/BillScanHelper.php. Left the old Gemini path in place,
// unused, in case it's ever worth reviving.
// API keys live in config/secrets.php (git-ignored); fall back to empty so a missing file just disables scanning.
if (is_file(__DIR__ . '/secrets.php')) { require_once __DIR__ . '/secrets.php'; }
foreach (['OCR_SPACE_API_KEY', 'GROQ_API_KEY', 'CLAUDE_API_KEY', 'GEMINI_API_KEY', 'SUPERADMIN_EMAIL', 'SUPERADMIN_PASSWORD_HASH'] as $k) { if (!defined($k)) define($k, ''); }
define('GROQ_TEXT_MODEL', 'openai/gpt-oss-120b');
define('CLAUDE_MODEL', 'claude-haiku-4-5-20251001');

define('GEMINI_SCAN_MODELS', ['gemini-flash-latest', 'gemini-3.8-flash']);

// ── WhatsApp (Udhaar reminders, receipts) — fill in when ready for that feature ──
define('WHATSAPP_PROVIDER', ''); // 'green-api' or 'meta-cloud'
define('GREEN_API_INSTANCE_ID', '');
define('GREEN_API_TOKEN', '');
