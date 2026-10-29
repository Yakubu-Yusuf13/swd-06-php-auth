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

function render_navbar(array $user): void
{
    ?>
    <nav class="site-nav" aria-label="Main navigation">
        <a class="brand" href="dashboard.php">SWD Auth</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <?php if (($user['role'] ?? null) === 'admin'): ?>
                <a href="admin/users.php">Admin</a>
            <?php endif; ?>
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button type="submit" class="nav-button">Sign out</button>
            </form>
        </div>
    </nav>
    <?php
}
