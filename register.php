<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $_SESSION['_old'] = [
        'name' => $name,
        'email' => $email,
    ];

    if (!csrf_verify()) {
        flash('error', 'Your form session expired. Please try again.');
        redirect('register.php');
    }

    $errors = [];

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Enter a name no longer than 100 characters.';
    }

    if (
        $email === ''
        || strlen($email) > 160
        || filter_var($email, FILTER_VALIDATE_EMAIL) === false
    ) {
        $errors[] = 'Enter a valid email address (maximum 160 characters).';
    }

    $passwordCharacters = [];
    $passwordLength = preg_match_all('/./us', $password, $passwordCharacters);

    if (
        $passwordLength === false
        || $passwordLength < 10
        || preg_match('/\p{L}/u', $password) !== 1
        || preg_match('/\p{N}/u', $password) !== 1
    ) {
        $errors[] = 'Use a password with at least 10 characters, including a letter and a number.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if ($errors !== []) {
        flash('error', implode(' ', $errors));
        redirect('register.php');
    }

    $pdo = db();
    $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $emailCheck->execute(['email' => $email]);

    if ($emailCheck->fetch() !== false) {
        flash('error', 'An account with that email address already exists.');
        redirect('register.php');
    }

    try {
        $insertUser = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
        );
        $insertUser->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    } catch (PDOException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1062) {
            throw $exception;
        }

        flash('error', 'An account with that email address already exists.');
        redirect('register.php');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    unset($_SESSION['_old']);

    $updateLogin = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $updateLogin->execute(['id' => $_SESSION['user_id']]);

    flash('success', 'Your account has been created.');
    redirect('dashboard.php');
}

$error = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="register-title">
        <p class="eyebrow">SWD Auth</p>
        <h1 id="register-title">Create your account</h1>
        <p class="subtitle">Get started with a few details.</p>

        <?php if ($error !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="register.php">
            <?= csrf_field() ?>

            <label for="name">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                maxlength="100"
                autocomplete="name"
                value="<?= e(old('name')) ?>"
                required
            >

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
                minlength="10"
                autocomplete="new-password"
                aria-describedby="password-hint"
                required
            >
            <small id="password-hint">At least 10 characters, with a letter and a number.</small>

            <label for="confirm-password">Confirm password</label>
            <input
                id="confirm-password"
                name="confirm_password"
                type="password"
                minlength="10"
                autocomplete="new-password"
                required
            >

            <button type="submit">Create account</button>
        </form>

        <p class="password-note">
            Passwords are stored as secure hashes, never as readable text.
        </p>
    </section>
</main>
</body>
</html>
