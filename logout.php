<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method Not Allowed';
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid CSRF token';
    exit;
}

$_SESSION = [];
$cookie = session_get_cookie_params();
$cookieOptions = [
    'expires' => time() - 42000,
    'path' => $cookie['path'],
    'secure' => $cookie['secure'],
    'httponly' => $cookie['httponly'],
    'samesite' => $cookie['samesite'],
];
if ($cookie['domain'] !== '') {
    $cookieOptions['domain'] = $cookie['domain'];
}
setcookie(session_name(), '', $cookieOptions);
session_destroy();

session_start();
flash('success', 'You have been signed out.');
redirect('login.php');
