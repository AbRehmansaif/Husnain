<?php
declare(strict_types=1);

namespace DonorNexa\Core;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'DonorNexa\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $baseDirectory = dirname(__DIR__);
            if (str_starts_with($relative, 'Config\\')) {
                $baseDirectory = dirname($baseDirectory) . '/config';
                $relative = substr($relative, strlen('Config\\'));
            }

            $file = $baseDirectory . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }
}
