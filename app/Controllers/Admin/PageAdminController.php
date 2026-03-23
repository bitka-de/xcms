<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Repositories\BlockTypeRepository;
use App\Repositories\MediaFolderRepository;
use App\Repositories\MediaRepository;
use App\Repositories\MediaTagRepository;
use App\Repositories\PageBlockRepository;
use App\Repositories\PageRepository;

class PageAdminController extends Controller
{
    private PageRepository $pageRepository;
    private PageBlockRepository $pageBlockRepository;
    private BlockTypeRepository $blockTypeRepository;
    private MediaRepository $mediaRepository;
    private MediaFolderRepository $mediaFolderRepository;
    private MediaTagRepository $mediaTagRepository;

    public function __construct(\App\Core\Request $request, \App\Core\Response $response)
    {
        parent::__construct($request, $response);
        $this->pageRepository = new PageRepository();
        $this->pageBlockRepository = new PageBlockRepository();
        $this->blockTypeRepository = new BlockTypeRepository();
        $this->mediaRepository = new MediaRepository();
        $this->mediaFolderRepository = new MediaFolderRepository();
        $this->mediaTagRepository = new MediaTagRepository();
    }

    public function index(): void
    {
        if ($this->request->isPost()) {
            $this->handleDelete();
            return;
        }

        $pages = $this->pageRepository->all();

        $this->render('admin/pages/index', [
            'pageTitle' => 'Manage Pages',
            'pages' => $pages,
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function create(): void
    {
        if ($this->request->isPost()) {
            $data = [
                'title' => trim((string) $this->request->getPost('title', '')),
                'slug' => trim((string) $this->request->getPost('slug', '')),
                'visibility' => trim((string) $this->request->getPost('visibility', 'draft')),
                'seo_title' => trim((string) $this->request->getPost('seo_title', '')),
                'seo_description' => trim((string) $this->request->getPost('seo_description', '')),
            ];

            $errors = $this->validate($data);
            if ($errors !== []) {
                $this->render('admin/pages/create', [
                    'pageTitle' => 'Create Page',
                    'form' => $data,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            if ($this->pageRepository->findBySlug($data['slug']) !== null) {
                $this->render('admin/pages/create', [
                    'pageTitle' => 'Create Page',
                    'form' => $data,
                    'errors' => ['slug' => 'Slug already exists.'],
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Slug already exists.',
                    ],
                ], 'admin');
                return;
            }

            $page = new Page([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'visibility' => $data['visibility'],
                'seo_title' => $data['seo_title'] !== '' ? $data['seo_title'] : null,
                'seo_description' => $data['seo_description'] !== '' ? $data['seo_description'] : null,
            ]);

            $id = $this->pageRepository->save($page);
            $this->redirect('/admin/pages/' . $id . '/edit?success=Page+created');
            return;
        }

        $this->render('admin/pages/create', [
            'pageTitle' => 'Create Page',
            'form' => [
                'title' => '',
                'slug' => '',
                'visibility' => 'draft',
                'seo_title' => '',
                'seo_description' => '',
            ],
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function edit(): void
    {
        $id = (int) $this->request->getParam('id', 0);
        $page = $this->pageRepository->find($id);

        if ($page === null) {
            $this->response->setStatus(404);
            $this->render('pages/404', [
                'pageTitle' => 'Page Not Found',
                'seoDescription' => 'The requested page does not exist.',
                'globalCss' => '',
                'pageCss' => '',
                'pageJs' => '',
            ], 'main');
            return;
        }

        if ($this->request->isPost()) {
            $action = (string) $this->request->getPost('_action', 'save_page');

            if ($action === 'add_block') {
                $this->handleAddBlock($page);
                return;
            }

            if ($action === 'update_block') {
                $this->handleUpdateBlock($page);
                return;
            }

            if ($action === 'delete_block') {
                $this->handleDeleteBlock($page);
                return;
            }

            $data = [
                'title' => trim((string) $this->request->getPost('title', '')),
                'slug' => trim((string) $this->request->getPost('slug', '')),
                'visibility' => trim((string) $this->request->getPost('visibility', 'draft')),
                'seo_title' => trim((string) $this->request->getPost('seo_title', '')),
                'seo_description' => trim((string) $this->request->getPost('seo_description', '')),
            ];

            $errors = $this->validate($data);
            if ($errors !== []) {
                $this->renderEditPage($page, $data, $errors, [
                    'type' => 'error',
                    'message' => 'Please fix the validation errors.',
                ]);
                return;
            }

            $existingWithSlug = $this->pageRepository->findBySlug($data['slug']);
            if ($existingWithSlug !== null && (int) $existingWithSlug->id !== (int) $page->id) {
                $this->renderEditPage($page, $data, ['slug' => 'Slug already exists.'], [
                    'type' => 'error',
                    'message' => 'Slug already exists.',
                ]);
                return;
            }

            $page->title = $data['title'];
            $page->slug = $data['slug'];
            $page->visibility = $data['visibility'];
            $page->seo_title = $data['seo_title'] !== '' ? $data['seo_title'] : null;
            $page->seo_description = $data['seo_description'] !== '' ? $data['seo_description'] : null;

            $this->pageRepository->save($page);
            $this->redirect('/admin/pages/' . $page->id . '/edit?success=Page+updated');
            return;
        }

        $this->renderEditPage(
            $page,
            [
                'title' => $page->title,
                'slug' => $page->slug,
                'visibility' => $page->visibility,
                'seo_title' => $page->seo_title ?? '',
                'seo_description' => $page->seo_description ?? '',
            ],
            [],
            $this->readFlashFromQuery()
        );
    }

    private function handleAddBlock(Page $page): void
    {
        $blockData = $this->getBlockFormData();
        $errors = $this->validateBlockData($blockData);

        if ($errors !== []) {
            $this->renderEditPage($page, $this->pageFormFromPage($page), [], [
                'type' => 'error',
                'message' => 'Please fix the block validation errors.',
            ], $errors, $blockData);
            return;
        }

        $pageBlock = new PageBlock([
            'page_id' => (int) $page->id,
            'block_type_id' => (int) $blockData['block_type_id'],
            'sort_order' => (int) $blockData['sort_order'],
            'props_json' => $blockData['props_json'],
            'bindings_json' => $blockData['bindings_json'],
        ]);

        $this->pageBlockRepository->save($pageBlock);
        $this->redirect('/admin/pages/' . $page->id . '/edit?success=Block+added');
    }

    private function handleUpdateBlock(Page $page): void
    {
        $blockId = (int) $this->request->getPost('block_id', 0);
        $pageBlock = $this->pageBlockRepository->find($blockId);

        if ($pageBlock === null || (int) $pageBlock->page_id !== (int) $page->id) {
            $this->redirect('/admin/pages/' . $page->id . '/edit?error=Block+not+found');
            return;
        }

        $blockData = $this->getBlockFormData();
        $errors = $this->validateBlockData($blockData);

        if ($errors !== []) {
            $this->renderEditPage($page, $this->pageFormFromPage($page), [], [
                'type' => 'error',
                'message' => 'Please fix the block validation errors.',
            ], ['existing_' . $blockId => $errors], []);
            return;
        }

        $pageBlock->block_type_id = (int) $blockData['block_type_id'];
        $pageBlock->sort_order = (int) $blockData['sort_order'];
        $pageBlock->props_json = $blockData['props_json'];
        $pageBlock->bindings_json = $blockData['bindings_json'];

        $this->pageBlockRepository->save($pageBlock);
        $this->redirect('/admin/pages/' . $page->id . '/edit?success=Block+updated');
    }

    private function handleDeleteBlock(Page $page): void
    {
        $blockId = (int) $this->request->getPost('block_id', 0);
        $pageBlock = $this->pageBlockRepository->find($blockId);

        if ($pageBlock === null || (int) $pageBlock->page_id !== (int) $page->id) {
            $this->redirect('/admin/pages/' . $page->id . '/edit?error=Block+not+found');
            return;
        }

        $this->pageBlockRepository->delete($blockId);
        $this->redirect('/admin/pages/' . $page->id . '/edit?success=Block+deleted');
    }

    private function handleDelete(): void
    {
        $action = (string) $this->request->getPost('_action', '');
        if ($action !== 'delete') {
            $this->redirect('/admin/pages');
            return;
        }

        $id = (int) $this->request->getPost('id', 0);
        if ($id <= 0) {
            $this->redirect('/admin/pages?error=Invalid+page+id');
            return;
        }

        $this->pageRepository->delete($id);
        $this->redirect('/admin/pages?success=Page+deleted');
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['title'] === '') {
            $errors['title'] = 'Title is required.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'Slug is required.';
        } elseif (!preg_match('/^[a-z0-9\/-]+$/', $data['slug'])) {
            $errors['slug'] = 'Slug can only contain lowercase letters, numbers, dashes and slashes.';
        }

        $allowedVisibility = ['public', 'private', 'draft'];
        if (!in_array($data['visibility'], $allowedVisibility, true)) {
            $errors['visibility'] = 'Visibility must be public, private, or draft.';
        }

        return $errors;
    }

    private function getBlockFormData(): array
    {
        return [
            'block_type_id' => (string) $this->request->getPost('block_type_id', ''),
            'sort_order' => (string) $this->request->getPost('sort_order', '0'),
            'props_json' => (string) $this->request->getPost('props_json', '{}'),
            'bindings_json' => (string) $this->request->getPost('bindings_json', '{}'),
        ];
    }

    private function validateBlockData(array $data): array
    {
        $errors = [];

        $blockTypeId = (int) $data['block_type_id'];
        if ($blockTypeId <= 0 || $this->blockTypeRepository->find($blockTypeId) === null) {
            $errors['block_type_id'] = 'A valid block type is required.';
        }

        if (filter_var($data['sort_order'], FILTER_VALIDATE_INT) === false) {
            $errors['sort_order'] = 'Sort order must be an integer.';
        }

        json_decode($data['props_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors['props_json'] = 'Props JSON must be valid JSON.';
        }

        json_decode($data['bindings_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors['bindings_json'] = 'Bindings JSON must be valid JSON.';
        }

        return $errors;
    }

    private function renderEditPage(
        Page $page,
        array $form,
        array $errors,
        ?array $flash = null,
        array $blockErrors = [],
        array $newBlockForm = []
    ): void {
        $pageBlocks = $this->pageBlockRepository->findByPageIdOrdered((int) $page->id);
        $blockTypes = $this->blockTypeRepository->getAllByName();

        $blockTypeMap = [];
        foreach ($blockTypes as $blockType) {
            $blockTypeMap[(int) $blockType->id] = $blockType;
        }

        $mediaHelperContext = $this->buildMediaHelperContext();

        $this->render('admin/pages/edit', [
            'pageTitle' => 'Edit Page',
            'page' => $page,
            'form' => $form,
            'errors' => $errors,
            'flash' => $flash,
            'pageBlocks' => $pageBlocks,
            'blockTypes' => $blockTypes,
            'blockTypeMap' => $blockTypeMap,
            'blockErrors' => $blockErrors,
            ...$mediaHelperContext,
            'newBlockForm' => $newBlockForm !== [] ? $newBlockForm : [
                'block_type_id' => '',
                'sort_order' => (string) ($this->pageBlockRepository->getMaxSortOrderForPage((int) $page->id) + 1),
                'props_json' => '{}',
                'bindings_json' => '{}',
            ],
        ], 'admin');
    }

    private function pageFormFromPage(Page $page): array
    {
        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'visibility' => $page->visibility,
            'seo_title' => $page->seo_title ?? '',
            'seo_description' => $page->seo_description ?? '',
        ];
    }

    private function readFlashFromQuery(): ?array
    {
        $success = trim((string) $this->request->getQuery('success', ''));
        if ($success !== '') {
            return ['type' => 'success', 'message' => $success];
        }

        $error = trim((string) $this->request->getQuery('error', ''));
        if ($error !== '') {
            return ['type' => 'error', 'message' => $error];
        }

        return null;
    }

    private function buildMediaHelperContext(): array
    {
        $q = trim((string) $this->request->getQuery('media_q', ''));

        $folderRaw = trim((string) $this->request->getQuery('media_folder_id', ''));
        $folderId = null;
        if ($folderRaw !== '' && ctype_digit($folderRaw)) {
            $candidate = (int) $folderRaw;
            if ($candidate > 0 && $this->mediaFolderRepository->find($candidate) !== null) {
                $folderId = $candidate;
            }
        }

        $tagRaw = trim((string) $this->request->getQuery('media_tag_id', ''));
        $tagId = null;
        if ($tagRaw !== '' && ctype_digit($tagRaw)) {
            $candidate = (int) $tagRaw;
            if ($candidate > 0 && $this->mediaTagRepository->find($candidate) !== null) {
                $tagId = $candidate;
            }
        }

        $mediaItems = $this->mediaRepository->searchWithFilters([
            'q' => $q,
            'folder_id' => $folderId,
            'tag_id' => $tagId,
            'type' => '',
        ]);

        return [
            'mediaFolders' => $this->mediaFolderRepository->getTreeList(),
            'mediaTags' => $this->mediaTagRepository->all(),
            'mediaItems' => $this->mediaRepository->attachTagsToMediaList($mediaItems),
            'selectedMediaFolderId' => $folderId,
            'selectedMediaTagId' => $tagId,
            'mediaSearchQuery' => $q,
        ];
    }
}
