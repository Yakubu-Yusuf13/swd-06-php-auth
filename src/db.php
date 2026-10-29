<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $database = config()['database'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $database['host'],
        $database['name']
    );

    $pdo = new PDO($dsn, $database['username'], $database['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
