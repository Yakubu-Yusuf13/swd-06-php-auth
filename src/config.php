<?php
declare(strict_types=1);

function config(): array
{
    static $config;

    if ($config === null) {
        $config = [
            'environment' => getenv('APP_ENV') ?: 'development',
            'database' => [
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'name' => getenv('DB_NAME') ?: 'swd_auth',
                'username' => getenv('DB_USER') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
            ],
        ];
    }

    return $config;
}
