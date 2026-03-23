<?php

namespace App\Services;

class ChunkUploadService
{
    private const CHUNK_SIZE_BYTES = 5242880; // 5 MB
    private const TEMP_ROOT_RELATIVE = '/storage/tmp/uploads';

    private MediaStorageService $mediaStorageService;

    public function __construct(?MediaStorageService $mediaStorageService = null)
    {
        $this->mediaStorageService = $mediaStorageService ?? new MediaStorageService();
    }

    public function getChunkSizeBytes(): int
    {
        return self::CHUNK_SIZE_BYTES;
    }

    public function handleChunkUpload(?array $chunkFile, array $payload): array
    {
        $metadata = $this->normalizeAndValidateMetadata($payload);
        $this->validateChunkFile($chunkFile);

        $uploadDir = $this->uploadDir((string) $metadata['upload_id']);
        $this->ensureDirectory($uploadDir);

        $manifest = $this->loadOrCreateManifest($uploadDir, $metadata);

        $expectedIndex = $this->countStoredChunks($uploadDir);
        if ((int) $metadata['chunk_index'] !== $expectedIndex) {
            throw new \RuntimeException('Chunks must be uploaded sequentially.');
        }

        $chunkPath = $this->chunkPath($uploadDir, (int) $metadata['chunk_index']);
        $isLast = (int) $metadata['chunk_index'] === ((int) $metadata['total_chunks'] - 1);
        $chunkBytes = (int) $chunkFile['size'];
        if (!$isLast && $chunkBytes !== self::CHUNK_SIZE_BYTES) {
            throw new \RuntimeException('Each non-final chunk must be exactly 5 MB.');
        }

        if (!move_uploaded_file((string) $chunkFile['tmp_name'], $chunkPath)) {
            throw new \RuntimeException('Failed to persist chunk on server.');
        }

        if (!$isLast) {
            return [
                'complete' => false,
                'upload_id' => $metadata['upload_id'],
                'received_chunk' => (int) $metadata['chunk_index'],
                'next_chunk' => (int) $metadata['chunk_index'] + 1,
                'total_chunks' => (int) $metadata['total_chunks'],
            ];
        }

        $assembledPath = $this->assembleChunks($uploadDir, (int) $metadata['total_chunks']);
        $assembledSize = (int) filesize($assembledPath);

        if ($manifest['total_size'] !== null && $assembledSize !== (int) $manifest['total_size']) {
            @unlink($assembledPath);
            throw new \RuntimeException('Assembled file size does not match expected size.');
        }

        $upload = $this->storeAsMediaFile($assembledPath, (string) $manifest['original_name']);
        $this->deleteDirectory($uploadDir);

        return [
            'complete' => true,
            'upload_id' => $metadata['upload_id'],
            'upload' => $upload,
        ];
    }

    private function normalizeAndValidateMetadata(array $payload): array
    {
        $uploadId = trim((string) ($payload['upload_id'] ?? ''));
        $chunkIndexRaw = trim((string) ($payload['chunk_index'] ?? ''));
        $totalChunksRaw = trim((string) ($payload['total_chunks'] ?? ''));
        $originalName = trim((string) ($payload['original_name'] ?? ''));
        $totalSizeRaw = trim((string) ($payload['total_size'] ?? ''));

        if (!preg_match('/^[a-zA-Z0-9_-]{8,128}$/', $uploadId)) {
            throw new \RuntimeException('Invalid upload id.');
        }

        if ($chunkIndexRaw === '' || !ctype_digit($chunkIndexRaw)) {
            throw new \RuntimeException('Invalid chunk index.');
        }

        if ($totalChunksRaw === '' || !ctype_digit($totalChunksRaw)) {
            throw new \RuntimeException('Invalid total chunks.');
        }

        $chunkIndex = (int) $chunkIndexRaw;
        $totalChunks = (int) $totalChunksRaw;

        if ($chunkIndex < 0) {
            throw new \RuntimeException('Chunk index must be >= 0.');
        }

        if ($totalChunks < 1) {
            throw new \RuntimeException('Total chunks must be >= 1.');
        }

        if ($chunkIndex >= $totalChunks) {
            throw new \RuntimeException('Chunk index out of range.');
        }

        if ($originalName === '') {
            throw new \RuntimeException('Original filename is required.');
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            throw new \RuntimeException('File extension is required.');
        }

        if (!in_array($extension, $this->mediaStorageService->allowedExtensions(), true)) {
            throw new \RuntimeException('File extension is not allowed.');
        }

        $totalSize = null;
        if ($totalSizeRaw !== '') {
            if (!ctype_digit($totalSizeRaw)) {
                throw new \RuntimeException('Invalid total size.');
            }

            $parsed = (int) $totalSizeRaw;
            if ($parsed <= 0) {
                throw new \RuntimeException('Total size must be greater than zero.');
            }

            $totalSize = $parsed;
        }

        return [
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'original_name' => $originalName,
            'extension' => $extension,
            'total_size' => $totalSize,
        ];
    }

