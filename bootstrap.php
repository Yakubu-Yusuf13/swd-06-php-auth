<?php
declare(strict_types=1);

require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/csrf.php';
require_once __DIR__ . '/src/auth.php';

error_reporting(E_ALL);
ini_set('log_errors', '1');
if (config()['environment'] === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: frame-ancestors 'none'");

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '1800');
session_name('SWDSESSID');
$isHttps = isset($_SERVER['HTTPS'])
    && strtolower((string) $_SERVER['HTTPS']) !== 'off';
if (!$isHttps && getenv('VERCEL') === '1') {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $isHttps = is_string($forwardedProto)
        && strtolower(trim(explode(',', $forwardedProto)[0])) === 'https';
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => config()['base_path'] === '' ? '/' : config()['base_path'],
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

$redisUrl = getenv('REDIS_URL');
if ($redisUrl !== false && $redisUrl !== '') {
    if (!extension_loaded('redis')) {
        throw new RuntimeException('The Redis PHP extension is required when REDIS_URL is configured.');
    }

    $redis = parse_url($redisUrl);
    if (
        $redis === false
        || !in_array($redis['scheme'] ?? '', ['redis', 'rediss'], true)
        || empty($redis['host'])
        || !isset($redis['port'])
        || !isset($redis['pass'])
    ) {
        throw new RuntimeException('REDIS_URL must be a redis:// or rediss:// URL with host, port, and password.');
    }
    if (getenv('VERCEL') === '1' && $redis['scheme'] !== 'rediss') {
        throw new RuntimeException('REDIS_URL must use rediss:// TLS on Vercel.');
    }

    $redisScheme = $redis['scheme'] === 'rediss' ? 'tls' : 'tcp';
    $redisAuth = '';
    if (isset($redis['user']) && $redis['user'] !== '') {
        $redisAuth = rawurldecode($redis['user']) . ':';
    }
    $redisAuth .= rawurldecode($redis['pass']);
    $redisDatabasePath = trim($redis['path'] ?? '', '/');
    $redisDatabase = $redisDatabasePath === ''
        ? 0
        : filter_var($redisDatabasePath, FILTER_VALIDATE_INT);
    if ($redisDatabase === false || $redisDatabase < 0) {
        throw new RuntimeException('REDIS_URL must use a valid Redis database number.');
    }

    $redisSavePath = $redisScheme . '://' . $redis['host'] . ':' . $redis['port'] . '?'
        . http_build_query([
            'auth' => $redisAuth,
            'database' => $redisDatabase,
            'prefix' => 'SWDSESSID:',
            'timeout' => 2,
            'read_timeout' => 2,
        ], '', '&', PHP_QUERY_RFC3986);
    if (
        ini_set('session.save_handler', 'redis') === false
        || ini_set('session.save_path', $redisSavePath) === false
    ) {
        throw new RuntimeException('Could not configure Redis-backed PHP sessions.');
    }
} elseif (getenv('VERCEL') === '1') {
    throw new RuntimeException('Set REDIS_URL to a shared Redis service for Vercel sessions.');
}

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
