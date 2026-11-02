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

// Pengaturan performa API eksternal (detik)
define('API_CONNECT_TIMEOUT', 3);   // maks. waktu menunggu koneksi
define('API_TIMEOUT', 8);           // maks. waktu per request
define('API_CACHE_TTL', 3600);      // cache hasil API 1 jam
define('API_DOWN_COOLDOWN', 60);    // setelah API gagal, jangan dicoba lagi selama 60 detik

define('API_CA_BUNDLE', __DIR__ . '/cacert.pem');
// false = error PHP tidak ditampilkan ke pengunjung. Ubah ke true hanya saat debugging di komputer sendiri.
define('DEBUG_MODE', false);

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0775, true);
}
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}
