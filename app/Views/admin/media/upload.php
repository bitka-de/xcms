<?php
$quotaService = new \App\Services\StorageQuotaService(new \App\Repositories\MediaRepository());
$usedStorageBytes = $quotaService->getUsedStorageBytes();
$maxStorageBytes = $quotaService->getMaxTotalStorageBytes();
$remainingStorageBytes = max(0, $maxStorageBytes - $usedStorageBytes);
$usedPercent = $maxStorageBytes > 0 ? min(100, max(0, ($usedStorageBytes / $maxStorageBytes) * 100)) : 0;

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
    </aside>

    <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="stat-card admin-form media-upload-form" data-media-upload-form>
        <div class="media-upload-grid">
            <label>
                File *
                <input type="file" name="file" data-upload-control data-upload-file accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.mp4,.webm,.mov,.mp3,.wav,.ogg,.m4a,.pdf" required>
                <?php if (!empty($errors['file'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Folder
                <select name="folder_id" data-upload-control>
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
                <input type="text" name="filename" data-upload-control value="<?= htmlspecialchars((string) ($form['filename'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="example-image.jpg">
                <?php if (!empty($errors['filename'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['filename'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Title
                <input type="text" name="title" data-upload-control value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label class="media-upload-span-2">
                Alt Text
                <input type="text" name="alt_text" data-upload-control value="<?= htmlspecialchars((string) ($form['alt_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
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
            <button type="submit" data-upload-control data-upload-submit>Upload Media</button>
            <a href="/admin/media" data-upload-back-link>Back to media library</a>
        </div>

        <noscript>
            <p class="media-upload-noscript">JavaScript is disabled. Standard upload is used without chunked progress.</p>
        </noscript>
    </form>
</section>

<script>
(function () {
    var CHUNK_SIZE = 5 * 1024 * 1024;
    var form = document.querySelector('[data-media-upload-form]');
    if (!form) {
        return;
    }

    var fileInput = form.querySelector('[data-upload-file]');
    var submitButton = form.querySelector('[data-upload-submit]');
    var controls = form.querySelectorAll('[data-upload-control]');
    var backLink = form.querySelector('[data-upload-back-link]');
    var statusPanel = form.querySelector('[data-upload-status-panel]');
    var progressEl = form.querySelector('[data-upload-progress]');
    var progressFill = form.querySelector('[data-upload-progress-fill]');
    var percentEl = form.querySelector('[data-upload-percent]');
    var statusEl = form.querySelector('[data-upload-status]');
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
        if (progressFill) {
            progressFill.style.width = safePercent.toFixed(2) + '%';
        }
        if (progressEl) {
            progressEl.setAttribute('aria-valuenow', String(Math.round(safePercent)));
        }
        if (percentEl) {
            percentEl.textContent = Math.round(safePercent) + '%';
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

    form.addEventListener('submit', async function (event) {
        if (!window.fetch || !window.FormData || !fileInput || !fileInput.files || fileInput.files.length === 0) {
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
        var formDataSnapshot = new FormData(form);
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

                var payload = new FormData();
                payload.append('chunk', chunkBlob, file.name);
                payload.append('upload_id', uploadId);
                payload.append('chunk_index', String(chunkIndex));
                payload.append('total_chunks', String(totalChunks));
                payload.append('original_name', file.name);
                payload.append('total_size', String(file.size));
                payload.append('folder_id', String(formDataSnapshot.get('folder_id') || ''));
                payload.append('filename', String(formDataSnapshot.get('filename') || ''));
                payload.append('title', String(formDataSnapshot.get('title') || ''));
                payload.append('alt_text', String(formDataSnapshot.get('alt_text') || ''));

                var response = await fetch('/admin/media/upload/chunk', {
                    method: 'POST',
                    body: payload,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                var json;
                try {
                    json = await response.json();
                } catch (_error) {
                    throw new Error('Server returned an invalid response.');
                }

                if (!response.ok || !json.success) {
                    throw new Error(json.error || 'Upload failed.');
                }

                uploadedBytes = end;
                setProgress((uploadedBytes / file.size) * 100);
                setStatus('Uploaded chunk ' + (chunkIndex + 1) + ' of ' + totalChunks + '…', 'is-uploading');

                finalResponse = json;
            }

            if (!finalResponse || !finalResponse.complete) {
                throw new Error('Upload did not complete correctly.');
            }

            setProgress(100);
            setStatus('Upload complete. Redirecting to media details…', 'is-success');

            if (finalResponse.redirect) {
                window.location.href = finalResponse.redirect;
                return;
            }

            setControlsDisabled(false);
            return;
        } catch (error) {
            setStatus(error && error.message ? error.message : 'Upload failed.', 'is-error');
            setControlsDisabled(false);
        }
    });
}());
</script>