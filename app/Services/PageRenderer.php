<?php

namespace App\Services;

use App\Models\BlockType;
use App\Repositories\BlockTypeRepository;
use App\Repositories\DesignSettingRepository;
use App\Repositories\PageBlockRepository;
use App\Repositories\PageRepository;

class PageRenderer
{
    private PageRepository $pageRepository;
    private PageBlockRepository $pageBlockRepository;
    private BlockTypeRepository $blockTypeRepository;
    private DesignSettingRepository $designSettingRepository;
    private BlockRenderer $blockRenderer;

    public function __construct(
        ?PageRepository $pageRepository = null,
        ?PageBlockRepository $pageBlockRepository = null,
        ?BlockTypeRepository $blockTypeRepository = null,
        ?DesignSettingRepository $designSettingRepository = null,
        ?BlockRenderer $blockRenderer = null
    ) {
        $this->pageRepository = $pageRepository ?? new PageRepository();
        $this->pageBlockRepository = $pageBlockRepository ?? new PageBlockRepository();
        $this->blockTypeRepository = $blockTypeRepository ?? new BlockTypeRepository();
        $this->designSettingRepository = $designSettingRepository ?? new DesignSettingRepository();
        $this->blockRenderer = $blockRenderer ?? new BlockRenderer();
    }

    public function renderPublicBySlug(string $slug): ?array
    {
        $page = $this->pageRepository->findPublicBySlug($slug);

        if ($page === null) {
            return null;
        }

        $pageBlocks = $this->pageBlockRepository->findByPageIdOrdered((int) $page->id);
        $blockTypes = $this->loadBlockTypesById($pageBlocks);

        $renderedBlocks = [];
        $htmlParts = [];
        $cssParts = [];
        $jsParts = [];

        foreach ($pageBlocks as $pageBlock) {
            $blockType = $blockTypes[$pageBlock->block_type_id] ?? null;
            if ($blockType === null) {
                continue;
            }

            $rendered = $this->blockRenderer->render($pageBlock, $blockType);
            $renderedBlocks[] = $rendered;
            $htmlParts[] = $rendered['html'];

            if ($rendered['css'] !== '') {
                $cssParts[] = $rendered['css'];
            }

            if ($rendered['js'] !== '') {
                $jsParts[] = $rendered['js'];
            }
        }

        $designSettings = $this->designSettingRepository->getAllAsKeyValue();
        $designCss = $this->buildDesignCssVariables($designSettings);

        return [
            'page' => $page,
            'page_array' => $page->toArray(),
            'blocks' => $renderedBlocks,
            'content_html' => implode("\n", $htmlParts),
            'css' => implode("\n\n", $cssParts),
            'js' => implode("\n\n", $jsParts),
            'design_settings' => $designSettings,
            'design_css_variables' => $designCss,
            'meta' => [
                'title' => $page->seo_title ?: $page->title,
                'description' => $page->seo_description ?: ($page->description ?? ''),
            ],
        ];
    }

    private function loadBlockTypesById(array $pageBlocks): array
    {
        $result = [];

        foreach ($pageBlocks as $pageBlock) {
            $blockTypeId = (int) $pageBlock->block_type_id;

            if (isset($result[$blockTypeId])) {
                continue;
            }

            $blockType = $this->blockTypeRepository->find($blockTypeId);
            if ($blockType !== null) {
                $result[$blockTypeId] = $blockType;
            }
        }

        return $result;
    }

    private function buildDesignCssVariables(array $settings): string
    {
        if ($settings === []) {
            return '';
        }

        $lines = [':root {'];

        foreach ($settings as $key => $value) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key) ?: 'var';
            $safeValue = str_replace(["\n", "\r"], '', (string) $value);
            $lines[] = '  --' . $safeKey . ': ' . $safeValue . ';';
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }
}
