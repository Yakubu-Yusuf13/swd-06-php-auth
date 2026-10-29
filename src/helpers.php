<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): void
{
    $parts = parse_url($location);

    if (
        $location === ''
        || preg_match('/[\r\n]/', $location) === 1
        || $parts === false
        || isset($parts['scheme'])
        || isset($parts['host'])
        || str_starts_with($location, '//')
    ) {
        throw new InvalidArgumentException('Redirect location must be a local URL.');
    }

    header('Location: ' . $location, true, 303);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return is_string($value) ? $value : null;
}

function old(string $key, string $default = ''): string
{
    $value = $_SESSION['_old'][$key] ?? $default;
    unset($_SESSION['_old'][$key]);

    return is_string($value) ? $value : $default;
}
