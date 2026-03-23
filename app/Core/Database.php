<?php

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $instance = null;
    private static string $dbPath;

    public static function initialize(string $dbPath): void
    {
        self::$dbPath = $dbPath;
        
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $isNewDb = !file_exists($dbPath);
        
        try {
            self::$instance = new PDO(
                'sqlite:' . $dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            self::$instance->exec('PRAGMA foreign_keys = ON');
        } catch (\PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not initialized');
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
