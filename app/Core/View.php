<?php

namespace App\Core;

class View
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = BASE_PATH . '/app/Views';
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->basePath . '/' . $view . '.php';

        if (!file_exists($path)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        ob_start();
        extract($data, EXTR_SKIP);
        require $path;
        return ob_get_clean();
    }
}
