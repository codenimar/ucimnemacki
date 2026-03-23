<?php
define('SITE_NAME', 'Učim Nemački');
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('SESSION_LIFETIME', 86400 * 30);

ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
