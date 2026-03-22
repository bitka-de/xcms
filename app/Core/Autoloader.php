<?php

namespace App\Core;

class Autoloader
{
    private static string $basePath;

    public static function register(string $basePath): void
    {
        self::$basePath = $basePath;
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        if (strpos($class, 'App\\') !== 0) {
            return;
        }

        $path = self::$basePath . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }
}
