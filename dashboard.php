<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$user = require_login();

$success = flash('success');
$createdAt = new DateTimeImmutable($user['created_at']);
$lastLoginAt = $user['last_login_at'] === null
    ? null
    : new DateTimeImmutable($user['last_login_at']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="dashboard-title">
        <p class="eyebrow">SWD Auth</p>
        <h1 id="dashboard-title">Welcome, <?= e($user['name']) ?></h1>
        <?php if ($success !== null): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>
        <p class="subtitle">You are signed in as <?= e($user['email']) ?>.</p>
        <dl class="account-details">
            <div>
                <dt>Account created</dt>
                <dd><?= e($createdAt->format('F j, Y')) ?></dd>
            </div>
            <div>
                <dt>Last login</dt>
                <dd><?= $lastLoginAt === null ? 'Not recorded' : e($lastLoginAt->format('F j, Y g:i a')) ?></dd>
            </div>
            <div>
                <dt>Role</dt>
                <dd><?= e($user['role']) ?></dd>
            </div>
        </dl>
        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button type="submit">Sign out</button>
        </form>
    </section>
</main>
</body>
</html>
