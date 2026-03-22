<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private ?string $slugFallback = null;

    public function get(string $path, array $handler): self
    {
        $this->registerRoute('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, array $handler): self
    {
        $this->registerRoute('POST', $path, $handler);
        return $this;
    }

    private function registerRoute(string $method, string $path, array $handler): void
    {
        $key = $method . ':' . $path;
        $this->routes[$key] = $handler;
    }

    public function setSlugFallback(string $controller, string $action): self
    {
        $this->slugFallback = json_encode(['controller' => $controller, 'action' => $action]);
        return $this;
    }

    public function dispatch(Request $request, Response $response): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        $match = $this->matchRoute($method, $path, $request);

        if ($match) {
            $this->executeRoute($match['handler'], $request, $response);
            return;
        }

        // Try slug fallback for public pages
        if ($this->slugFallback !== null) {
            $match = $this->matchSlug($path, $request);
            if ($match) {
                $this->executeRoute($match['handler'], $request, $response);
                return;
            }
        }

        // 404
        $response->setStatus(404);
        $response->setBody('<h1>404 Not Found</h1>');
        $response->send();
    }

    private function matchRoute(string $method, string $path, Request $request): ?array
    {
        foreach ($this->routes as $key => $handler) {
            [$routeMethod, $routePath] = explode(':', $key, 2);

            if ($routeMethod !== $method) {
                continue;
            }

            $params = $this->parseParams($routePath, $path);
            if ($params !== null) {
                $request->setParams($params);
                return ['handler' => $handler];
            }
        }

        return null;
    }

    private function parseParams(string $pattern, string $path): ?array
    {
        $pattern = preg_replace_callback('/:(\w+)/', function($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);

        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {
            return array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    private function matchSlug(string $path, Request $request): ?array
    {
        if ($path === '/') {
            return null;
        }

        $slug = ltrim($path, '/');

        if (strpos($slug, '/') === false) {
            $request->setParams(['slug' => $slug]);
            return ['handler' => json_decode($this->slugFallback, true)];
        }

        return null;
    }

    private function executeRoute(array $handler, Request $request, Response $response): void
    {
        $controllerName = $handler['controller'];
        $action = $handler['action'];

        $className = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($className)) {
            $response->setStatus(500);
            $response->setBody('<h1>Controller not found: ' . htmlspecialchars($className) . '</h1>');
            $response->send();
            return;
        }

        $controller = new $className($request, $response);

        if (!method_exists($controller, $action)) {
            $response->setStatus(500);
            $response->setBody('<h1>Action not found: ' . htmlspecialchars($action) . '</h1>');
            $response->send();
            return;
        }

        $controller->$action();
    }
}
