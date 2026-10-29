<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $database = config()['database'];
    $port = filter_var(
        $database['port'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 65535]]
    );
    if ($port === false) {
        throw new RuntimeException('DB_PORT must be a valid TCP port.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $database['host'],
        $port,
        $database['name']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ($database['ssl_ca_base64'] !== '') {
        $caCertificate = base64_decode($database['ssl_ca_base64'], true);
        if ($caCertificate === false || $caCertificate === '') {
            throw new RuntimeException('DB_SSL_CA_BASE64 must contain a base64-encoded CA certificate.');
        }

        $caPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'swd-auth-db-ca-' . hash('sha256', $caCertificate) . '.pem';
        if (!is_file($caPath) && file_put_contents($caPath, $caCertificate, LOCK_EX) === false) {
            throw new RuntimeException('Could not write the database CA certificate to temporary storage.');
        }

        $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    } elseif (getenv('VERCEL') === '1') {
        throw new RuntimeException('Set DB_SSL_CA_BASE64 to require verified TLS for the hosted database.');
    }

    $pdo = new PDO($dsn, $database['username'], $database['password'], $options);

    return $pdo;
}
