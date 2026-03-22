<?php

$dbPathBeforeBootstrap = __DIR__ . '/storage/database.sqlite';
$dbDirBeforeBootstrap = dirname($dbPathBeforeBootstrap);
$storageExisted = is_dir($dbDirBeforeBootstrap);
$databaseExisted = file_exists($dbPathBeforeBootstrap);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Config;
use App\Core\MigrationRunner;
use App\Core\Seeder;

echo "=== xcms setup ===\n";

$dbPath = Config::get('database_path');
$dbDir = dirname($dbPath);

if (!$storageExisted && is_dir($dbDir)) {
    echo "[OK]   Created storage directory: $dbDir\n";
} else {
    echo "[INFO] Storage directory ready: $dbDir\n";
}

if (!$databaseExisted && file_exists($dbPath)) {
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
