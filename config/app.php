<?php

return [
    'app_name' => 'xcms',
    'app_url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'debug' => getenv('APP_DEBUG') === 'true',
    'database_path' => dirname(__DIR__) . '/storage/database.sqlite',
];
