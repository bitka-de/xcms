<?php
$quotaService = new \App\Services\StorageQuotaService(new \App\Repositories\MediaRepository());
$usage = $quotaService->getUsageSummary();
$usedStorageBytes = (int) $usage['used_bytes'];
$maxStorageBytes = (int) $usage['total_bytes'];
$remainingStorageBytes = (int) $usage['remaining_bytes'];
$usedPercent = (float) $usage['used_percent'];
$canUpload = $remainingStorageBytes > 0;
?>
<section class="admin-page-header">
    <h2>Upload Media</h2>
    <p>Allowed types: <?= htmlspecialchars(implode(', ', $allowedExtensions), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>. Maximum size: <?= (int) $maxFileSizeMb ?> MB.</p>
</section>

<section class="admin-grid media-upload-layout" data-media-upload-root>
    <aside class="stat-card media-upload-summary" aria-label="Storage usage summary">
        <h3>Storage Usage</h3>
        <p class="media-upload-quota-line"><strong data-storage-used><?= htmlspecialchars((string) $usage['used_formatted'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> used</p>
        <p class="media-upload-quota-line"><strong data-storage-remaining><?= htmlspecialchars((string) $usage['remaining_formatted'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> remaining</p>
        <p class="media-upload-quota-total">of <?= htmlspecialchars((string) $usage['total_formatted'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> total</p>
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
        <section class="media-upload-dropzone<?= $canUpload ? '' : ' is-disabled' ?>" data-upload-dropzone tabindex="0" role="button" aria-label="Drag and drop files here or click to select files">
            <span class="media-upload-dropzone-icon" aria-hidden="true">⇪</span>
            <p class="media-upload-dropzone-title">Drop files to upload</p>
            <p class="media-upload-dropzone-subtitle">or click to choose one or more files</p>
            <p class="media-upload-dropzone-meta" data-upload-selection>No files selected</p>
            <input type="file" name="file" class="media-upload-file-input" data-upload-control data-upload-file accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.mp4,.webm,.mov,.mp3,.wav,.ogg,.m4a,.pdf" multiple <?= $canUpload ? '' : 'disabled' ?>>
        </section>

        <div class="media-upload-grid">
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
        </div>

        <?php if (!empty($errors['file'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>

        <section class="media-upload-queue-panel" aria-live="polite">
            <header class="media-upload-queue-head">
                <h3>File Queue</h3>
                <span data-upload-queue-count>0 files · 0 B</span>
            </header>
            <ul class="media-upload-queue-list" data-upload-queue-list></ul>
            <p class="media-upload-queue-empty" data-upload-queue-empty>
                <strong>No files queued yet.</strong>
                <span>Drop files above or click the upload zone to start.</span>
            </p>
        </section>

        <section class="media-upload-progress-panel" aria-live="polite" data-upload-status-panel>
            <header class="media-upload-progress-head">
                <h3>Overall Progress</h3>
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
    var dropzone = form.querySelector('[data-upload-dropzone]');
    var selectionText = form.querySelector('[data-upload-selection]');
    var queueList = form.querySelector('[data-upload-queue-list]');
    var queueCount = form.querySelector('[data-upload-queue-count]');
    var queueEmpty = form.querySelector('[data-upload-queue-empty]');
    var controls = form.querySelectorAll('[data-upload-control]');
    var backLink = form.querySelector('[data-upload-back-link]');
    var statusPanel = form.querySelector('[data-upload-status-panel]');
    var progressEl = form.querySelector('[data-upload-progress]');
    var progressFill = form.querySelector('[data-upload-progress-fill]');
    var percentEl = form.querySelector('[data-upload-percent]');
    var statusEl = form.querySelector('[data-upload-status]');
    var canUpload = form.getAttribute('data-can-upload') === '1';
    var queue = [];
    var isUploading = false;
    var dragDepth = 0;
    var storageRemainingBytes = <?= (int) $remainingStorageBytes ?>;

    function setControlsDisabled(disabled) {
        controls.forEach(function (el) {
            el.disabled = disabled;
        });

        if (dropzone) {
            dropzone.classList.toggle('is-disabled', disabled || !canUpload);
        }

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

    function formatBytes(bytes) {
        if (!isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }

        var units = ['B', 'KB', 'MB', 'GB', 'TB'];
        var size = bytes;
        var unitIndex = 0;
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        var precision = (unitIndex <= 2) ? 1 : 2;
        if (unitIndex === 0) {
            precision = 0;
        }

        return size.toFixed(precision) + ' ' + units[unitIndex];
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

    function buildApiErrorMessage(json, fallback) {
        if (!json || typeof json !== 'object') {
            return fallback;
        }

        var parts = [];
        var base = typeof json.error === 'string' && json.error !== '' ? json.error : fallback;
        parts.push(base);

        if (typeof json.stage === 'string' && json.stage !== '') {
            parts.push('Stage: ' + json.stage);
        }

        if (typeof json.error_code === 'string' && json.error_code !== '') {
            parts.push('Code: ' + json.error_code);
        }

        if (json.context && typeof json.context === 'object') {
            if (typeof json.context.expected_chunk === 'number' && typeof json.context.received_chunk === 'number') {
                parts.push('Expected chunk ' + json.context.expected_chunk + ', got ' + json.context.received_chunk);
            }

            if (typeof json.context.remaining_storage === 'number') {
                parts.push('Remaining storage: ' + formatBytes(json.context.remaining_storage));
            }

            if (typeof json.context.exception_type === 'string' && json.context.exception_type !== '') {
                parts.push('Error type: ' + json.context.exception_type);
            }

            if (typeof json.context.errors === 'object' && json.context.errors !== null && Object.keys(json.context.errors).length > 0) {
                var errorList = [];
                for (var key in json.context.errors) {
                    if (json.context.errors.hasOwnProperty(key)) {
                        errorList.push(json.context.errors[key]);
                    }
                }
                if (errorList.length > 0) {
                    parts.push('Details: ' + errorList.join('; '));
                }
            }
        }

        return parts.join(' | ');
    }

    function wait(ms) {
        return new Promise(function (resolve) {
            window.setTimeout(resolve, ms);
        });
    }

    function makeFileId(file) {
        return [file.name, file.size, file.lastModified].join('::');
    }

    function updateSelectionMeta() {
        if (!selectionText) {
            return;
        }

        if (queue.length === 0) {
            selectionText.textContent = 'No files selected';
            return;
        }

        var totalBytes = 0;
        queue.forEach(function (entry) {
            totalBytes += entry.file.size;
        });
        selectionText.textContent = queue.length + ' file' + (queue.length === 1 ? '' : 's') + ' selected (' + formatBytes(totalBytes) + ')';
    }

    function queueStateLabel(state) {
        if (state === 'uploading') {
            return 'Uploading';
        }
        if (state === 'success') {
            return 'Success';
        }
        if (state === 'error') {
            return 'Error';
        }
        if (state === 'skipped') {
            return 'Skipped';
        }
        return 'Queued';
    }

    function queueStateClass(state) {
        if (state === 'uploading') {
            return 'is-uploading';
        }
        if (state === 'success') {
            return 'is-success';
        }
        if (state === 'error') {
            return 'is-error';
        }
        if (state === 'skipped') {
            return 'is-skipped';
        }
        return 'is-queued';
    }

    function renderQueue() {
        if (!queueList || !queueCount || !queueEmpty) {
            return;
        }

        var totalBytes = 0;
        queue.forEach(function (entry) {
            totalBytes += entry.file.size;
        });

        queueCount.textContent = queue.length + ' file' + (queue.length === 1 ? '' : 's') + ' · ' + formatBytes(totalBytes);
        queueList.innerHTML = '';
        queueEmpty.hidden = queue.length > 0;

        queue.forEach(function (entry) {
            var item = document.createElement('li');
            item.className = 'media-upload-queue-item ' + queueStateClass(entry.state);

            var row = document.createElement('div');
            row.className = 'media-upload-queue-row';

            var left = document.createElement('div');
            left.className = 'media-upload-queue-main';

            var name = document.createElement('p');
            name.className = 'media-upload-queue-name';
            name.textContent = entry.file.name;

            var state = document.createElement('span');
            state.className = 'media-upload-queue-state ' + queueStateClass(entry.state);
            state.textContent = queueStateLabel(entry.state);

            left.appendChild(name);
            left.appendChild(state);

            var size = document.createElement('p');
            size.className = 'media-upload-queue-size';
            size.textContent = formatBytes(entry.file.size) + ' · ' + Math.round(entry.percent) + '%';

            row.appendChild(left);
            row.appendChild(size);

            var progress = document.createElement('div');
            progress.className = 'media-upload-queue-progress';
            var fill = document.createElement('span');
            fill.style.width = Math.max(0, Math.min(100, entry.percent)).toFixed(2) + '%';
            progress.appendChild(fill);

            var status = document.createElement('p');
            status.className = 'media-upload-queue-status';
            status.textContent = entry.statusText;

            item.appendChild(row);
            item.appendChild(progress);
            item.appendChild(status);
            queueList.appendChild(item);
        });

        updateSelectionMeta();
    }

    function addFiles(fileList) {
        if (!fileList || fileList.length === 0) {
            return;
        }

        Array.prototype.forEach.call(fileList, function (file) {
            var id = makeFileId(file);
            var exists = queue.some(function (entry) {
                return entry.id === id;
            });
            if (exists) {
                return;
            }

            queue.push({
                id: id,
                file: file,
                percent: 0,
                state: 'queued',
                statusText: 'Waiting to upload',
                mediaId: null,
                editUrl: null
            });
        });

        renderQueue();
    }

    function setEntryState(entry, state, statusText, percent) {
        entry.state = state;
        entry.statusText = statusText;
        if (typeof percent === 'number') {
            entry.percent = percent;
        }
        renderQueue();
    }

    function setOverallProgressFromQueue() {
        if (queue.length === 0) {
            setProgress(0);
            return;
        }

        var sum = 0;
        queue.forEach(function (entry) {
            sum += entry.percent;
        });
        setProgress(sum / queue.length);
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
                    var message = buildApiErrorMessage(json, 'Upload failed.');
                    reject(new Error(message));
                    return;
                }

                resolve(json);
            };

            xhr.onerror = function () {
                reject(new Error('Network error during upload. Please retry.'));
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

        return payload;
    }

    async function uploadOneFile(entry, metadata, queueLength) {
        var file = entry.file;
        var totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        var uploadId = makeUploadId();
        var uploadedBytes = 0;
        var finalResponse = null;

        var customFilename = queueLength === 1 ? metadata.filename : '';

        setEntryState(entry, 'uploading', 'Uploading', 0);

        for (var chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
            var start = chunkIndex * CHUNK_SIZE;
            var end = Math.min(start + CHUNK_SIZE, file.size);
            var chunkBlob = file.slice(start, end);

            var chunkMetadata = {
                folder_id: metadata.folder_id,
                filename: customFilename
            };

            var payload = buildChunkPayload(file, chunkBlob, uploadId, chunkIndex, totalChunks, chunkMetadata);
            var json = await uploadSingleChunk(payload, uploadedBytes, file.size);

            uploadedBytes = end;
            var percent = (uploadedBytes / file.size) * 100;
            setEntryState(entry, 'uploading', 'Uploading chunk ' + (chunkIndex + 1) + '/' + totalChunks, percent);
            setOverallProgressFromQueue();
            finalResponse = json;
        }

        if (!finalResponse || !finalResponse.complete) {
            throw new Error('Upload did not complete correctly.');
        }

        entry.mediaId = finalResponse.media_id || null;
        entry.editUrl = finalResponse.redirect || null;
        setEntryState(entry, 'success', 'Completed', 100);
        setOverallProgressFromQueue();
        return finalResponse;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!canUpload) {
            setStatus('Storage quota reached. Delete media before uploading.', 'is-error');
            return;
        }

        if (isUploading) {
            return;
        }

        if (!window.FormData || !window.XMLHttpRequest || !fileInput) {
            return;
        }

        if (queue.length === 0) {
            setStatus('Please add at least one file to the queue.', 'is-error');
            setProgress(0);
            return;
        }

        var startedAt = Date.now();
        var formDataSnapshot = new FormData(form);
        var metadata = {
            folder_id: String(formDataSnapshot.get('folder_id') || ''),
            filename: ''
        };
        var successCount = 0;
        var errorCount = 0;
        var queueLength = queue.length;

        isUploading = true;
        setControlsDisabled(true);
        setProgress(0);
        setStatus('Starting upload queue…', 'is-uploading');

        try {
            for (var i = 0; i < queue.length; i++) {
                var entry = queue[i];

                if (entry.file.size > storageRemainingBytes) {
                    errorCount++;
                    setEntryState(entry, 'skipped', 'Skipped: not enough remaining storage', 0);
                    setOverallProgressFromQueue();
                    continue;
                }

                setStatus('Uploading file ' + (i + 1) + ' of ' + queue.length + ': ' + entry.file.name, 'is-uploading');

                try {
                    await uploadOneFile(entry, metadata, queueLength);
                    successCount++;
                    storageRemainingBytes = Math.max(0, storageRemainingBytes - entry.file.size);
                } catch (error) {
                    errorCount++;
                    setEntryState(entry, 'error', 'Failed: ' + (error && error.message ? error.message : 'Upload error'), entry.percent);
                    setOverallProgressFromQueue();
                }
            }

            if (successCount > 0 && errorCount === 0) {
                setStatus('All files uploaded successfully (' + successCount + ').', 'is-success');
            } else if (successCount > 0 && errorCount > 0) {
                setStatus('Completed with issues: ' + successCount + ' succeeded, ' + errorCount + ' failed or skipped.', 'is-error');
            } else {
                setStatus('Upload failed for all queued files.', 'is-error');
            }

            var elapsedMs = Date.now() - startedAt;
            var minVisibleMs = 3000;
            if (elapsedMs < minVisibleMs) {
                await wait(minVisibleMs - elapsedMs);
            }
        } catch (error) {
            setStatus(error && error.message ? error.message : 'Upload failed.', 'is-error');
        } finally {
            isUploading = false;
            setControlsDisabled(false);
        }
    });

    if (dropzone) {
        dropzone.addEventListener('click', function () {
            if (!canUpload || isUploading || !fileInput) {
                return;
            }
            fileInput.click();
        });

        dropzone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (!canUpload || isUploading || !fileInput) {
                    return;
                }
                fileInput.click();
            }
        });

        dropzone.addEventListener('dragover', function (event) {
            if (!canUpload || isUploading) {
                return;
            }
            event.preventDefault();
            dropzone.classList.add('is-dragover');
        });

        dropzone.addEventListener('dragenter', function (event) {
            if (!canUpload || isUploading) {
                return;
            }
            event.preventDefault();
            dragDepth++;
            dropzone.classList.add('is-dragover');
        });

        dropzone.addEventListener('dragleave', function () {
            dragDepth = Math.max(0, dragDepth - 1);
            if (dragDepth === 0) {
                dropzone.classList.remove('is-dragover');
            }
        });

        dropzone.addEventListener('drop', function (event) {
            if (!canUpload || isUploading) {
                return;
            }
            event.preventDefault();
            dragDepth = 0;
            dropzone.classList.remove('is-dragover');
            addFiles(event.dataTransfer ? event.dataTransfer.files : null);
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            addFiles(fileInput.files);
            fileInput.value = '';
        });
    }

    renderQueue();
    setControlsDisabled(false);
}());
</script>