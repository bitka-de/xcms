<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\CmsCollection;
use App\Repositories\CollectionRepository;

class CollectionAdminController extends Controller
{
    private CollectionRepository $collectionRepository;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->collectionRepository = new CollectionRepository();
    }

    public function index(): void
    {
        if ($this->request->isPost()) {
            $this->handleDelete();
            return;
        }

        $collections = $this->collectionRepository->all();

        $this->render('admin/collections/index', [
            'pageTitle' => 'Manage Collections',
            'collections' => $collections,
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function create(): void
    {
        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $errors = $this->validate($data);

            if ($errors !== []) {
                $this->render('admin/collections/create', [
                    'pageTitle' => 'Create Collection',
                    'form' => $data,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            if ($this->collectionRepository->findByKey($data['slug']) !== null) {
                $this->render('admin/collections/create', [
                    'pageTitle' => 'Create Collection',
                    'form' => $data,
                    'errors' => ['slug' => 'Slug already exists.'],
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Slug already exists.',
                    ],
                ], 'admin');
                return;
            }

            $collection = new CmsCollection([
                'name' => $data['name'],
                'key' => $data['slug'],
                'schema_json' => $data['schema_json'],
            ]);

            $id = $this->collectionRepository->save($collection);
            $this->redirect('/admin/collections/' . $id . '/edit?success=Collection+created');
            return;
        }

        $this->render('admin/collections/create', [
            'pageTitle' => 'Create Collection',
            'form' => [
                'name' => '',
                'slug' => '',
                'schema_json' => '{\n  "fields": []\n}',
            ],
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function edit(): void
    {
        $id = (int) $this->request->getParam('id', 0);
        $collection = $this->collectionRepository->find($id);

        if ($collection === null) {
            $this->response->setStatus(404);
            $this->render('pages/404', [
                'pageTitle' => 'Collection Not Found',
                'seoDescription' => 'The requested collection does not exist.',
                'globalCss' => '',
                'pageCss' => '',
                'pageJs' => '',
            ], 'main');
            return;
        }

        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $errors = $this->validate($data);

            if ($errors !== []) {
                $this->render('admin/collections/edit', [
                    'pageTitle' => 'Edit Collection',
                    'collection' => $collection,
                    'form' => $data,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $existing = $this->collectionRepository->findByKey($data['slug']);
            if ($existing !== null && (int) $existing->id !== (int) $collection->id) {
                $this->render('admin/collections/edit', [
                    'pageTitle' => 'Edit Collection',
                    'collection' => $collection,
                    'form' => $data,
                    'errors' => ['slug' => 'Slug already exists.'],
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Slug already exists.',
                    ],
                ], 'admin');
                return;
            }

            $collection->name = $data['name'];
            $collection->key = $data['slug'];
            $collection->schema_json = $data['schema_json'];

            $this->collectionRepository->save($collection);
            $this->redirect('/admin/collections/' . $collection->id . '/edit?success=Collection+updated');
            return;
        }

        $this->render('admin/collections/edit', [
            'pageTitle' => 'Edit Collection',
            'collection' => $collection,
            'form' => [
                'name' => $collection->name,
                'slug' => $collection->key,
                'schema_json' => $collection->schema_json,
            ],
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    private function handleDelete(): void
    {
        $action = (string) $this->request->getPost('_action', '');
        if ($action !== 'delete') {
            $this->redirect('/admin/collections');
            return;
        }

        $id = (int) $this->request->getPost('id', 0);
        if ($id <= 0) {
            $this->redirect('/admin/collections?error=Invalid+collection+id');
            return;
        }

        $this->collectionRepository->delete($id);
        $this->redirect('/admin/collections?success=Collection+deleted');
    }

    private function getFormData(): array
    {
        return [
            'name' => trim((string) $this->request->getPost('name', '')),
            'slug' => trim((string) $this->request->getPost('slug', '')),
            'schema_json' => trim((string) $this->request->getPost('schema_json', '{}')),
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

        if ($data['schema_json'] === '') {
            $errors['schema_json'] = 'Schema JSON is required.';
        } else {
            json_decode($data['schema_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['schema_json'] = 'Schema JSON must be valid JSON.';
            }
        }

        return $errors;
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
