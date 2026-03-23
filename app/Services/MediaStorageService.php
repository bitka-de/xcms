<?php

namespace App\Services;

use App\Models\Media;

class MediaStorageService
{
    private const MAX_FILE_SIZE = 104857600;

    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'svg',
        'mp4',
        'webm',
        'mov',
        'mp3',
        'wav',
        'ogg',
        'm4a',
        'pdf',
    ];

    private const BLOCKED_EXTENSIONS = [
        'php',
        'phtml',
        'phar',
        'exe',
        'js',
        'sh',
        'bat',
        'cmd',
        'com',
        'msi',
        'dll',
    ];

    private const ALLOWED_MIME_MAP = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'svg' => ['image/svg+xml'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mov' => ['video/quicktime'],
        'mp3' => ['audio/mpeg', 'audio/mp3', 'audio/x-mp3', 'audio/x-mpeg'],
        'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave'],
        'ogg' => ['audio/ogg', 'application/ogg'],
        'm4a' => ['audio/mp4', 'audio/x-m4a', 'audio/m4a'],
        'pdf' => ['application/pdf'],
    ];

    public function allowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    public function maxFileSizeMb(): int
    {
        return (int) round(self::MAX_FILE_SIZE / 1024 / 1024);
    }

    public function validateUploadedFile(?array $file): array
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

        if (!isset($file['tmp_name'], $file['name'], $file['size']) || !is_uploaded_file((string) $file['tmp_name'])) {
            $errors['file'] = 'Invalid upload.';
            return $errors;
        }

        $size = (int) $file['size'];
        if ($size <= 0) {
            $errors['file'] = 'Uploaded file is empty.';
            return $errors;
        }

        if ($size > self::MAX_FILE_SIZE) {
            $errors['file'] = 'Maximum file size is ' . $this->maxFileSizeMb() . ' MB.';
            return $errors;
        }

        $originalName = (string) $file['name'];
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            $errors['file'] = 'File extension is blocked.';
            return $errors;
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $errors['file'] = 'Allowed file types: ' . implode(', ', self::ALLOWED_EXTENSIONS) . '.';
            return $errors;
        }

        $mimeType = $this->detectMime((string) $file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_MIME_MAP[$extension] ?? [], true)) {
            $errors['file'] = 'Detected MIME type does not match the selected extension.';
            return $errors;
        }

        if ($extension === 'svg' && !$this->isSvgSafe((string) $file['tmp_name'])) {
            $errors['file'] = 'SVG contains unsafe markup.';
        }

        return $errors;
    }

    public function saveUploadedFile(array $file): array
    {
        $this->ensureUploadDirectory();

        $originalName = (string) $file['name'];
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = (string) pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : 'media';

        $storedName = $this->generateStoredName($baseName, $extension);
        $relativePath = 'uploads/media/' . $storedName;
        $absolutePath = BASE_PATH . '/public/' . $relativePath;

        if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
            throw new \RuntimeException('Failed to store uploaded file.');
        }

        $mimeType = $this->detectMime($absolutePath);
        $dimensions = $this->extractDimensions($absolutePath, $mimeType);

        return [
            'original_name' => $originalName,
            'filename' => $this->normalizeDisplayFilename($baseName, $extension),
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'file_size' => (int) filesize($absolutePath),
            'path' => '/' . $relativePath,
            'type' => $this->resolveType($mimeType, $extension),
            'default_title' => $this->humanize($baseName),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ];
    }

    public function renamePhysicalFile(Media $media, string $requestedFilename): array
    {
        $this->ensureUploadDirectory();

        $extension = strtolower($media->extension);
        $baseName = pathinfo($requestedFilename, PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : pathinfo($media->filename, PATHINFO_FILENAME);

        $newStoredName = $this->generateStoredName($baseName, $extension);
        $oldAbsolutePath = $this->absolutePathFromMedia($media);
        $newRelativePath = 'uploads/media/' . $newStoredName;
        $newAbsolutePath = BASE_PATH . '/public/' . $newRelativePath;

        if (!is_file($oldAbsolutePath)) {
            throw new \RuntimeException('Source file does not exist for rename.');
        }

        if (!rename($oldAbsolutePath, $newAbsolutePath)) {
            throw new \RuntimeException('Unable to rename physical file.');
        }

        return [
            'stored_name' => $newStoredName,
            'path' => '/' . $newRelativePath,
        ];
    }

    public function deletePhysicalFile(Media $media): void
    {
        $absolutePath = $this->absolutePathFromMedia($media);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    public function normalizeDisplayFilename(string $value, string $extension): string
    {
        $baseName = pathinfo(trim($value), PATHINFO_FILENAME);
        $baseName = $this->slugify($baseName !== '' ? $baseName : 'file');

        return $baseName . '.' . strtolower($extension);
    }

    public function isValidDisplayFilename(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9._ -]+$/', $value);
    }

    public function previewStoredName(string $requestedFilename, string $extension): string
    {
        $baseName = pathinfo($requestedFilename, PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : 'file';

        return $this->slugify($baseName) . '.' . strtolower($extension);
    }

    private function ensureUploadDirectory(): void
    {
        $directory = BASE_PATH . '/public/uploads/media';

        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create media upload directory.');
        }
    }

    private function generateStoredName(string $baseName, string $extension): string
    {
        $safe = $this->slugify($baseName);

        return $safe . '-' . bin2hex(random_bytes(8)) . '.' . strtolower($extension);
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'file';
    }

    private function humanize(string $value): string
    {
        $value = preg_replace('/[_-]+/', ' ', trim($value)) ?? '';
        $value = trim($value);

        return $value !== '' ? ucwords($value) : 'Untitled';
    }

    private function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo !== false ? (string) finfo_file($finfo, $path) : 'application/octet-stream';

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        return $mimeType;
    }

    private function resolveType(string $mimeType, string $extension): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        if (in_array(strtolower($extension), ['mp3', 'wav', 'ogg', 'm4a'], true)) {
            return 'audio';
        }

        return 'document';
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

    private function isSvgSafe(string $path): bool
    {
        $contents = (string) @file_get_contents($path);
        if ($contents === '') {
            return false;
        }

        $normalized = strtolower($contents);

        if (str_contains($normalized, '<script') || str_contains($normalized, 'onload=') || str_contains($normalized, 'javascript:')) {
            return false;
        }

        return true;
    }

    private function absolutePathFromMedia(Media $media): string
    {
        $path = ltrim($media->path, '/');
        if (!str_starts_with($path, 'uploads/media/')) {
            $path = 'uploads/media/' . $media->stored_name;
        }

        return BASE_PATH . '/public/' . $path;
    }
}