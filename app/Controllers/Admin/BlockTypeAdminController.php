<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\BlockType;
use App\Repositories\BlockTypeRepository;

class BlockTypeAdminController extends Controller
{
    private BlockTypeRepository $blockTypeRepository;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->blockTypeRepository = new BlockTypeRepository();
    }

    public function index(): void
    {
        if ($this->request->isPost()) {
            $this->handleDelete();
            return;
        }

        $blockTypes = $this->blockTypeRepository->all();

        $this->render('admin/block-types/index', [
            'pageTitle' => 'Manage Block Types',
            'blockTypes' => $blockTypes,
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function create(): void
    {
        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $errors = $this->validate($data);

            if ($errors !== []) {
                $this->render('admin/block-types/create', [
                    'pageTitle' => 'Create Block Type',
                    'form' => $data,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            if ($this->blockTypeRepository->findByKey($data['slug']) !== null) {
                $this->render('admin/block-types/create', [
                    'pageTitle' => 'Create Block Type',
                    'form' => $data,
                    'errors' => ['slug' => 'Slug already exists.'],
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Slug already exists.',
                    ],
                ], 'admin');
                return;
            }

            $metadata = [
                'schema_json' => $data['schema_json'],
                'preview_image' => $data['preview_image'],
            ];

            $blockType = new BlockType([
                'name' => $data['name'],
                'key' => $data['slug'],
                'description' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'html_template' => $data['html_template'],
                'css_template' => $data['css_code'],
                'js_template' => $data['js_code'],
            ]);

            $id = $this->blockTypeRepository->save($blockType);
            $this->redirect('/admin/block-types/' . $id . '/edit?success=Block+type+created');
            return;
        }

        $this->render('admin/block-types/create', [
            'pageTitle' => 'Create Block Type',
            'form' => $this->emptyForm(),
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function edit(): void
    {
        $id = (int) $this->request->getParam('id', 0);
        $blockType = $this->blockTypeRepository->find($id);

        if ($blockType === null) {
            $this->response->setStatus(404);
            $this->render('pages/404', [
                'pageTitle' => 'Block Type Not Found',
                'seoDescription' => 'The requested block type does not exist.',
                'globalCss' => '',
                'pageCss' => '',
                'pageJs' => '',
            ], 'main');
            return;
        }

        $meta = $this->extractMetadata($blockType->description);

        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $errors = $this->validate($data);

            if ($errors !== []) {
                $this->render('admin/block-types/edit', [
                    'pageTitle' => 'Edit Block Type',
                    'blockType' => $blockType,
                    'form' => $data,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $existing = $this->blockTypeRepository->findByKey($data['slug']);
            if ($existing !== null && (int) $existing->id !== (int) $blockType->id) {
                $this->render('admin/block-types/edit', [
                    'pageTitle' => 'Edit Block Type',
                    'blockType' => $blockType,
                    'form' => $data,
                    'errors' => ['slug' => 'Slug already exists.'],
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Slug already exists.',
                    ],
                ], 'admin');
                return;
            }

            $metadata = [
                'schema_json' => $data['schema_json'],
                'preview_image' => $data['preview_image'],
            ];

            $blockType->name = $data['name'];
            $blockType->key = $data['slug'];
            $blockType->description = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $blockType->html_template = $data['html_template'];
            $blockType->css_template = $data['css_code'];
            $blockType->js_template = $data['js_code'];

            $this->blockTypeRepository->save($blockType);
            $this->redirect('/admin/block-types/' . $blockType->id . '/edit?success=Block+type+updated');
            return;
        }

        $this->render('admin/block-types/edit', [
            'pageTitle' => 'Edit Block Type',
            'blockType' => $blockType,
            'form' => [
                'name' => $blockType->name,
                'slug' => $blockType->key,
                'html_template' => $blockType->html_template,
                'css_code' => $blockType->css_template ?? '',
                'js_code' => $blockType->js_template ?? '',
                'schema_json' => $meta['schema_json'] ?? '{}',
                'preview_image' => $meta['preview_image'] ?? '',
            ],
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    private function handleDelete(): void
    {
        $action = (string) $this->request->getPost('_action', '');
        if ($action !== 'delete') {
            $this->redirect('/admin/block-types');
            return;
        }

        $id = (int) $this->request->getPost('id', 0);
        if ($id <= 0) {
            $this->redirect('/admin/block-types?error=Invalid+block+type+id');
            return;
        }

        $this->blockTypeRepository->delete($id);
        $this->redirect('/admin/block-types?success=Block+type+deleted');
    }

    private function getFormData(): array
    {
        return [
            'name' => trim((string) $this->request->getPost('name', '')),
            'slug' => trim((string) $this->request->getPost('slug', '')),
            'html_template' => (string) $this->request->getPost('html_template', ''),
            'css_code' => (string) $this->request->getPost('css_code', ''),
            'js_code' => (string) $this->request->getPost('js_code', ''),
            'schema_json' => trim((string) $this->request->getPost('schema_json', '{}')),
            'preview_image' => trim((string) $this->request->getPost('preview_image', '')),
        ];
    }

    private function emptyForm(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'html_template' => '',
            'css_code' => '',
            'js_code' => '',
            'schema_json' => '{}',
            'preview_image' => '',
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'Slug is required.';
        } elseif (!preg_match('/^[a-z0-9\/-]+$/', $data['slug'])) {
            $errors['slug'] = 'Slug can only contain lowercase letters, numbers, dashes and slashes.';
        }

        if (trim($data['html_template']) === '') {
            $errors['html_template'] = 'HTML template is required.';
        }

        if ($data['schema_json'] !== '') {
            json_decode($data['schema_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['schema_json'] = 'Schema JSON must be valid JSON.';
            }
        }

        return $errors;
    }

    private function extractMetadata(?string $description): array
    {
        if ($description === null || trim($description) === '') {
            return [];
        }

        $decoded = json_decode($description, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
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
}
