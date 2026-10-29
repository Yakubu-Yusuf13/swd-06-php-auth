<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    $_SESSION['_old'] = ['email' => $email];

    if (!csrf_verify()) {
        flash('error', 'Your form session expired. Please try again.');
        redirect('login.php');
    }

    $user = false;
    if (
        $email !== ''
        && strlen($email) <= 160
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
    ) {
        $statement = db()->prepare(
            'SELECT id, email, password_hash, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
    }

    $dummyHash = '$2y$10$92IXUNpJ0O0OQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    $passwordIsValid = password_verify(
        $password,
        is_array($user) ? $user['password_hash'] : $dummyHash
    );

    if (!is_array($user) || !$passwordIsValid) {
        flash('error', 'Invalid email or password.');
        redirect('login.php');
    }

    $pdo = db();
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
        $rehash = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $rehash->execute([
            'password_hash' => $newPasswordHash,
            'id' => $user['id'],
        ]);
    }

    $updateLogin = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $updateLogin->execute(['id' => $user['id']]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['_old']);

    $intendedUrl = validate_intended_url($_SESSION['_intended_url'] ?? null);
    unset($_SESSION['_intended_url']);

    redirect($intendedUrl ?? 'dashboard.php');
}

$error = flash('error');
$success = flash('success');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="login-title">
        <p class="eyebrow">SWD Auth</p>
        <h1 id="login-title">Welcome back</h1>
        <p class="subtitle">Sign in to continue to your account.</p>

        <?php if ($error !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <?php if ($success !== null): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= csrf_field() ?>

            <label for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                maxlength="160"
                autocomplete="email"
                value="<?= e(old('email')) ?>"
                required
            >

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Sign in</button>
        </form>

        <p class="password-note">New here? <a href="register.php">Create an account</a>.</p>
    </section>
</main>
</body>
</html>
