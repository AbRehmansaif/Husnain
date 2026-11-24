<?php
declare(strict_types=1);

namespace DonorNexa\Services;

use DonorNexa\Config\Database;
use DonorNexa\Core\Session;
use PDO;

final class RememberMeService
{
    private const COOKIE_NAME = 'donornexa_remember';
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function remember(int $userId): void
    {
        $this->forget();
        $token = bin2hex(random_bytes(32));
        $expires = new \DateTimeImmutable('+30 days', new \DateTimeZone(APP_TIMEZONE));
        $statement = $this->db->prepare(
            'INSERT INTO remember_tokens (token_hash, user_id, expires_at)
             VALUES (:token_hash, :user_id, :expires_at)'
        );
        $statement->execute([
            'token_hash' => hash('sha256', $token),
            'user_id' => $userId,
            'expires_at' => $expires->format('Y-m-d H:i:s'),
        ]);
        $this->setCookie($userId . '.' . $token, $expires->getTimestamp());
    }

    public function restoreIfNeeded(): void
    {
        if (current_user() !== null || empty($_COOKIE[self::COOKIE_NAME])) {
            return;
        }

        $parts = explode('.', (string) $_COOKIE[self::COOKIE_NAME], 2);
        $userId = isset($parts[0]) ? filter_var($parts[0], FILTER_VALIDATE_INT) : false;
        $token = $parts[1] ?? '';
        if (!$userId || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->expireCookie();
            return;
        }

        $statement = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.role
             FROM remember_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :token_hash AND t.user_id = :user_id
               AND t.expires_at > NOW() AND u.status = \'active\''
        );
        $statement->execute([
            'token_hash' => hash('sha256', $token),
            'user_id' => $userId,
        ]);
        $account = $statement->fetch();
        if (!$account) {
            $this->forget();
            return;
        }

        Session::regenerate();
        $_SESSION['user'] = [
            'id' => (int) $account['id'],
            'name' => $account['name'],
            'email' => $account['email'],
            'role' => $account['role'],
        ];
        $this->remember((int) $account['id']);
    }

    public function forget(): void
    {
        $cookie = (string) ($_COOKIE[self::COOKIE_NAME] ?? '');
        $parts = explode('.', $cookie, 2);
        if (isset($parts[1]) && preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
            $this->db->prepare('DELETE FROM remember_tokens WHERE token_hash = :token_hash')
                ->execute(['token_hash' => hash('sha256', $parts[1])]);
        }
        $this->expireCookie();
    }

    private function expireCookie(): void
    {
        $this->setCookie('', time() - 3600);
    }

    private function setCookie(string $value, int $expires): void
    {
        $cookiePath = parse_url(APP_URL, PHP_URL_PATH);
        setcookie(self::COOKIE_NAME, $value, [
            'expires' => $expires,
            'path' => is_string($cookiePath) && $cookiePath !== '' ? rtrim($cookiePath, '/') : '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
