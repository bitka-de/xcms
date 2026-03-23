<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Media;
use App\Repositories\MediaRepository;

class MediaAdminController extends Controller
{
    private const MAX_FILE_SIZE = 10485760;

    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'pdf',
    ];

    private const ALLOWED_MIME_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'pdf' => ['application/pdf'],
    ];

    private MediaRepository $mediaRepository;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->mediaRepository = new MediaRepository();
    }

    public function index(): void
    {
        if ($this->request->isPost()) {
            $this->handleDelete();
            return;
        }

        $this->render('admin/media/index', [
            'pageTitle' => 'Media Library',
            'mediaItems' => $this->mediaRepository->all(),
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function create(): void
    {
        if ($this->request->isPost()) {
            $form = [
                'title' => trim((string) $this->request->getPost('title', '')),
                'alt_text' => trim((string) $this->request->getPost('alt_text', '')),
            ];

            $file = $this->request->getFile('file');
            $errors = $this->validateUpload($form, $file);

            if ($errors !== []) {
                $this->render('admin/media/create', [
                    'pageTitle' => 'Upload Media',
                    'form' => $form,
                    'errors' => $errors,
                    'maxFileSizeMb' => (int) (self::MAX_FILE_SIZE / 1024 / 1024),
                    'allowedExtensions' => self::ALLOWED_EXTENSIONS,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the upload errors.',
                    ],
                ], 'admin');
                return;
            }

            $upload = $this->storeUploadedFile($file, $form);

            $media = new Media([
                'title' => $form['title'] !== '' ? $form['title'] : $upload['default_title'],
                'alt_text' => $form['alt_text'] !== '' ? $form['alt_text'] : null,
                'original_name' => $upload['original_name'],
                'filename' => $upload['filename'],
                'mime_type' => $upload['mime_type'],
                'extension' => $upload['extension'],
                'size_bytes' => $upload['size_bytes'],
                'storage_path' => $upload['storage_path'],
                'public_url' => $upload['public_url'],
                'width' => $upload['width'],
                'height' => $upload['height'],
            ]);

            $id = $this->mediaRepository->save($media);
            $this->redirect('/admin/media/' . $id . '/edit?success=Media+uploaded');
            return;
        }

        $this->render('admin/media/create', [
            'pageTitle' => 'Upload Media',
            'form' => [
                'title' => '',
                'alt_text' => '',
            ],
            'errors' => [],
            'maxFileSizeMb' => (int) (self::MAX_FILE_SIZE / 1024 / 1024),
            'allowedExtensions' => self::ALLOWED_EXTENSIONS,
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    public function edit(): void
    {
        $id = (int) $this->request->getParam('id', 0);
        $media = $this->mediaRepository->find($id);

        if ($media === null) {
            $this->renderNotFound();
            return;
        }

        if ($this->request->isPost()) {
            $form = [
                'title' => trim((string) $this->request->getPost('title', '')),
                'alt_text' => trim((string) $this->request->getPost('alt_text', '')),
            ];

            $errors = [];
            if ($form['title'] === '') {
                $errors['title'] = 'Title is required.';
            }

            if ($errors !== []) {
                $this->render('admin/media/edit', [
                    'pageTitle' => 'Edit Media',
                    'media' => $media,
                    'form' => $form,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $media->title = $form['title'];
            $media->alt_text = $form['alt_text'] !== '' ? $form['alt_text'] : null;
            $this->mediaRepository->save($media);

            $this->redirect('/admin/media/' . $media->id . '/edit?success=Media+updated');
            return;
        }

        $this->render('admin/media/edit', [
            'pageTitle' => 'Edit Media',
            'media' => $media,
            'form' => [
                'title' => $media->title,
                'alt_text' => $media->alt_text ?? '',
            ],
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    private function handleDelete(): void
    {
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

        $absolutePath = BASE_PATH . '/public/' . ltrim($media->storage_path, '/');
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        $this->mediaRepository->delete($id);
        $this->redirect('/admin/media?success=Media+deleted');
    }

    private function validateUpload(array $form, ?array $file): array
    {
        $errors = [];

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['file'] = 'A file is required.';
            return $errors;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors['file'] = 'Upload failed. Please try again.';
            return $errors;
        }

        if (!isset($file['tmp_name'], $file['name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
            $errors['file'] = 'Invalid uploaded file.';
            return $errors;
        }

        if ($form['title'] === '') {
            $filename = pathinfo((string) $file['name'], PATHINFO_FILENAME);
            if (trim($filename) === '') {
                $errors['title'] = 'Title is required.';
            }
        }

        if ((int) $file['size'] <= 0) {
            $errors['file'] = 'Uploaded file is empty.';
        } elseif ((int) $file['size'] > self::MAX_FILE_SIZE) {
            $errors['file'] = 'Maximum file size is ' . (int) (self::MAX_FILE_SIZE / 1024 / 1024) . ' MB.';
        }

        $originalName = (string) $file['name'];
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $errors['file'] = 'Allowed file types: ' . implode(', ', self::ALLOWED_EXTENSIONS) . '.';
            return $errors;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo !== false ? (string) finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        $allowedMimeTypes = self::ALLOWED_MIME_TYPES[$extension] ?? [];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $errors['file'] = 'Detected MIME type is not allowed for this file extension.';
        }

        return $errors;
    }

    private function storeUploadedFile(array $file, array $form): array
    {
        $this->ensureUploadDirectory();

        $originalName = (string) $file['name'];
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBaseName = $this->slugify($baseName !== '' ? $baseName : 'media');
        $filename = $safeBaseName . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $relativePath = 'uploads/' . $filename;
        $absolutePath = BASE_PATH . '/public/' . $relativePath;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        $mimeType = (string) mime_content_type($absolutePath);
        $dimensions = $this->extractDimensions($absolutePath, $mimeType);

        return [
            'default_title' => $this->humanizeTitle($baseName),
            'original_name' => $originalName,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size_bytes' => (int) filesize($absolutePath),
            'storage_path' => $relativePath,
            'public_url' => '/' . $relativePath,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ];
    }

    private function ensureUploadDirectory(): void
    {
        $directory = BASE_PATH . '/public/uploads';

        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create upload directory.');
        }
    }

    private function extractDimensions(string $absolutePath, string $mimeType): array
    {
        if (!str_starts_with($mimeType, 'image/')) {
            return ['width' => null, 'height' => null];
        }

        $dimensions = @getimagesize($absolutePath);
        if ($dimensions === false) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => isset($dimensions[0]) ? (int) $dimensions[0] : null,
            'height' => isset($dimensions[1]) ? (int) $dimensions[1] : null,
        ];
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? 'media';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'media';
    }

    private function humanizeTitle(string $value): string
    {
        $value = preg_replace('/[_-]+/', ' ', trim($value)) ?? '';
        $value = trim($value);

        return $value !== '' ? ucwords($value) : 'Untitled Media';
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