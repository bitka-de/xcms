<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Repositories\MediaFolderRepository;
use App\Repositories\MediaRepository;
use App\Repositories\MediaTagRepository;
use App\Services\MediaStorageService;

class MediaAdminController extends Controller
{
    private MediaRepository $mediaRepository;
    private MediaFolderRepository $mediaFolderRepository;
    private MediaTagRepository $mediaTagRepository;
    private MediaStorageService $mediaStorageService;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->mediaRepository = new MediaRepository();
        $this->mediaFolderRepository = new MediaFolderRepository();
        $this->mediaTagRepository = new MediaTagRepository();
        $this->mediaStorageService = new MediaStorageService();
    }

    public function index(): void
    {
        $filters = $this->resolveListFiltersFromQuery();
        $mediaItems = $this->mediaRepository->searchWithFilters($filters);
        $mediaItems = $this->mediaRepository->attachTagsToMediaList($mediaItems);
        $folderTree = $this->mediaFolderRepository->getTreeList();
        $folderMap = $this->mediaFolderRepository->getIdNameMap();
        $availableTags = $this->mediaTagRepository->all();

        $this->render('admin/media/index', [
            'pageTitle' => 'Media Library',
            'mediaItems' => $mediaItems,
            'folderTree' => $folderTree,
            'folderMap' => $folderMap,
            'selectedFolderId' => $filters['folder_id'],
            'selectedTagId' => $filters['tag_id'],
            'selectedType' => $filters['type'],
            'searchQuery' => $filters['q'],
            'availableTags' => $availableTags,
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function upload(): void
    {
        if ($this->request->isPost()) {
            $form = [
                'folder_id' => trim((string) $this->request->getPost('folder_id', '')),
                'filename' => trim((string) $this->request->getPost('filename', '')),
                'title' => trim((string) $this->request->getPost('title', '')),
                'alt_text' => trim((string) $this->request->getPost('alt_text', '')),
            ];

            $file = $this->request->getFile('file');
            $errors = $this->validateUploadForm($form, $file);

            if ($errors !== []) {
                $this->render('admin/media/upload', [
                    'pageTitle' => 'Upload Media',
                    'form' => $form,
                    'folderTree' => $this->mediaFolderRepository->getTreeList(),
                    'errors' => $errors,
                    'maxFileSizeMb' => $this->mediaStorageService->maxFileSizeMb(),
                    'allowedExtensions' => $this->mediaStorageService->allowedExtensions(),
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the upload errors.',
                    ],
                ], 'admin');
                return;
            }

            $upload = $this->mediaStorageService->saveUploadedFile($file);
            $folderId = $this->normalizeFolderId($form['folder_id']);

            $media = new Media([
                'folder_id' => $folderId,
                'filename' => $upload['filename'],
                'original_name' => $upload['original_name'],
                'stored_name' => $upload['stored_name'],
                'mime_type' => $upload['mime_type'],
                'extension' => $upload['extension'],
                'file_size' => $upload['file_size'],
                'path' => $upload['path'],
                'type' => $upload['type'],
                'size_bytes' => $upload['file_size'],
                'storage_path' => ltrim($upload['path'], '/'),
                'public_url' => $upload['path'],
                'title' => $form['title'] !== '' ? $form['title'] : $upload['default_title'],
                'alt_text' => $form['alt_text'] !== '' ? $form['alt_text'] : null,
                'width' => $upload['width'],
                'height' => $upload['height'],
            ]);

            if ($form['filename'] !== '') {
                $media->filename = $this->mediaStorageService->normalizeDisplayFilename($form['filename'], $media->extension);
            }

            $id = $this->mediaRepository->save($media);
            $this->redirect('/admin/media/edit?id=' . $id . '&success=Media+uploaded');
            return;
        }

        $this->render('admin/media/upload', [
            'pageTitle' => 'Upload Media',
            'form' => [
                'folder_id' => '',
                'filename' => '',
                'title' => '',
                'alt_text' => '',
            ],
            'folderTree' => $this->mediaFolderRepository->getTreeList(),
            'errors' => [],
            'maxFileSizeMb' => $this->mediaStorageService->maxFileSizeMb(),
            'allowedExtensions' => $this->mediaStorageService->allowedExtensions(),
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function edit(): void
    {
        $id = (int) $this->request->getQuery('id', $this->request->getParam('id', 0));
        $media = $this->mediaRepository->findWithTags($id);

        if ($media === null) {
            $this->renderNotFound();
            return;
        }

        if ($this->request->isPost()) {
            $form = [
                'id' => (string) $id,
                'folder_id' => trim((string) $this->request->getPost('folder_id', '')),
                'filename' => trim((string) $this->request->getPost('filename', '')),
                'title' => trim((string) $this->request->getPost('title', '')),
                'alt_text' => trim((string) $this->request->getPost('alt_text', '')),
                'copyright_text' => trim((string) $this->request->getPost('copyright_text', '')),
                'copyright_author' => trim((string) $this->request->getPost('copyright_author', '')),
                'license_name' => trim((string) $this->request->getPost('license_name', '')),
                'license_url' => trim((string) $this->request->getPost('license_url', '')),
                'source_url' => trim((string) $this->request->getPost('source_url', '')),
                'usage_notes' => trim((string) $this->request->getPost('usage_notes', '')),
                'attribution_required' => (string) $this->request->getPost('attribution_required', '') === '1',
                'tags' => trim((string) $this->request->getPost('tags', '')),
                'rename_physical' => (string) $this->request->getPost('rename_physical', '') === '1',
            ];

            $tagNames = $this->parseCommaSeparatedTags($form['tags']);
            $form['tags'] = $this->implodeTagNames($tagNames);

            $errors = $this->validateEditForm($form, $media);

            if ($errors !== []) {
                $this->render('admin/media/edit', [
                    'pageTitle' => 'Edit Media',
                    'media' => $media,
                    'form' => $form,
                    'folderTree' => $this->mediaFolderRepository->getTreeList(),
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $media->folder_id = $this->normalizeFolderId($form['folder_id']);
            $media->filename = $this->mediaStorageService->normalizeDisplayFilename($form['filename'], $media->extension);
            $media->title = $form['title'];
            $media->alt_text = $form['alt_text'] !== '' ? $form['alt_text'] : null;
            $media->copyright_text = $this->normalizeOptionalText($form['copyright_text']);
            $media->copyright_author = $this->normalizeOptionalText($form['copyright_author']);
            $media->license_name = $this->normalizeOptionalText($form['license_name']);
            $media->license_url = $this->normalizeOptionalText($form['license_url']);
            $media->source_url = $this->normalizeOptionalText($form['source_url']);
            $media->usage_notes = $this->normalizeOptionalText($form['usage_notes']);
            $media->attribution_required = $form['attribution_required'] ? 1 : 0;

            // Keep references stable by default: display filename can change without physical rename.
            // Physical rename is opt-in and generates a safe unique server-side name.
            if ($form['rename_physical']) {
                $rename = $this->mediaStorageService->renamePhysicalFile($media, $media->filename);
                $media->stored_name = $rename['stored_name'];
                $media->path = $rename['path'];
                $media->storage_path = ltrim($rename['path'], '/');
                $media->public_url = $rename['path'];
            }

            $this->mediaRepository->save($media);
            $this->mediaRepository->syncTags((int) $media->id, $tagNames);

            $this->redirect('/admin/media/edit?id=' . $media->id . '&success=Media+updated');
            return;
        }

        $this->render('admin/media/edit', [
            'pageTitle' => 'Edit Media',
            'media' => $media,
            'form' => [
                'id' => (string) $media->id,
                'folder_id' => $media->folder_id !== null ? (string) $media->folder_id : '',
                'filename' => $media->filename,
                'title' => $media->title,
                'alt_text' => $media->alt_text ?? '',
                'copyright_text' => $media->copyright_text ?? '',
                'copyright_author' => $media->copyright_author ?? '',
                'license_name' => $media->license_name ?? '',
                'license_url' => $media->license_url ?? '',
                'source_url' => $media->source_url ?? '',
                'usage_notes' => $media->usage_notes ?? '',
                'attribution_required' => (int) $media->attribution_required === 1,
                'tags' => $this->tagsToCommaSeparated($media->tags),
                'rename_physical' => false,
            ],
            'folderTree' => $this->mediaFolderRepository->getTreeList(),
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function delete(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/media');
            return;
        }

        $action = (string) $this->request->getPost('_action', '');
        if ($action !== 'delete') {
            $this->redirect('/admin/media');
            return;
        }

        $id = (int) $this->request->getPost('id', 0);
        $media = $this->mediaRepository->find($id);

        if ($media === null) {
            $this->redirect('/admin/media?error=Media+not+found');
            return;
        }

        $this->mediaStorageService->deletePhysicalFile($media);

        $this->mediaRepository->delete($id);
        $this->redirect('/admin/media?success=Media+deleted');
    }

    public function folders(): void
    {
        $this->render('admin/media/folders', [
            'pageTitle' => 'Media Folders',
            'folders' => $this->mediaFolderRepository->allWithParents(),
            'folderTree' => $this->mediaFolderRepository->getTreeList(),
            'mediaCountByFolder' => $this->mediaFolderRepository->mediaCountByFolder(),
            'childCountByFolder' => $this->mediaFolderRepository->childCountByFolder(),
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function createFolder(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/media/folders');
            return;
        }

        $name = trim((string) $this->request->getPost('name', ''));
        $parentId = $this->normalizeFolderId((string) $this->request->getPost('parent_id', ''));

        if ($name === '') {
            $this->redirect('/admin/media/folders?error=Folder+name+is+required');
            return;
        }

        if ($parentId !== null && $this->mediaFolderRepository->find($parentId) === null) {
            $this->redirect('/admin/media/folders?error=Invalid+parent+folder');
            return;
        }

        $slug = $this->mediaFolderRepository->generateUniqueSlug($name, $parentId);
        $folder = new MediaFolder([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
        ]);

        $this->mediaFolderRepository->save($folder);
        $this->redirect('/admin/media/folders?success=Folder+created');
    }

    public function editFolder(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/media/folders');
            return;
        }

        $id = (int) $this->request->getPost('id', 0);
        $folder = $this->mediaFolderRepository->find($id);

        if ($folder === null) {
            $this->redirect('/admin/media/folders?error=Folder+not+found');
            return;
        }

        $name = trim((string) $this->request->getPost('name', ''));
        $parentId = $this->normalizeFolderId((string) $this->request->getPost('parent_id', ''));

        if ($name === '') {
            $this->redirect('/admin/media/folders?error=Folder+name+is+required');
            return;
        }

        if ($parentId !== null) {
            if ($parentId === (int) $folder->id) {
                $this->redirect('/admin/media/folders?error=Folder+cannot+be+its+own+parent');
                return;
            }

            if ($this->mediaFolderRepository->isDescendantOf($parentId, (int) $folder->id)) {
                $this->redirect('/admin/media/folders?error=Cannot+move+folder+under+its+own+descendant');
                return;
            }
        }

        $folder->name = $name;
        $folder->parent_id = $parentId;
        $folder->slug = $this->mediaFolderRepository->generateUniqueSlug($name, $parentId, (int) $folder->id);
        $this->mediaFolderRepository->save($folder);

        $this->redirect('/admin/media/folders?success=Folder+updated');
    }

    public function deleteFolder(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/media/folders');
            return;
        }

        $id = (int) $this->request->getPost('id', 0);
        $folder = $this->mediaFolderRepository->find($id);

        if ($folder === null) {
            $this->redirect('/admin/media/folders?error=Folder+not+found');
            return;
        }

        if ($this->mediaFolderRepository->hasChildren($id)) {
            $this->redirect('/admin/media/folders?error=Folder+has+child+folders');
            return;
        }

        if ($this->mediaRepository->countByFolderId($id) > 0) {
            $this->redirect('/admin/media/folders?error=Folder+contains+media+items');
            return;
        }

        $this->mediaFolderRepository->delete($id);
        $this->redirect('/admin/media/folders?success=Folder+deleted');
    }

    private function validateUploadForm(array $form, ?array $file): array
    {
        $errors = $this->mediaStorageService->validateUploadedFile($file);

        $folderId = $this->normalizeFolderId($form['folder_id']);
        if ($form['folder_id'] !== '' && $folderId === null) {
            $errors['folder_id'] = 'Invalid folder.';
        }

        if ($folderId !== null && $this->mediaFolderRepository->find($folderId) === null) {
            $errors['folder_id'] = 'Selected folder does not exist.';
        }

        if ($form['filename'] !== '' && !$this->mediaStorageService->isValidDisplayFilename($form['filename'])) {
            $errors['filename'] = 'Filename contains invalid characters.';
        }

        return $errors;
    }

    private function validateEditForm(array $form, Media $media): array
    {
        $errors = [];

        $folderId = $this->normalizeFolderId($form['folder_id']);
        if ($form['folder_id'] !== '' && $folderId === null) {
            $errors['folder_id'] = 'Invalid folder.';
        }

        if ($folderId !== null && $this->mediaFolderRepository->find($folderId) === null) {
            $errors['folder_id'] = 'Selected folder does not exist.';
        }

        if ($form['filename'] === '') {
            $errors['filename'] = 'Filename is required.';
        } elseif (!$this->mediaStorageService->isValidDisplayFilename($form['filename'])) {
            $errors['filename'] = 'Filename contains invalid characters.';
        }

        if ($form['title'] === '') {
            $errors['title'] = 'Title is required.';
        }

        if ($form['license_url'] !== '' && !$this->isValidOptionalUrl($form['license_url'])) {
            $errors['license_url'] = 'License URL must be a valid URL.';
        }

        if ($form['source_url'] !== '' && !$this->isValidOptionalUrl($form['source_url'])) {
            $errors['source_url'] = 'Source URL must be a valid URL.';
        }

        if ($form['rename_physical']) {
            $targetStoredName = $this->mediaStorageService->previewStoredName($form['filename'], $media->extension);
            $existing = $this->mediaRepository->findByStoredName($targetStoredName);
            if ($existing !== null && (int) $existing->id !== (int) $media->id) {
                $errors['filename'] = 'Filename already exists. Please choose a different name.';
            }
        }

        return $errors;
    }

    private function normalizeFolderId(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (!ctype_digit($raw)) {
            return null;
        }

        $id = (int) $raw;
        return $id > 0 ? $id : null;
    }

    private function resolveListFiltersFromQuery(): array
    {
        $q = trim((string) $this->request->getQuery('q', ''));

        $folderRaw = trim((string) $this->request->getQuery('folder_id', ''));
        $folderId = null;
        if ($folderRaw !== '' && ctype_digit($folderRaw)) {
            $candidate = (int) $folderRaw;
            if ($candidate > 0 && $this->mediaFolderRepository->find($candidate) !== null) {
                $folderId = $candidate;
            }
        }

        $tagRaw = trim((string) $this->request->getQuery('tag_id', ''));
        $tagId = null;
        if ($tagRaw !== '' && ctype_digit($tagRaw)) {
            $candidate = (int) $tagRaw;
            if ($candidate > 0 && $this->mediaTagRepository->find($candidate) !== null) {
                $tagId = $candidate;
            }
        }

        $type = trim((string) $this->request->getQuery('type', ''));
        if (!in_array($type, ['image', 'video', 'document'], true)) {
            $type = '';
        }

        return [
            'q' => $q,
            'folder_id' => $folderId,
            'tag_id' => $tagId,
            'type' => $type,
        ];
    }

    private function parseCommaSeparatedTags(string $input): array
    {
        $normalized = [];

        foreach (explode(',', $input) as $part) {
            $value = trim($part);
            if ($value === '') {
                continue;
            }

            $normalized[strtolower($value)] = $value;
        }

        return array_values($normalized);
    }

    private function implodeTagNames(array $tagNames): string
    {
        return implode(', ', $tagNames);
    }

    private function tagsToCommaSeparated(array $tags): string
    {
        $names = [];
        foreach ($tags as $tag) {
            if (!is_object($tag) || !property_exists($tag, 'name')) {
                continue;
            }

            $name = trim((string) $tag->name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return implode(', ', $names);
    }

    private function normalizeOptionalText(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function isValidOptionalUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
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
            'pageTitle' => 'Media Not Found',
            'seoDescription' => 'The requested media item does not exist.',
            'globalCss' => '',
            'pageCss' => '',
            'pageJs' => '',
        ], 'main');
    }
}