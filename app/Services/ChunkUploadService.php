<?php

namespace App\Services;

class ChunkUploadException extends \RuntimeException
{
    private string $errorCodeName;
    private string $stage;
    private int $httpStatus;
    private bool $retryable;
    private array $context;

    public function __construct(
        string $message,
        string $errorCodeName,
        string $stage,
        int $httpStatus = 422,
        bool $retryable = false,
        array $context = []
    ) {
        parent::__construct($message);
        $this->errorCodeName = $errorCodeName;
        $this->stage = $stage;
        $this->httpStatus = $httpStatus;
        $this->retryable = $retryable;
        $this->context = $context;
    }

    public function getErrorCodeName(): string
    {
        return $this->errorCodeName;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

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
            $this->raise(
                'chunk_out_of_sequence',
                'Chunks must be uploaded sequentially.',
                'chunk',
                [
                    'upload_id' => $metadata['upload_id'],
                    'expected_chunk' => $expectedIndex,
                    'received_chunk' => (int) $metadata['chunk_index'],
                    'total_chunks' => (int) $metadata['total_chunks'],
                ],
                409,
                true
            );
        }

        $chunkPath = $this->chunkPath($uploadDir, (int) $metadata['chunk_index']);
        $isLast = (int) $metadata['chunk_index'] === ((int) $metadata['total_chunks'] - 1);
        $chunkBytes = (int) $chunkFile['size'];
        if (!$isLast && $chunkBytes !== self::CHUNK_SIZE_BYTES) {
            $this->raise(
                'invalid_chunk_size',
                'Each non-final chunk must be exactly 5 MB.',
                'chunk',
                [
                    'upload_id' => $metadata['upload_id'],
                    'chunk_index' => (int) $metadata['chunk_index'],
                    'received_size' => $chunkBytes,
                    'expected_size' => self::CHUNK_SIZE_BYTES,
                ]
            );
        }

        if (!move_uploaded_file((string) $chunkFile['tmp_name'], $chunkPath)) {
            $this->raise(
                'chunk_store_failed',
                'Failed to persist chunk on server.',
                'chunk',
                [
                    'upload_id' => $metadata['upload_id'],
                    'chunk_index' => (int) $metadata['chunk_index'],
                ],
                500,
                true
            );
        }

        if (!$isLast) {
            // Intentionally keep chunk files for interrupted uploads.
            // Chunks are isolated per upload_id directory and can be cleaned later by a maintenance job.
            return [
                'complete' => false,
                'upload_id' => $metadata['upload_id'],
                'received_chunk' => (int) $metadata['chunk_index'],
                'next_chunk' => (int) $metadata['chunk_index'] + 1,
                'total_chunks' => (int) $metadata['total_chunks'],
            ];
        }

        try {
            $assembledPath = $this->assembleChunks($uploadDir, (int) $metadata['total_chunks'], (string) $metadata['upload_id']);
            $assembledSize = (int) filesize($assembledPath);

            if ($manifest['total_size'] !== null && $assembledSize !== (int) $manifest['total_size']) {
                @unlink($assembledPath);
                $this->raise(
                    'assembly_size_mismatch',
                    'Assembled file size does not match expected size.',
                    'assembly',
                    [
                        'upload_id' => $metadata['upload_id'],
                        'expected_size' => (int) $manifest['total_size'],
                        'assembled_size' => $assembledSize,
                    ]
                );
            }

            $upload = $this->storeAsMediaFile($assembledPath, (string) $manifest['original_name']);
            $this->deleteDirectory($uploadDir);
        } catch (ChunkUploadException $exception) {
            $cleanupSuccess = $this->cleanupAfterFinalizationFailure($uploadDir);
            $context = $exception->getContext();
            $context['cleanup_attempted'] = true;
            $context['cleanup_success'] = $cleanupSuccess;

            throw new ChunkUploadException(
                $exception->getMessage(),
                $exception->getErrorCodeName(),
                $exception->getStage(),
                $exception->getHttpStatus(),
                $exception->isRetryable(),
                $context
            );
        } catch (\Throwable $exception) {
            $cleanupSuccess = $this->cleanupAfterFinalizationFailure($uploadDir);

            throw new ChunkUploadException(
                'Final assembly failed. Please retry the upload.',
                'finalization_failed',
                'assembly',
                500,
                false,
                [
                    'upload_id' => $metadata['upload_id'],
                    'cleanup_attempted' => true,
                    'cleanup_success' => $cleanupSuccess,
                ]
            );
        }

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
        $originalName = trim((string) (($payload['original_name'] ?? '') !== '' ? $payload['original_name'] : ($payload['file_name'] ?? '')));
        $totalSizeRaw = trim((string) (($payload['total_size'] ?? '') !== '' ? $payload['total_size'] : ($payload['file_size'] ?? '')));

        if (!preg_match('/^[a-zA-Z0-9_-]{8,128}$/', $uploadId)) {
            $this->raise('invalid_upload_id', 'Invalid upload id.', 'metadata');
        }

        if ($chunkIndexRaw === '' || !ctype_digit($chunkIndexRaw)) {
            $this->raise('invalid_chunk_index', 'Invalid chunk index.', 'metadata', ['upload_id' => $uploadId]);
        }

        if ($totalChunksRaw === '' || !ctype_digit($totalChunksRaw)) {
            $this->raise('invalid_total_chunks', 'Invalid total chunks.', 'metadata', ['upload_id' => $uploadId]);
        }

        $chunkIndex = (int) $chunkIndexRaw;
        $totalChunks = (int) $totalChunksRaw;

        if ($chunkIndex < 0) {
            $this->raise('invalid_chunk_index', 'Chunk index must be >= 0.', 'metadata', ['upload_id' => $uploadId]);
        }

