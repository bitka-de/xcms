<?php

namespace App\Services;

class CssScoper
{
    public function scope(string $css, string $blockId): string
    {
        $prefix = '[data-block-id="' . addslashes($blockId) . '"]';

        // Regex-based scoping is intentionally simple and may not fully support complex nested at-rules.
        $scoped = preg_replace_callback('/(^|})\s*([^@{}][^{]+)\s*\{/m', function (array $matches) use ($prefix): string {
            $leading = $matches[1];
            $selectorGroup = trim($matches[2]);

            $selectors = array_map('trim', explode(',', $selectorGroup));
            $selectors = array_map(function (string $selector) use ($prefix): string {
                if ($selector === '') {
                    return $selector;
                }

                if (str_starts_with($selector, $prefix)) {
                    return $selector;
                }

                if ($selector === ':root') {
                    return $prefix;
                }

                if (str_starts_with($selector, ':')) {
                    return $prefix . $selector;
                }

                return $prefix . ' ' . $selector;
            }, $selectors);

            return $leading . ' ' . implode(', ', $selectors) . ' {';
        }, $css);

        return $scoped ?? $css;
    }
}
