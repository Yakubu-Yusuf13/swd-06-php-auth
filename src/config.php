<?php
declare(strict_types=1);

function config(): array
{
    static $config;

    if ($config === null) {
        $basePath = getenv('APP_BASE_PATH');
        if ($basePath === false) {
            $basePath = getenv('VERCEL') === '1' ? '' : '/swd/06-php-auth';
        }

        $config = [
            'environment' => getenv('APP_ENV') ?: 'production',
            'base_path' => rtrim($basePath, '/'),
            'database' => [
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'name' => getenv('DB_NAME') ?: 'swd_auth',
                'username' => getenv('DB_USER') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
                'port' => getenv('DB_PORT') ?: '3306',
                'ssl_ca_base64' => getenv('DB_SSL_CA_BASE64') ?: '',
            ],
        ];
    }

    return $config;
}
