<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post_string('action');

    if (!csrf_verify()) {
        flash('error', 'Your form session expired. Please try again.');
        redirect('profile.php');
    }

    if ($action === 'profile') {
        $name = trim(post_string('name'));
        $email = strtolower(trim(post_string('email')));
        $_SESSION['_old'] = ['name' => $name, 'email' => $email];
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

        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            redirect('profile.php');
        }

        $pdo = db();
        $duplicateCheck = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
        );
        $duplicateCheck->execute(['email' => $email, 'id' => $user['id']]);

        if ($duplicateCheck->fetch() !== false) {
            flash('error', 'An account with that email address already exists.');
            redirect('profile.php');
        }

        try {
            $update = $pdo->prepare(
                'UPDATE users SET name = :name, email = :email WHERE id = :id'
            );
            $update->execute([
                'name' => $name,
                'email' => $email,
                'id' => $user['id'],
            ]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1062) {
                throw $exception;
            }

            flash('error', 'An account with that email address already exists.');
            redirect('profile.php');
        }

        unset($_SESSION['_old']);
        flash('success', 'Your profile has been updated.');
        redirect('profile.php');
    }

    if ($action === 'password') {
        $currentPassword = post_string('current_password');
        $newPassword = post_string('new_password');
        $confirmPassword = post_string('confirm_password');

        $statement = db()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $user['id']]);
        $passwordHash = $statement->fetchColumn();

        if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
            flash('error', 'The current password is incorrect.');
            redirect('profile.php');
        }

        if (!password_meets_policy($newPassword)) {
            flash('error', 'Use a new password with at least 10 characters, including a letter and a number.');
            redirect('profile.php');
        }

        if ($newPassword !== $confirmPassword) {
            flash('error', 'The new passwords do not match.');
            redirect('profile.php');
        }

        $updatePassword = db()->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $updatePassword->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $user['id'],
        ]);

        session_regenerate_id(true);
        $_SESSION['last_activity'] = time();
        flash('success', 'Your password has been changed.');
        redirect('profile.php');
    }

    flash('error', 'Choose a valid profile action.');
    redirect('profile.php');
}

$error = flash('error');
$success = flash('success');
$name = old('name', $user['name']);
$email = old('email', $user['email']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your profile</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<?php render_navbar($user); ?>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="profile-title">
        <p class="eyebrow">Account</p>
        <h1 id="profile-title">Your profile</h1>
        <p class="subtitle">Update your account details or change your password.</p>

        <?php if ($error !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <?php if ($success !== null): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>

        <h2>Profile details</h2>
        <form method="post" action="profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">

            <label for="name">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                maxlength="100"
                autocomplete="name"
                value="<?= e($name) ?>"
                required
            >

            <label for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                maxlength="160"
                autocomplete="email"
                value="<?= e($email) ?>"
                required
            >

            <button type="submit">Save profile</button>
        </form>

        <hr>

        <h2>Change password</h2>
        <form method="post" action="profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <label for="current-password">Current password</label>
            <input
                id="current-password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                required
            >

            <label for="new-password">New password</label>
            <input
                id="new-password"
                name="new_password"
                type="password"
                minlength="10"
                autocomplete="new-password"
                aria-describedby="password-hint"
                required
            >
            <small id="password-hint">At least 10 characters, with a letter and a number.</small>

            <label for="confirm-password">Confirm new password</label>
            <input
                id="confirm-password"
                name="confirm_password"
                type="password"
                minlength="10"
                autocomplete="new-password"
                required
            >

            <button type="submit">Change password</button>
        </form>
    </section>
</main>
</body>
</html>
