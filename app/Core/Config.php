<?php

namespace App\Core;

class Config
{
    private static array $data = [];

    public static function load(string $path): void
    {
        if (file_exists($path)) {
            self::$data = require $path;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }

    public static function all(): array
    {
        return self::$data;
    }
}
