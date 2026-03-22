<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

try {
    $request = new Request();
    $response = new Response();
    $router = new Router();

    // Load routes
    $routes = require dirname(__DIR__) . '/routes.php';
    foreach ($routes as $path => $handler) {
        // Infer HTTP method from context (defaults to GET)
        $router->get($path, $handler);
        $router->post($path, $handler);
    }

    // Set slug fallback for public pages
    $router->setSlugFallback('PageController', 'show');

    // Dispatch
    $router->dispatch($request, $response);

} catch (\Exception $e) {
    http_response_code(500);
    echo '<h1>Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    if (getenv('APP_DEBUG')) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}
