<?php

namespace App\Services;

use App\Models\BlockType;
use App\Models\PageBlock;

class BlockRenderer
{
    private TemplateEngine $templateEngine;
    private CssScoper $cssScoper;

    public function __construct(?TemplateEngine $templateEngine = null, ?CssScoper $cssScoper = null)
    {
        $this->templateEngine = $templateEngine ?? new TemplateEngine();
        $this->cssScoper = $cssScoper ?? new CssScoper();
    }

    public function render(PageBlock $pageBlock, BlockType $blockType): array
    {
        $blockId = $this->resolveBlockId($pageBlock);
        $props = $pageBlock->getProps();

        $innerHtml = $this->templateEngine->render($blockType->html_template, $props);
        $html = '<div class="cms-block" data-block-id="' . htmlspecialchars($blockId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
            . $innerHtml
            . '</div>';

        $css = '';
        if ($blockType->hasCss()) {
            $css = $this->cssScoper->scope((string) $blockType->css_template, $blockId);
        }

        $js = $blockType->hasJs() ? (string) $blockType->js_template : '';

        return [
            'block_id' => $blockId,
            'page_block_id' => $pageBlock->id,
            'block_type_id' => $blockType->id,
            'block_type_key' => $blockType->key,
            'html' => $html,
            'css' => $css,
            'js' => $js,
        ];
    }

    private function resolveBlockId(PageBlock $pageBlock): string
    {
        if ($pageBlock->id !== null) {
            return (string) $pageBlock->id;
        }

        return 'tmp-' . spl_object_id($pageBlock);
    }
}
