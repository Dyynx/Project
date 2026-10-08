<?php
declare(strict_types=1);

session_start();

date_default_timezone_set('Asia/Jakarta');

define('APP_NAME', 'Otaku Track');
// Dynamic base URL: works even if the project folder is renamed.
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
define('BASE_URL', $scriptDir === '/' ? '' : $scriptDir);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'otaku_track');
define('DB_USER', 'root');
define('DB_PASS', '');

define('JIKAN_BASE_URL', 'https://api.jikan.moe/v4');
define('JIKAN_CLIENT_FALLBACK', true);
define('CACHE_DIR', __DIR__ . '/../storage/cache');

define('API_CA_BUNDLE', __DIR__ . '/cacert.pem');
define('DEBUG_MODE', true);

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0775, true);
}
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
