<?php
// Local-dev-only router for `php -S` (which doesn't read .htaccess). Emulates the
// production RewriteRule: real files/dirs are served as-is, everything else goes
// through index.php with $_GET['url'] set, exactly like Apache does on Hostinger.
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}
$_GET['url'] = ltrim($path, '/');
require __DIR__ . '/index.php';
