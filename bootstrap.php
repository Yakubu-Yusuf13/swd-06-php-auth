<?php
declare(strict_types=1);

require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/csrf.php';
require_once __DIR__ . '/src/auth.php';

if (config()['environment'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('SWDSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/swd/06-php-auth',
    'secure' => isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (isset($_SESSION['user_id'])) {
    $lastActivity = $_SESSION['last_activity'] ?? null;

    if (!is_int($lastActivity) || $lastActivity > time()) {
        $_SESSION['last_activity'] = time();
    } elseif (time() - $lastActivity >= 1800) {
        $requestedUrl = null;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            $requestedUrl = validate_intended_url($_SERVER['REQUEST_URI'] ?? null);
        }

        $_SESSION = [];
        session_regenerate_id(true);
        if ($requestedUrl !== null) {
            $_SESSION['_intended_url'] = $requestedUrl;
        }
        flash('error', 'You were signed out after 30 minutes of inactivity.');
        redirect('login.php');
    } else {
        $_SESSION['last_activity'] = time();
    }
}
