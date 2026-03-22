<?php

namespace App\Core;

use PDO;
use PDOException;

class MigrationRunner
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->migrationsPath = dirname(__DIR__, 2) . '/database/migrations';
    }

    public function run(): void
    {
        $this->ensureMigrationsTable();

        $files = $this->getMigrationFiles();

        if ($files === []) {
            echo "[INFO] No migration files found.\n";
            return;
        }

        $executed = 0;

        foreach ($files as $file) {
            if ($this->hasRun($file)) {
                echo "[SKIP] {$file} (already run)\n";
                continue;
            }

            echo "[RUN]  {$file}\n";
            $this->executeFile($file);
            $this->recordMigration($file);
            $executed++;
        }

        if ($executed === 0) {
            echo "[INFO] All migrations up to date.\n";
            return;
        }

        echo "[OK]   {$executed} migration(s) executed.\n";
    }

    private function ensureMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                executed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->exec($sql);
    }

    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = array_filter(
            scandir($this->migrationsPath),
            static fn(string $name): bool => str_ends_with($name, '.sql')
        );

        sort($files, SORT_STRING);

        return array_values($files);
    }

    private function hasRun(string $file): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM migrations WHERE migration = ? LIMIT 1');
        $stmt->execute([$file]);

        return (bool) $stmt->fetchColumn();
    }

    private function executeFile(string $file): void
    {
        $path = $this->migrationsPath . '/' . $file;
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new \RuntimeException("Unable to read migration file: {$path}");
        }

        if (trim($sql) === '') {
            return;
        }

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            static fn(string $statement): bool => $statement !== ''
        );

        foreach ($statements as $statement) {
            try {
                $this->db->exec($statement);
            } catch (PDOException $exception) {
                if ($this->shouldIgnoreStatementError($statement, $exception)) {
                    continue;
                }

                throw $exception;
            }
        }
    }

    private function shouldIgnoreStatementError(string $statement, PDOException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $normalizedStatement = strtoupper(trim($statement));

        if (str_starts_with($normalizedStatement, 'ALTER TABLE') && str_contains($message, 'duplicate column name')) {
            return true;
        }

        if (str_starts_with($normalizedStatement, 'CREATE INDEX') && str_contains($message, 'already exists')) {
            return true;
        }

        return false;
    }

    private function recordMigration(string $file): void
    {
        $stmt = $this->db->prepare('INSERT INTO migrations (migration) VALUES (?)');
        $stmt->execute([$file]);
    }
}
