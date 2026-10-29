# SWD PHP Authentication

A PHP 8 and MySQL authentication example for XAMPP. The application uses the
`swd_auth` database and its `users` and `login_attempts` tables.

## Run with XAMPP

1. Place this project at `C:\xampp\htdocs\swd\06-php-auth`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Import `database.sql` into MariaDB/MySQL, for example from PowerShell:

   ```powershell
   Get-Content -Raw .\database.sql | C:\xampp\mysql\bin\mysql.exe -u root
   ```

4. Open <http://localhost/swd/06-php-auth/register.php> to create an account,
   or <http://localhost/swd/06-php-auth/login.php> to sign in.

XAMPP's local defaults are `DB_HOST=127.0.0.1`, `DB_NAME=swd_auth`,
`DB_USER=root`, and an empty `DB_PASSWORD`. Override them with environment
variables for your PHP/Apache process when your MySQL account differs. Use a
dedicated database account with only the permissions this application needs,
and set a strong password outside local development.

Set `APP_ENV=development` to display PHP errors while developing. The default
is production mode: errors are logged and not sent to the browser. Do not enable
development mode on a publicly reachable server.

## Create the first administrator

Register the account normally, then from the project directory run:

```powershell
C:\xampp\php\php.exe promote-admin.php me@example.com
```

The script only runs through PHP CLI. Admins can manage roles at
`admin/users.php`; the current admin cannot demote their own account.

## Security features

- Passwords are stored with PHP's `password_hash()` and checked using
  `password_verify()`. Plaintext passwords and fast hashes such as MD5 or SHA-1
  are not used.
- PDO prepared statements are used for database queries; dynamic values are
  passed as parameters.
- Session IDs are regenerated after authentication and password changes.
  Session cookies are HttpOnly, SameSite=Lax, HTTPS-only when HTTPS is active,
  and strict session mode is enabled. Sessions expire after 30 minutes idle.
- State-changing forms use session-bound CSRF tokens.
- Login failures are tracked by email and IP. Five failures within 15 minutes
  temporarily block that email/IP pair; successful login clears its attempts.
- Profile input is validated, output is escaped, and email uniqueness is
  enforced both before update and by the database unique constraint.
- Intended post-login destinations are validated as local project paths.
- Admin actions require the admin role and CSRF validation. Non-admin users
  receive a friendly HTTP 403 response.
- Responses set `X-Frame-Options: DENY`,
  `Content-Security-Policy: frame-ancestors 'none'`,
  `X-Content-Type-Options: nosniff`, and
  `Referrer-Policy: strict-origin-when-cross-origin`.
- PHP errors are not displayed to visitors by default.

## Main routes

- `register.php` — create an account.
- `login.php` / `logout.php` — sign in and sign out.
- `dashboard.php` — authenticated account dashboard.
- `profile.php` — update profile details or change the password.
- `admin/users.php` — admin-only user and role management.
