<?php

require_once __DIR__ . '/bootstrap.php';

use App\Core\Config;
use App\Core\MigrationRunner;
use App\Core\Seeder;

echo "=== xcms setup ===\n";

$dbPath = Config::get('database_path');
$dbDir = dirname($dbPath);

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "[OK]   Created storage directory: $dbDir\n";
}

if (!file_exists($dbPath)) {
    touch($dbPath);
    echo "[OK]   Created database file: $dbPath\n";
} else {
    echo "[INFO] Database file exists: $dbPath\n";
}

echo "\n[STEP] Running migrations...\n";
$migrationRunner = new MigrationRunner();
$migrationRunner->run();

echo "\n[STEP] Running seeds...\n";
$seeder = new Seeder();
$seeder->run();

echo "\n[DONE] Setup completed successfully.\n";
