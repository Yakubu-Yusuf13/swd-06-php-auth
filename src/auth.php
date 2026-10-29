<?php
declare(strict_types=1);

function validate_intended_url(mixed $url): ?string
{
    if (
        !is_string($url)
        || $url === ''
        || strlen($url) > 2048
        || preg_match('/[\x00-\x20\x7f\\\\]/', $url) === 1
    ) {
        return null;
    }

    $parts = parse_url($url);
    if (
        $parts === false
        || isset($parts['scheme'])
        || isset($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['port'])
        || !isset($parts['path'])
    ) {
        return null;
    }

    $basePath = config()['base_path'];
    $path = rawurldecode($parts['path']);
    if (
        !str_starts_with($path, $basePath . '/')
        || str_contains($path, '\\')
        || preg_match('/[\x00-\x1f\x7f]/', $path) === 1
    ) {
        return null;
    }

    foreach (explode('/', $path) as $segment) {
        if ($segment === '.' || $segment === '..') {
            return null;
        }
    }

    return $url;
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }

    $loaded = true;
    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) || $userId < 1) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, name, email, role, created_at, last_login_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch() ?: null;

    if ($user === null) {
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['last_activity']);
        session_regenerate_id(true);
    } else {
        $_SESSION['role'] = $user['role'];
    }

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user !== null) {
        return $user;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        $intendedUrl = validate_intended_url($_SERVER['REQUEST_URI'] ?? null);
        if ($intendedUrl !== null) {
            $_SESSION['_intended_url'] = $intendedUrl;
        }
    }

    flash('error', 'Please sign in to continue.');
    redirect('login.php');
}

function require_guest(): void
{
    if (current_user() !== null) {
        redirect('dashboard.php');
    }
}

function require_role(string $role): array
{
    $user = require_login();

    if (($user['role'] ?? null) !== $role) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        $dashboardUrl = e(config()['base_path'] . '/dashboard.php');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Access denied</title><link rel="stylesheet" href="'
            . e(config()['base_path'] . '/assets/auth.css')
            . '"></head><body><main class="auth-shell"><section class="auth-card">'
            . '<p class="eyebrow">403</p><h1>Access denied</h1>'
            . '<p class="subtitle">You do not have permission to view this page.</p>'
            . '<p><a href="' . $dashboardUrl . '">Return to your dashboard</a></p>'
            . '</section></main></body></html>';
        exit;
    }

    return $user;
}

function login_throttle_remaining_minutes(string $email, string $ip): int
{
    $statement = db()->prepare(
        'SELECT GREATEST(
            1,
            CEIL(TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MIN(attempted_at), INTERVAL 15 MINUTE)) / 60)
         ) AS retry_minutes
         FROM login_attempts
         WHERE email = :email
           AND ip = :ip
           AND attempted_at >= NOW() - INTERVAL 15 MINUTE
         HAVING COUNT(*) >= 5'
    );
    $statement->execute(['email' => $email, 'ip' => $ip]);
    $minutes = $statement->fetchColumn();

    return $minutes === false ? 0 : (int) $minutes;
}

function record_login_attempt(string $email, string $ip): void
{
    $statement = db()->prepare(
        'INSERT INTO login_attempts (email, ip) VALUES (:email, :ip)'
    );
    $statement->execute(['email' => $email, 'ip' => $ip]);
}

function clear_login_attempts(string $email, string $ip): void
{
    $statement = db()->prepare(
        'DELETE FROM login_attempts WHERE email = :email AND ip = :ip'
    );
    $statement->execute(['email' => $email, 'ip' => $ip]);
}
