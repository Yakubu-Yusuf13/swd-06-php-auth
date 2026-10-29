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
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        exit;
    }

    return $user;
}
