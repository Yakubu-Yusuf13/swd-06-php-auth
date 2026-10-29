<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$admin = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Your form session expired. Please try again.');
        redirect('users.php');
    }

    $userIdInput = $_POST['user_id'] ?? '';
    $roleInput = $_POST['role'] ?? '';
    $userId = is_string($userIdInput) && ctype_digit($userIdInput)
        ? (int) $userIdInput
        : 0;
    $role = is_string($roleInput) ? $roleInput : '';

    if ($userId < 1 || !in_array($role, ['user', 'admin'], true)) {
        flash('error', 'Choose a valid user and role.');
        redirect('users.php');
    }

    if ($userId === (int) $admin['id'] && $role === 'user') {
        flash('error', 'You cannot demote your own account.');
        redirect('users.php');
    }

    $update = db()->prepare('UPDATE users SET role = :role WHERE id = :id');
    $update->execute(['role' => $role, 'id' => $userId]);

    if ($update->rowCount() === 0) {
        $exists = db()->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $exists->execute(['id' => $userId]);
        if ($exists->fetch() === false) {
            flash('error', 'That user could not be found.');
            redirect('users.php');
        }
    }

    flash('success', 'User role updated.');
    redirect('users.php');
}

$users = db()->query(
    'SELECT id, name, email, role, created_at, last_login_at
     FROM users
     ORDER BY created_at DESC, id DESC'
)->fetchAll();
$error = flash('error');
$success = flash('success');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage users</title>
    <link rel="stylesheet" href="../assets/auth.css">
</head>
<body>
<?php render_navbar($admin); ?>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="users-title" style="width: min(100%, 1100px);">
        <p class="eyebrow">Administration</p>
        <h1 id="users-title">Manage users</h1>
        <p class="subtitle">Review accounts and change their access roles.</p>

        <?php if ($error !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <?php if ($success !== null): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="user-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Last login</th>
                    <th>Change role</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $listedUser): ?>
                    <tr>
                        <td><?= e($listedUser['name']) ?></td>
                        <td><?= e($listedUser['email']) ?></td>
                        <td><?= e($listedUser['role']) ?></td>
                        <td><?= e((new DateTimeImmutable($listedUser['created_at']))->format('Y-m-d')) ?></td>
                        <td>
                            <?= $listedUser['last_login_at'] === null
                                ? '<span class="muted">Never</span>'
                                : e((new DateTimeImmutable($listedUser['last_login_at']))->format('Y-m-d H:i')) ?>
                        </td>
                        <td>
                            <?php if ((int) $listedUser['id'] === (int) $admin['id']): ?>
                                <span class="muted">You cannot demote yourself.</span>
                            <?php else: ?>
                                <form method="post" action="users.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                                    <label class="visually-hidden" for="role-<?= (int) $listedUser['id'] ?>">
                                        Role for <?= e($listedUser['name']) ?>
                                    </label>
                                    <select id="role-<?= (int) $listedUser['id'] ?>" name="role">
                                        <option value="user"<?= $listedUser['role'] === 'user' ? ' selected' : '' ?>>User</option>
                                        <option value="admin"<?= $listedUser['role'] === 'admin' ? ' selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit">Save role</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
