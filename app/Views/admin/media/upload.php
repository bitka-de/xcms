<?php
$quotaService = new \App\Services\StorageQuotaService(new \App\Repositories\MediaRepository());
$usedStorageBytes = $quotaService->getUsedStorageBytes();
$maxStorageBytes = $quotaService->getMaxTotalStorageBytes();
$remainingStorageBytes = max(0, $maxStorageBytes - $usedStorageBytes);
$usedPercent = $maxStorageBytes > 0 ? min(100, max(0, ($usedStorageBytes / $maxStorageBytes) * 100)) : 0;
$canUpload = $remainingStorageBytes > 0;

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $size = (float) $bytes;
    $unit = 0;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }

    $precision = $unit >= 3 ? 2 : 1;
    return number_format($size, $precision, '.', ',') . ' ' . $units[$unit];
};
?>
<section class="admin-page-header">
    <h2>Upload Media</h2>
    <p>Allowed types: <?= htmlspecialchars(implode(', ', $allowedExtensions), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>. Maximum size: <?= (int) $maxFileSizeMb ?> MB.</p>
</section>

<section class="admin-grid media-upload-layout" data-media-upload-root>
    <aside class="stat-card media-upload-summary" aria-label="Storage usage summary">
        <h3>Storage Usage</h3>
        <p class="media-upload-quota-line"><strong data-storage-used><?= htmlspecialchars($formatBytes($usedStorageBytes), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> used</p>
        <p class="media-upload-quota-line"><strong data-storage-remaining><?= htmlspecialchars($formatBytes($remainingStorageBytes), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> remaining</p>
        <p class="media-upload-quota-total">of <?= htmlspecialchars($formatBytes($maxStorageBytes), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> total</p>
        <div class="media-upload-quota-bar" role="progressbar" aria-label="Storage used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($usedPercent) ?>">
            <span class="media-upload-quota-fill" style="width: <?= number_format($usedPercent, 2, '.', '') ?>%"></span>
        </div>
        <p class="media-upload-quota-percent"><?= number_format($usedPercent, 1, '.', '') ?>% used</p>
        <?php if ($canUpload): ?>
            <p class="media-upload-ability media-upload-ability-ok" role="status">Uploads available</p>
        <?php else: ?>
            <p class="media-upload-ability media-upload-ability-full" role="alert">Storage full. Delete files before uploading.</p>
        <?php endif; ?>
    </aside>

    <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="stat-card admin-form media-upload-form" data-media-upload-form data-can-upload="<?= $canUpload ? '1' : '0' ?>">
        <div class="media-upload-grid">
            <label>
                File *
                <input type="file" name="file" data-upload-control data-upload-file accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.mp4,.webm,.mov,.mp3,.wav,.ogg,.m4a,.pdf" <?= $canUpload ? '' : 'disabled' ?> required>
                <?php if (!empty($errors['file'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Folder
                <select name="folder_id" data-upload-control <?= $canUpload ? '' : 'disabled' ?>>
                    <option value="">Root (no folder)</option>
                    <?php foreach ($folderTree as $folder): ?>
                        <?php $indent = str_repeat('-- ', (int) $folder['depth']); ?>
                        <option value="<?= (int) $folder['id'] ?>" <?= (string) ($form['folder_id'] ?? '') === (string) $folder['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['folder_id'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['folder_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Display Filename (optional)
                <input type="text" name="filename" data-upload-control <?= $canUpload ? '' : 'disabled' ?> value="<?= htmlspecialchars((string) ($form['filename'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="example-image.jpg">
                <?php if (!empty($errors['filename'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['filename'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Title
                <input type="text" name="title" data-upload-control <?= $canUpload ? '' : 'disabled' ?> value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label class="media-upload-span-2">
                Alt Text
                <input type="text" name="alt_text" data-upload-control <?= $canUpload ? '' : 'disabled' ?> value="<?= htmlspecialchars((string) ($form['alt_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            </label>
        </div>

        <section class="media-upload-progress-panel" aria-live="polite" data-upload-status-panel>
            <header class="media-upload-progress-head">
                <h3>Upload Progress</h3>
                <strong data-upload-percent>0%</strong>
            </header>
            <div class="media-upload-progress-track" role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-upload-progress>
                <span class="media-upload-progress-fill" data-upload-progress-fill></span>
            </div>
            <p class="media-upload-status-text" data-upload-status>Ready to upload.</p>
        </section>

        <p class="helper-text">Files are uploaded in 5 MB chunks. Physical file names are always generated server-side and uniquely stored under <code>/public/uploads/media/</code>.</p>

        <div class="form-actions">
            <button type="submit" data-upload-control data-upload-submit <?= $canUpload ? '' : 'disabled' ?>>Upload Media</button>
            <a href="/admin/media" data-upload-back-link>Back to media library</a>
        </div>

        <?php if (!$canUpload): ?>
            <p class="field-error">Storage quota reached. Please delete existing media to free up space.</p>
        <?php endif; ?>

        <noscript>
            <p class="media-upload-noscript">JavaScript is disabled. Standard upload is used without chunked progress.</p>
        </noscript>
    </form>
</section>

<script>
(function () {
    var CHUNK_SIZE = 5 * 1024 * 1024;
    var CHUNK_ENDPOINT = '/admin/media/upload/chunk';
    var form = document.querySelector('[data-media-upload-form]');

    if (!form) {
        return;
    }

    var fileInput = form.querySelector('[data-upload-file]');
    var controls = form.querySelectorAll('[data-upload-control]');
    var backLink = form.querySelector('[data-upload-back-link]');
    var statusPanel = form.querySelector('[data-upload-status-panel]');
    var progressEl = form.querySelector('[data-upload-progress]');
    var progressFill = form.querySelector('[data-upload-progress-fill]');
    var percentEl = form.querySelector('[data-upload-percent]');
    var statusEl = form.querySelector('[data-upload-status]');
    var canUpload = form.getAttribute('data-can-upload') === '1';

    function setControlsDisabled(disabled) {
        controls.forEach(function (el) {
            el.disabled = disabled;
        });

        if (backLink) {
            if (disabled) {
                backLink.setAttribute('aria-disabled', 'true');
                backLink.classList.add('is-disabled');
            } else {
                backLink.removeAttribute('aria-disabled');
                backLink.classList.remove('is-disabled');
            }
        }
    }

    function setProgress(percent) {
        var safePercent = Math.max(0, Math.min(100, percent));
        var integerPercent = Math.round(safePercent);

        if (progressFill) {
            progressFill.style.width = safePercent.toFixed(2) + '%';
        }

        if (progressEl) {
            progressEl.setAttribute('aria-valuenow', String(integerPercent));
        }

        if (percentEl) {
            percentEl.textContent = integerPercent + '%';
        }
    }

    function setStatus(message, state) {
        if (statusEl) {
            statusEl.textContent = message;
        }
        if (statusPanel) {
            statusPanel.classList.remove('is-uploading', 'is-success', 'is-error');
            if (state) {
                statusPanel.classList.add(state);
            }
        }
    }

    function makeUploadId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID().replace(/[^a-zA-Z0-9_-]/g, '');
        }

        return String(Date.now()) + '-' + String(Math.random()).slice(2, 12);
    }

    function parseJsonSafe(text) {
        try {
            return JSON.parse(text);
        } catch (_error) {
            return null;
        }
    }

    function wait(ms) {
        return new Promise(function (resolve) {
            window.setTimeout(resolve, ms);
        });
    }

    function uploadSingleChunk(payload, uploadedBeforeChunk, fileSize) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', CHUNK_ENDPOINT, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function (event) {
                if (!event.lengthComputable) {
                    return;
                }

                var uploadedOverall = uploadedBeforeChunk + event.loaded;
                setProgress((uploadedOverall / fileSize) * 100);
            };

            xhr.onload = function () {
                var json = parseJsonSafe(xhr.responseText || '');

                if (xhr.status < 200 || xhr.status >= 300 || !json || !json.success) {
                    var message = json && json.error ? json.error : 'Upload failed.';
                    reject(new Error(message));
                    return;
                }

                resolve(json);
            };

            xhr.onerror = function () {
                reject(new Error('Network error during upload.'));
            };

            xhr.send(payload);
        });
    }

    function buildChunkPayload(file, chunkBlob, uploadId, chunkIndex, totalChunks, metadata) {
        var payload = new FormData();
        payload.append('chunk', chunkBlob, file.name);
        payload.append('upload_id', uploadId);
        payload.append('chunk_index', String(chunkIndex));
        payload.append('total_chunks', String(totalChunks));

        // Required payload keys for the backend chunk contract.
        payload.append('file_name', file.name);
        payload.append('file_size', String(file.size));

        // Backward-compatible keys already used in controller/service.
        payload.append('original_name', file.name);
        payload.append('total_size', String(file.size));

        payload.append('folder_id', metadata.folder_id);
        payload.append('filename', metadata.filename);
        payload.append('title', metadata.title);
        payload.append('alt_text', metadata.alt_text);

        return payload;
    }

    form.addEventListener('submit', async function (event) {
        if (!canUpload) {
            event.preventDefault();
            setStatus('Storage quota reached. Delete media before uploading.', 'is-error');
            return;
        }

        if (!window.FormData || !window.XMLHttpRequest || !fileInput || !fileInput.files || fileInput.files.length === 0) {
            return;
        }

        event.preventDefault();

        var file = fileInput.files[0];
        if (!file || file.size <= 0) {
            setStatus('Please choose a valid file.', 'is-error');
            setProgress(0);
            return;
        }

        var totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        var uploadId = makeUploadId();
        var startedAt = Date.now();
        var formDataSnapshot = new FormData(form);
        var metadata = {
            folder_id: String(formDataSnapshot.get('folder_id') || ''),
            filename: String(formDataSnapshot.get('filename') || ''),
            title: String(formDataSnapshot.get('title') || ''),
            alt_text: String(formDataSnapshot.get('alt_text') || '')
        };
        var uploadedBytes = 0;

        setControlsDisabled(true);
        setProgress(0);
        setStatus('Starting chunked upload…', 'is-uploading');

        try {
            var finalResponse = null;

            for (var chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                var start = chunkIndex * CHUNK_SIZE;
                var end = Math.min(start + CHUNK_SIZE, file.size);
                var chunkBlob = file.slice(start, end);

                var payload = buildChunkPayload(file, chunkBlob, uploadId, chunkIndex, totalChunks, metadata);
                var json = await uploadSingleChunk(payload, uploadedBytes, file.size);

                uploadedBytes = end;
                setProgress((uploadedBytes / file.size) * 100);
                setStatus('Uploaded chunk ' + (chunkIndex + 1) + ' of ' + totalChunks + '…', 'is-uploading');

                finalResponse = json;
            }

            if (!finalResponse || !finalResponse.complete) {
                throw new Error('Upload did not complete correctly.');
            }

            setProgress(100);
            setStatus('Upload completed successfully.', 'is-success');

            var elapsedMs = Date.now() - startedAt;
            var minVisibleMs = 3000;
            if (elapsedMs < minVisibleMs) {
                await wait(minVisibleMs - elapsedMs);
            }

            if (finalResponse.redirect) {
                window.location.href = finalResponse.redirect;
                return;
            }

            setControlsDisabled(false);
        } catch (error) {
            setStatus(error && error.message ? error.message : 'Upload failed.', 'is-error');
            setControlsDisabled(false);
        }
    });
}());
</script>