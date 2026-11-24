<?php
declare(strict_types=1);

use DonorNexa\Core\Session;

define('APP_NAME', 'DonorNexa');
define('APP_ROOT', dirname(__DIR__));
define(
    'APP_URL',
    rtrim((string) (getenv('APP_URL') ?: ('/' . trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/'))), '/')
);
define('APP_TIMEZONE', 'Asia/Karachi');

date_default_timezone_set(APP_TIMEZONE);

require_once APP_ROOT . '/src/Core/Session.php';
Session::start();

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route = 'home', array $params = []): string
{
    $query = http_build_query(['page' => $route] + $params);
    return (APP_URL !== '' ? APP_URL : '') . '/index.php?' . $query;
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params), true, 303);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = (string) ($_POST['_csrf'] ?? '');
    if ($submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        throw new \DonorNexa\Exceptions\ValidationException('Your form expired. Please refresh and try again.', 419);
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function refresh_user_session(): void
{
    $user = current_user();
    if ($user === null) {
        return;
    }

    $statement = \DonorNexa\Config\Database::connection()->prepare(
        'SELECT id, name, email, role, status FROM users WHERE id = :id'
    );
    $statement->execute(['id' => $user['id']]);
    $account = $statement->fetch();
    if (!$account || ($account['status'] !== 'active'
        && !($account['role'] === 'hospital' && $account['status'] === 'pending'))) {
        unset($_SESSION['user']);
        Session::regenerate();
        return;
    }

    $_SESSION['user'] = [
        'id' => (int) $account['id'],
        'name' => $account['name'],
        'email' => $account['email'],
        'role' => $account['role'],
    ];
}

function require_role(string ...$roles): array
{
    $user = current_user();
    if ($user === null) {
        if (defined('API_REQUEST') && API_REQUEST) {
            throw new \DonorNexa\Exceptions\ValidationException('Authentication is required for this endpoint.', 401);
        }
        redirect('login');
    }
    if (!in_array($user['role'], $roles, true)) {
        throw new \DonorNexa\Exceptions\ValidationException('You do not have permission to access this page.', 403);
    }

    return $user;
}