    private function validateChunkFile(?array $chunkFile): void
    {
        if ($chunkFile === null || ($chunkFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('Chunk payload is required.');
        }

        if (($chunkFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Chunk upload failed.');
        }

        if (!isset($chunkFile['tmp_name'], $chunkFile['size']) || !is_uploaded_file((string) $chunkFile['tmp_name'])) {
            throw new \RuntimeException('Invalid uploaded chunk.');
        }

        $size = (int) $chunkFile['size'];
        if ($size <= 0) {
            throw new \RuntimeException('Chunk cannot be empty.');
        }

        if ($size > self::CHUNK_SIZE_BYTES) {
            throw new \RuntimeException('Chunk exceeds 5 MB limit.');
        }
    }

    private function loadOrCreateManifest(string $uploadDir, array $metadata): array
    {
        $manifestPath = $uploadDir . '/manifest.json';

        if (is_file($manifestPath)) {
            $raw = (string) file_get_contents($manifestPath);
            $manifest = json_decode($raw, true);

            if (!is_array($manifest)) {
                throw new \RuntimeException('Upload metadata is corrupted.');
            }

            if ((string) ($manifest['original_name'] ?? '') !== (string) $metadata['original_name']) {
                throw new \RuntimeException('Original filename does not match initial chunk.');
            }

            if ((int) ($manifest['total_chunks'] ?? 0) !== (int) $metadata['total_chunks']) {
                throw new \RuntimeException('Total chunk count does not match initial chunk.');
            }

            return [
                'original_name' => (string) $manifest['original_name'],
                'total_chunks' => (int) $manifest['total_chunks'],
                'total_size' => isset($manifest['total_size']) && $manifest['total_size'] !== null ? (int) $manifest['total_size'] : null,
            ];
        }

        $manifest = [
            'original_name' => (string) $metadata['original_name'],
            'total_chunks' => (int) $metadata['total_chunks'],
            'total_size' => $metadata['total_size'],
            'created_at' => date('c'),
        ];

        file_put_contents($manifestPath, json_encode($manifest));

        return [
            'original_name' => (string) $manifest['original_name'],
            'total_chunks' => (int) $manifest['total_chunks'],
            'total_size' => $manifest['total_size'] !== null ? (int) $manifest['total_size'] : null,
        ];
    }

    private function assembleChunks(string $uploadDir, int $totalChunks): string
    {
        $assembledPath = $uploadDir . '/assembled.bin';
        $out = fopen($assembledPath, 'wb');

        if ($out === false) {
            throw new \RuntimeException('Cannot create assembled file.');
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $this->chunkPath($uploadDir, $i);
                if (!is_file($chunkPath)) {
                    throw new \RuntimeException('Missing chunk during assembly.');
                }

                $in = fopen($chunkPath, 'rb');
                if ($in === false) {
                    throw new \RuntimeException('Cannot read chunk during assembly.');
                }

                while (!feof($in)) {
                    $buffer = fread($in, 1048576);
                    if ($buffer === false) {
                        fclose($in);
                        throw new \RuntimeException('Error while reading chunk data.');
                    }

                    if ($buffer === '') {
                        break;
                    }

                    if (fwrite($out, $buffer) === false) {
                        fclose($in);
                        throw new \RuntimeException('Error while writing assembled file.');
                    }
                }

                fclose($in);
            }
        } finally {
            fclose($out);
        }

        return $assembledPath;
    }

    private function storeAsMediaFile(string $assembledPath, string $originalName): array
    {
        $uploadDir = BASE_PATH . '/public/uploads/media';
        $this->ensureDirectory($uploadDir);

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = (string) pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : 'media';

        $storedName = $this->slugify($baseName) . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $relativePath = 'uploads/media/' . $storedName;
        $absolutePath = BASE_PATH . '/public/' . $relativePath;

        if (!rename($assembledPath, $absolutePath)) {
            if (!copy($assembledPath, $absolutePath)) {
                throw new \RuntimeException('Failed to store assembled file.');
            }
            @unlink($assembledPath);
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

    private function countStoredChunks(string $uploadDir): int
    {
        $paths = glob($uploadDir . '/chunk-*.part');

        return is_array($paths) ? count($paths) : 0;
    }

    private function chunkPath(string $uploadDir, int $chunkIndex): string
    {
        return $uploadDir . '/chunk-' . str_pad((string) $chunkIndex, 6, '0', STR_PAD_LEFT) . '.part';
    }

    private function uploadDir(string $uploadId): string
    {
        return $this->tempRootPath() . '/' . $uploadId;
    }

    private function tempRootPath(): string
    {
        return rtrim(BASE_PATH . self::TEMP_ROOT_RELATIVE, '/');
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Failed to create directory for chunk upload.');
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'file';
    }

    private function normalizeDisplayFilename(string $value, string $extension): string
    {
        $baseName = pathinfo(trim($value), PATHINFO_FILENAME);
        $baseName = $this->slugify($baseName !== '' ? $baseName : 'file');

        return $baseName . '.' . strtolower($extension);
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
}
