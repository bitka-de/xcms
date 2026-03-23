<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $files;
    private array $server;
    private array $params = [];

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->parsePath();
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
    }

    private function parsePath(): string
    {
        $requestUri = $this->server['REQUEST_URI'] ?? '/';
        $scriptName = $this->server['SCRIPT_NAME'] ?? '/';
        
        $path = parse_url($requestUri, PHP_URL_PATH);
        $basePath = dirname($scriptName);
        
        if ($basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        return rtrim($path ?: '/', '/') ?: '/';
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function getPost(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getFile(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }
}
