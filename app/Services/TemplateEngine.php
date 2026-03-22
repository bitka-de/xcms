<?php

namespace App\Services;

class TemplateEngine
{
    public function render(string $template, array $data = []): string
    {
        $result = $template;

        // Triple braces bypass escaping.
        $result = preg_replace_callback('/\{\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}\}/', function (array $matches) use ($data): string {
            $value = $this->resolveValue($data, $matches[1]);
            return $this->stringify($value);
        }, $result) ?? $result;

        // Double braces are HTML-escaped by default.
        $result = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function (array $matches) use ($data): string {
            $value = $this->resolveValue($data, $matches[1]);
            return htmlspecialchars($this->stringify($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $result) ?? $result;

        return $result;
    }

    private function resolveValue(array $data, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return '';
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return '';
    }
}