        if ($totalChunks < 1) {
            $this->raise('invalid_total_chunks', 'Total chunks must be >= 1.', 'metadata', ['upload_id' => $uploadId]);
        }

        if ($chunkIndex >= $totalChunks) {
            $this->raise('chunk_index_out_of_range', 'Chunk index out of range.', 'metadata', ['upload_id' => $uploadId]);
        }

        if ($originalName === '') {
            $this->raise('missing_file_name', 'Original filename is required.', 'metadata', ['upload_id' => $uploadId]);
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            $this->raise('missing_extension', 'File extension is required.', 'metadata', ['upload_id' => $uploadId]);
        }

        if (!in_array($extension, $this->mediaStorageService->allowedExtensions(), true)) {
            $this->raise('extension_not_allowed', 'File extension is not allowed.', 'metadata', ['upload_id' => $uploadId]);
        }

        $totalSize = null;
        if ($totalSizeRaw !== '') {
            if (!ctype_digit($totalSizeRaw)) {
                $this->raise('invalid_file_size', 'Invalid total size.', 'metadata', ['upload_id' => $uploadId]);
            }

            $parsed = (int) $totalSizeRaw;
            if ($parsed <= 0) {
                $this->raise('invalid_file_size', 'Total size must be greater than zero.', 'metadata', ['upload_id' => $uploadId]);
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
            $this->raise('missing_chunk_payload', 'Chunk payload is required.', 'chunk');
        }

        if (($chunkFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->raise('chunk_upload_failed', 'Chunk upload failed.', 'chunk', [
                'php_upload_error' => (int) ($chunkFile['error'] ?? UPLOAD_ERR_OK),
            ]);
        }

        if (!isset($chunkFile['tmp_name'], $chunkFile['size']) || !is_uploaded_file((string) $chunkFile['tmp_name'])) {
            $this->raise('invalid_chunk_payload', 'Invalid uploaded chunk.', 'chunk');
        }

        $size = (int) $chunkFile['size'];
        if ($size <= 0) {
            $this->raise('empty_chunk', 'Chunk cannot be empty.', 'chunk');
        }

        if ($size > self::CHUNK_SIZE_BYTES) {
            $this->raise('chunk_too_large', 'Chunk exceeds 5 MB limit.', 'chunk', [
                'chunk_size' => $size,
                'max_chunk_size' => self::CHUNK_SIZE_BYTES,
            ]);
        }
    }

    private function loadOrCreateManifest(string $uploadDir, array $metadata): array
    {
        $manifestPath = $uploadDir . '/manifest.json';

        if (is_file($manifestPath)) {
            $raw = (string) file_get_contents($manifestPath);
            $manifest = json_decode($raw, true);

            if (!is_array($manifest)) {
                $this->raise('manifest_corrupted', 'Upload metadata is corrupted.', 'metadata');
            }

            if ((string) ($manifest['original_name'] ?? '') !== (string) $metadata['original_name']) {
                $this->raise('manifest_mismatch_name', 'Original filename does not match initial chunk.', 'metadata', [
                    'upload_id' => $metadata['upload_id'],
                ]);
            }

            if ((int) ($manifest['total_chunks'] ?? 0) !== (int) $metadata['total_chunks']) {
                $this->raise('manifest_mismatch_total_chunks', 'Total chunk count does not match initial chunk.', 'metadata', [
                    'upload_id' => $metadata['upload_id'],
                ]);
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

    private function assembleChunks(string $uploadDir, int $totalChunks, string $uploadId): string
    {
        $assembledPath = $uploadDir . '/assembled.bin';
        $out = fopen($assembledPath, 'wb');

        if ($out === false) {
            $this->raise('assembly_create_failed', 'Cannot create assembled file.', 'assembly', [
                'upload_id' => $uploadId,
            ], 500);
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $this->chunkPath($uploadDir, $i);
                if (!is_file($chunkPath)) {
                    $this->raise('assembly_missing_chunk', 'Missing chunk during assembly.', 'assembly', [
                        'upload_id' => $uploadId,
                        'missing_chunk' => $i,
                    ]);
                }

                $in = fopen($chunkPath, 'rb');
                if ($in === false) {
                    $this->raise('assembly_chunk_read_failed', 'Cannot read chunk during assembly.', 'assembly', [
                        'upload_id' => $uploadId,
                        'chunk_index' => $i,
                    ], 500);
                }

                while (!feof($in)) {
                    $buffer = fread($in, 1048576);
                    if ($buffer === false) {
                        fclose($in);
                        $this->raise('assembly_chunk_read_failed', 'Error while reading chunk data.', 'assembly', [
                            'upload_id' => $uploadId,
                            'chunk_index' => $i,
                        ], 500);
                    }

                    if ($buffer === '') {
                        break;
                    }

                    if (fwrite($out, $buffer) === false) {
                        fclose($in);
                        $this->raise('assembly_write_failed', 'Error while writing assembled file.', 'assembly', [
                            'upload_id' => $uploadId,
                            'chunk_index' => $i,
                        ], 500);
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
                @unlink($absolutePath);
                $this->raise('store_final_file_failed', 'Failed to store assembled file.', 'assembly', [], 500);
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

    private function cleanupAfterFinalizationFailure(string $uploadDir): bool
    {
        if (!is_dir($uploadDir)) {
            return true;
        }

        $this->deleteDirectory($uploadDir);

        return !is_dir($uploadDir);
    }

    private function raise(
        string $errorCode,
        string $message,
        string $stage,
        array $context = [],
        int $httpStatus = 422,
        bool $retryable = false
    ): void {
        throw new ChunkUploadException($message, $errorCode, $stage, $httpStatus, $retryable, $context);
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
