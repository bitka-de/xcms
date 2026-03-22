<?php

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/app/Core/Autoloader.php';
require_once BASE_PATH . '/app/Core/Config.php';
require_once BASE_PATH . '/app/Core/Database.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Database;

Autoloader::register(BASE_PATH);

Config::load(BASE_PATH . '/config/app.php');

$dbPath = Config::get('database_path');
Database::initialize($dbPath);

return true;
