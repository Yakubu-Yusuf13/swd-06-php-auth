<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    flash('error', 'Please create an account or sign in to continue.');
    redirect('register.php');
}

$statement = db()->prepare('SELECT name, email FROM users WHERE id = :id LIMIT 1');
$statement->execute(['id' => $_SESSION['user_id']]);
$user = $statement->fetch();

if ($user === false) {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
    flash('error', 'Your session is no longer valid. Please create an account or sign in.');
    redirect('register.php');
}

$success = flash('success');
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
    </section>
</main>
</body>
</html>
