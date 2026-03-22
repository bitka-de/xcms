<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::connection();
    echo "xcms is running. Database initialized at " . dirname(__DIR__) . "/storage/database.sqlite\n";
} catch (\Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
