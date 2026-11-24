<?php
declare(strict_types=1);

namespace DonorNexa\Config;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $localConfig = APP_ROOT . '/config/local.php';
        $local = is_file($localConfig) ? require $localConfig : [];
        $host = getenv('DB_HOST') ?: ($local['DB_HOST'] ?? '127.0.0.1');
        $port = getenv('DB_PORT') ?: ($local['DB_PORT'] ?? '3306');
        $name = getenv('DB_NAME') ?: ($local['DB_NAME'] ?? 'donornexa');
        $user = getenv('DB_USER') ?: ($local['DB_USER'] ?? 'root');
        $password = getenv('DB_PASSWORD') ?: ($local['DB_PASSWORD'] ?? '');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+05:00'",
            ]);
        } catch (PDOException $exception) {
            error_log('[DonorNexa] Database connection failed: ' . $exception->getMessage());
            throw new PDOException('The database is temporarily unavailable. Check the local database setup and try again.');
        }

        return self::$connection;
    }
}
