<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Page;
use App\Repositories\PageRepository;

class PageAdminController extends Controller
{
    private PageRepository $pageRepository;

    public function __construct(\App\Core\Request $request, \App\Core\Response $response)
    {
        parent::__construct($request, $response);
        $this->pageRepository = new PageRepository();
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
            $data = [
                'title' => trim((string) $this->request->getPost('title', '')),
                'slug' => trim((string) $this->request->getPost('slug', '')),
                'visibility' => trim((string) $this->request->getPost('visibility', 'draft')),
                'seo_title' => trim((string) $this->request->getPost('seo_title', '')),
                'seo_description' => trim((string) $this->request->getPost('seo_description', '')),
            ];

            $errors = $this->validate($data);
            if ($errors !== []) {
                $this->render('admin/pages/edit', [
                    'pageTitle' => 'Edit Page',
                    'page' => $page,
                    'form' => $data,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $existingWithSlug = $this->pageRepository->findBySlug($data['slug']);
            if ($existingWithSlug !== null && (int) $existingWithSlug->id !== (int) $page->id) {
                $this->render('admin/pages/edit', [
                    'pageTitle' => 'Edit Page',
                    'page' => $page,
                    'form' => $data,
                    'errors' => ['slug' => 'Slug already exists.'],
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Slug already exists.',
                    ],
                ], 'admin');
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

        $this->render('admin/pages/edit', [
            'pageTitle' => 'Edit Page',
            'page' => $page,
            'form' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'visibility' => $page->visibility,
                'seo_title' => $page->seo_title ?? '',
                'seo_description' => $page->seo_description ?? '',
            ],
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
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
