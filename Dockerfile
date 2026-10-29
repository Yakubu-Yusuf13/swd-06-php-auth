FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions pdo_mysql redis

COPY --chown=www-data:www-data Caddyfile /etc/caddy/Caddyfile
COPY --chown=www-data:www-data bootstrap.php dashboard.php index.php login.php logout.php profile.php register.php /app/public/
COPY --chown=www-data:www-data src /app/public/src
COPY --chown=www-data:www-data admin /app/public/admin
COPY --chown=www-data:www-data assets /app/public/assets

ENV PORT=80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
