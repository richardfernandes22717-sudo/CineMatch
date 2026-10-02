<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CineMatch Authentication Helper
|--------------------------------------------------------------------------
| Handles:
| - PHP sessions
| - Login status
| - User information
| - Protected pages
| - CSRF protection
| - Flash messages
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config/database.php';

/*
|--------------------------------------------------------------------------
| Start a secure PHP session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
/*
|--------------------------------------------------------------------------
| Escape output safely
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| Redirect helper
|--------------------------------------------------------------------------
*/

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/*
|--------------------------------------------------------------------------
| Check whether a user is logged in
|--------------------------------------------------------------------------
*/

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'])
        && is_numeric($_SESSION['user_id']);
}

/*
|--------------------------------------------------------------------------
| Get the logged-in user
|--------------------------------------------------------------------------
*/

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    try {
        $stmt = db()->prepare(
            'SELECT
                id,
                name,
                email,
                role,
                status,
                created_at
             FROM users
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->execute([
            (int) $_SESSION['user_id']
        ]);

        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            logout_user(false);
            return null;
        }

        return $user;
    } catch (PDOException $error) {
        return null;
    }
}

/*
|--------------------------------------------------------------------------
| Log a user into the application
|--------------------------------------------------------------------------
*/

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';
    $_SESSION['logged_in_at'] = time();
}

/*
|--------------------------------------------------------------------------
| Log the current user out
|--------------------------------------------------------------------------
*/

function logout_user(bool $redirectAfterLogout = true): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $cookie['path'],
            $cookie['domain'],
            (bool) $cookie['secure'],
            (bool) $cookie['httponly']
        );
    }

    session_destroy();

    if ($redirectAfterLogout) {
        redirect('/movieverse/auth/login.php');
    }
}

/*
|--------------------------------------------------------------------------
| Protect user-only pages
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    if (!is_logged_in() || current_user() === null) {
        set_flash(
            'error',
            'Please log in to access that page.'
        );

        redirect('/movieverse/auth/login.php');
    }
}

/*
|--------------------------------------------------------------------------
| Prevent logged-in users from opening login/register pages
|--------------------------------------------------------------------------
*/

function require_guest(): void
{
    if (is_logged_in() && current_user() !== null) {
        redirect('/movieverse/user/dashboard.php');
    }
}

/*
|--------------------------------------------------------------------------
| Check whether the current user is an administrator
|--------------------------------------------------------------------------
*/

function is_admin(): bool
{
    return is_logged_in()
        && ($_SESSION['user_role'] ?? '') === 'admin';
}

/*
|--------------------------------------------------------------------------
| Protect admin-only pages
|--------------------------------------------------------------------------
*/

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied. Administrator permission is required.');
    }
}

/*
|--------------------------------------------------------------------------
| Flash messages
|--------------------------------------------------------------------------
*/

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/*
|--------------------------------------------------------------------------
| CSRF protection
|--------------------------------------------------------------------------
*/

function csrf_token(): string
{
    if (
        empty($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrf_token())
        . '">';
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/*
|--------------------------------------------------------------------------
| Remember old form inputs
|--------------------------------------------------------------------------
*/

function set_old_input(array $input): void
{
    unset($input['password'], $input['confirm_password']);
    $_SESSION['old_input'] = $input;
}

function old(string $key, string $default = ''): string
{
    return e(
        isset($_SESSION['old_input'][$key])
            ? (string) $_SESSION['old_input'][$key]
            : $default
    );
}

function clear_old_input(): void
{
    unset($_SESSION['old_input']);
}