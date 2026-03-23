<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\CollectionEntry;
use App\Repositories\CollectionEntryRepository;
use App\Repositories\CollectionRepository;
use App\Repositories\MediaFolderRepository;
use App\Repositories\MediaRepository;
use App\Repositories\MediaTagRepository;

class CollectionEntryAdminController extends Controller
{
    private CollectionRepository $collectionRepository;
    private CollectionEntryRepository $collectionEntryRepository;
    private MediaRepository $mediaRepository;
    private MediaFolderRepository $mediaFolderRepository;
    private MediaTagRepository $mediaTagRepository;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->collectionRepository = new CollectionRepository();
        $this->collectionEntryRepository = new CollectionEntryRepository();
        $this->mediaRepository = new MediaRepository();
        $this->mediaTagRepository = new MediaTagRepository();
        $this->mediaFolderRepository = new MediaFolderRepository();
    }

    public function index(): void
    {
        $collectionId = (int) $this->request->getParam('collectionId', 0);
        $collection = $this->collectionRepository->find($collectionId);

        if ($collection === null) {
            $this->renderNotFound();
            return;
        }

        if ($this->request->isPost()) {
            $this->handleDelete($collectionId);
            return;
        }

        $this->redirect('/admin/collections/' . $collectionId . '/edit');
    }

    public function create(): void
    {
        $collectionId = (int) $this->request->getParam('collectionId', 0);
        $collection = $this->collectionRepository->find($collectionId);

        if ($collection === null) {
            $this->renderNotFound();
            return;
        }

        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $errors = $this->validate($data);

            if ($errors !== []) {
                $this->render('admin/collection-entries/create', [
                    'pageTitle' => 'Create Entry',
                    'collection' => $collection,
                    'form' => $data,
                    'errors' => $errors,
                    ...$this->buildMediaHelperContext(),
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $entry = new CollectionEntry([
                'collection_id' => $collectionId,
                'data_json' => $data['data_json'],
                'status' => $data['status'],
            ]);

            $this->collectionEntryRepository->save($entry);
            $this->redirect('/admin/collections/' . $collectionId . '/edit?success=Entry+created');
            return;
        }

        $this->render('admin/collection-entries/create', [
            'pageTitle' => 'Create Entry',
            'collection' => $collection,
            'form' => [
                'data_json' => "{\n  \n}",
                'status' => 'draft',
            ],
            'errors' => [],
            ...$this->buildMediaHelperContext(),
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function edit(): void
    {
        $collectionId = (int) $this->request->getParam('collectionId', 0);
        $entryId = (int) $this->request->getParam('id', 0);

        $collection = $this->collectionRepository->find($collectionId);
        $entry = $this->collectionEntryRepository->find($entryId);

        if ($collection === null || $entry === null || (int) $entry->collection_id !== $collectionId) {
            $this->renderNotFound();
            return;
        }

        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $errors = $this->validate($data);

            if ($errors !== []) {
                $this->render('admin/collection-entries/edit', [
                    'pageTitle' => 'Edit Entry',
                    'collection' => $collection,
                    'entry' => $entry,
                    'form' => $data,
                    'errors' => $errors,
                    ...$this->buildMediaHelperContext(),
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $entry->data_json = $data['data_json'];
            $entry->status = $data['status'];
            $this->collectionEntryRepository->save($entry);

            $this->redirect('/admin/collections/' . $collectionId . '/edit?success=Entry+updated');
            return;
        }

        $this->render('admin/collection-entries/edit', [
            'pageTitle' => 'Edit Entry',
            'collection' => $collection,
            'entry' => $entry,
            'form' => [
                'data_json' => $entry->data_json,
                'status' => $entry->status,
            ],
            'errors' => [],
            ...$this->buildMediaHelperContext(),
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    private function handleDelete(int $collectionId): void
    {
        $action = (string) $this->request->getPost('_action', '');
        if ($action !== 'delete') {
            $this->redirect('/admin/collections/' . $collectionId . '/edit');
            return;
        }

        $entryId = (int) $this->request->getPost('id', 0);
        $entry = $this->collectionEntryRepository->find($entryId);

        if ($entry === null || (int) $entry->collection_id !== $collectionId) {
            $this->redirect('/admin/collections/' . $collectionId . '/edit?error=Entry+not+found');
            return;
        }

        $this->collectionEntryRepository->delete($entryId);
        $this->redirect('/admin/collections/' . $collectionId . '/edit?success=Entry+deleted');
    }

    private function getFormData(): array
    {
        return [
            'data_json' => trim((string) $this->request->getPost('data_json', '{}')),
            'status' => trim((string) $this->request->getPost('status', 'draft')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['data_json'] === '') {
            $errors['data_json'] = 'Data JSON is required.';
        } else {
            json_decode($data['data_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['data_json'] = 'Data JSON must be valid JSON.';
            }
        }

        $allowed = ['draft', 'published', 'archived'];
        if (!in_array($data['status'], $allowed, true)) {
            $errors['status'] = 'Status must be draft, published, or archived.';
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

    private function renderNotFound(): void
    {
        $this->response->setStatus(404);
        $this->render('pages/404', [
            'pageTitle' => 'Entry Not Found',
            'seoDescription' => 'The requested collection entry does not exist.',
            'globalCss' => '',
            'pageCss' => '',
            'pageJs' => '',
        ], 'main');
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
