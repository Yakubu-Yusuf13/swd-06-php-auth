<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';

$email = $argv[1] ?? '';
if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Usage: php promote-admin.php user@example.com\n");
    exit(1);
}

$pdo = db();
$findUser = $pdo->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1');
$findUser->execute(['email' => strtolower(trim($email))]);
$user = $findUser->fetch();

if ($user === false) {
    fwrite(STDERR, "No user found with that email address.\n");
    exit(1);
}

if ($user['role'] === 'admin') {
    fwrite(STDOUT, "That account is already an admin.\n");
    exit(0);
}

$promote = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
$promote->execute(['id' => $user['id']]);
fwrite(STDOUT, "Admin role granted successfully.\n");
