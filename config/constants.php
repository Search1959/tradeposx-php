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

// ── AI purchase-bill scanning (Gemini) — fill in when ready for that feature ──
define('GEMINI_API_KEY', '');
define('GEMINI_MODEL', 'gemini-flash-latest');

// ── WhatsApp (Udhaar reminders, receipts) — fill in when ready for that feature ──
define('WHATSAPP_PROVIDER', ''); // 'green-api' or 'meta-cloud'
define('GREEN_API_INSTANCE_ID', '');
define('GREEN_API_TOKEN', '');
