<?php

$selectedFolderId = (int) ($selectedFolderId ?? 0);
$selectedTagId    = (int) ($selectedTagId ?? 0);
$selectedType     = (string) ($selectedType ?? '');
$searchQuery      = (string) ($searchQuery ?? '');
$hasFilters       = $searchQuery !== '' || $selectedFolderId > 0 || $selectedTagId > 0 || $selectedType !== '';
$itemCount        = count($mediaItems);

$quotaService = new \App\Services\StorageQuotaService(new \App\Repositories\MediaRepository());
$usage = $quotaService->getUsageSummary();
$usedStorageBytes = (int) $usage['used_bytes'];
$maxStorageBytes = (int) $usage['total_bytes'];
$remainingStorageBytes = (int) $usage['remaining_bytes'];
$quotaUsedPercent = (float) $usage['used_percent'];
$canUpload = $remainingStorageBytes > 0;
?>

<section class="admin-page-header media-manager-header">
    <div class="media-manager-title-wrap">
        <h2>Media Library</h2>
        <p>Images, video, audio, and documents — filter, reuse paths, and manage rights.</p>
    </div>
    <div class="media-header-actions">
        <a class="media-header-btn media-header-btn-primary<?= $canUpload ? '' : ' is-disabled' ?>" href="<?= $canUpload ? '/admin/media/upload' : '#' ?>"<?= $canUpload ? '' : ' aria-disabled="true" tabindex="-1"' ?>>Upload Media</a>
        <a class="media-header-btn" href="/admin/media/folders">Manage Folders</a>
    </div>
</section>

<section class="admin-grid media-manager-page">
    <article class="media-quota-panel" aria-label="Storage quota">
        <div class="media-quota-top">
            <h3>Storage Quota</h3>
            <span class="media-quota-state <?= $canUpload ? 'is-ok' : 'is-full' ?>"><?= $canUpload ? 'Uploads available' : 'Storage full' ?></span>
        </div>
        <div class="media-quota-stats">
            <p><strong><?= htmlspecialchars((string) $usage['used_formatted'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><span>used</span></p>
            <p><strong><?= htmlspecialchars((string) $usage['remaining_formatted'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><span>remaining</span></p>
            <p><strong><?= htmlspecialchars((string) $usage['total_formatted'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><span>total</span></p>
        </div>
        <div class="media-quota-bar" role="progressbar" aria-label="Storage used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($quotaUsedPercent) ?>">
            <span class="media-quota-fill" style="width: <?= number_format($quotaUsedPercent, 2, '.', '') ?>%"></span>
        </div>
        <p class="media-quota-note"><?= number_format($quotaUsedPercent, 1) ?>% of storage quota in use</p>
        <?php if (!$canUpload): ?>
            <p class="media-quota-alert">Delete media files to free space before uploading new files.</p>
        <?php endif; ?>
    </article>

    <form method="get" action="/admin/media" class="media-manager-toolbar" aria-label="Media filters">
        <div class="media-toolbar-row media-toolbar-row-top">
            <label class="media-field media-field-search">
                <span>Search</span>
                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($searchQuery, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    placeholder="Filename, title, MIME, tags, copyright…"
                    autocomplete="off"
                >
            </label>

            <label class="media-field">
                <span>Folder</span>
                <select name="folder_id">
                    <option value="">All folders</option>
                    <?php foreach (($folderTree ?? []) as $folder): ?>
                        <?php $indent = str_repeat('– ', (int) $folder['depth']); ?>
                        <option value="<?= (int) $folder['id'] ?>" <?= $selectedFolderId === (int) $folder['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="media-field">
                <span>Tag</span>
                <select name="tag_id">
                    <option value="">All tags</option>
                    <?php foreach (($availableTags ?? []) as $tag): ?>
                        <option value="<?= (int) $tag->id ?>" <?= $selectedTagId === (int) $tag->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $tag->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="media-field">
                <span>Type</span>
                <select name="type">
                    <option value="">All types</option>
                    <option value="image"    <?= $selectedType === 'image'    ? 'selected' : '' ?>>Image</option>
                    <option value="video"    <?= $selectedType === 'video'    ? 'selected' : '' ?>>Video</option>
                    <option value="audio"    <?= $selectedType === 'audio'    ? 'selected' : '' ?>>Audio</option>
                    <option value="document" <?= $selectedType === 'document' ? 'selected' : '' ?>>Document</option>
                </select>
            </label>
        </div>

        <div class="media-toolbar-row media-toolbar-row-bottom">
            <div class="media-toolbar-meta">
                <strong><?= $itemCount ?></strong>
                <span><?= $itemCount === 1 ? 'item' : 'items' ?></span>
                <?php if ($hasFilters): ?>
                    <span class="media-filter-dot" aria-hidden="true">•</span>
                    <span class="media-filter-active-label" role="status" aria-live="polite">Filtered</span>
                <?php endif; ?>
            </div>

            <div class="media-toolbar-actions">
                <?php if ($hasFilters): ?>
                    <a class="media-toolbar-link" href="/admin/media">Reset filters</a>
                <?php endif; ?>
                <button type="submit">Apply</button>
            </div>
        </div>
    </form>

    <?php if (empty($mediaItems)): ?>
        <article class="media-manager-empty" aria-live="polite">
            <div class="media-empty-illustration" aria-hidden="true">◌</div>
            <?php if ($hasFilters): ?>
                <h3>No results</h3>
                <p>No files match your current filters. Try clearing the search or resetting the toolbar.</p>
                <div class="media-empty-actions">
                    <a class="media-header-btn" href="/admin/media">Clear Filters</a>
                    <a class="media-header-btn media-header-btn-primary<?= $canUpload ? '' : ' is-disabled' ?>" href="<?= $canUpload ? '/admin/media/upload' : '#' ?>"<?= $canUpload ? '' : ' aria-disabled="true" tabindex="-1"' ?>>Upload Media</a>
                </div>
            <?php else: ?>
                <h3>Library is empty</h3>
                <p>Upload your first file to start reusing assets in pages and collections.</p>
                <div class="media-empty-actions">
                    <a class="media-header-btn media-header-btn-primary<?= $canUpload ? '' : ' is-disabled' ?>" href="<?= $canUpload ? '/admin/media/upload' : '#' ?>"<?= $canUpload ? '' : ' aria-disabled="true" tabindex="-1"' ?>>Upload First File</a>
                </div>
            <?php endif; ?>
        </article>
    <?php else: ?>
        <div class="media-manager-grid" role="list">
            <?php foreach ($mediaItems as $media): ?>
                <?php
                $isImage  = $media->isImage();
                $isVideo  = $media->isVideo();
                $isAudio  = method_exists($media, 'isAudio') && $media->isAudio();
                $isPdf    = strtolower((string) ($media->extension ?? '')) === 'pdf';

                $type = strtolower((string) ($media->type ?? 'document'));
                if (!in_array($type, ['image', 'video', 'audio', 'document'], true)) {
                    $type = 'document';
                }

                $typeLabel  = ucfirst($type);
                $badgeClass = 'media-type-badge media-type-' . $type;
                $fileSize   = (int) ($media->file_size ?? $media->size_bytes ?? 0);
                $title      = (string) $media->effectiveTitle();
                $mimeType   = (string) ($media->mime_type ?? '');
                $folderName = (string) ($media->folder_name ?? 'Root');
                $path       = (string) ($media->path ?? '');
                $extension  = strtoupper((string) ($media->extension ?? ''));
                $altText    = (string) ($media->alt_text ?? $title);

                $tagNames = [];
                foreach (($media->tags ?? []) as $tag) {
                    if (!is_object($tag) || !property_exists($tag, 'name')) {
                        continue;
                    }
                    $tn = trim((string) $tag->name);
                    if ($tn !== '') {
                        $tagNames[] = $tn;
                    }
                }

                $rightsParts = [];
                if (!empty($media->copyright_author)) {
                    $rightsParts[] = (string) $media->copyright_author;
                }
                if (!empty($media->license_name)) {
                    $rightsParts[] = (string) $media->license_name;
                }
                ?>
                <article class="media-manager-card" role="listitem" data-media-type="<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    <?php if ($isAudio): ?>
                    <div class="media-manager-preview">
                        <div class="media-manager-audio-preview" aria-hidden="true">
                            <span class="media-audio-icon" aria-hidden="true">♪</span>
                            <span class="media-audio-label">Audio</span>
                        </div>
                        <audio src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" preload="metadata" hidden data-audio-player></audio>
                        <div class="media-audio-controls">
                            <div class="media-audio-top">
                                <div class="media-audio-buttons">
                                    <button type="button" class="media-audio-btn media-audio-btn-start" data-audio-toggle aria-label="Play">
                                        <span data-audio-icon-play>
                                            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="lucide lucide-play-icon lucide-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M5 5a2 2 0 0 1 3-1.73l12 7a2 2 0 0 1 0 3.46l-12 7A2 2 0 0 1 5 19z"/>
                                            </svg>
                                        </span>
                                        <span data-audio-icon-stop hidden>
                                            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="lucide lucide-pause-icon lucide-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <rect width="5" height="18" x="14" y="3" rx="1"/>
                                                <rect width="5" height="18" x="5" y="3" rx="1"/>
                                            </svg>
                                        </span>
                                    </button>
                                    <button type="button" class="media-audio-btn media-audio-btn-restart" data-audio-restart hidden aria-label="Neu starten">
                                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="lucide lucide-rotate-ccw-icon lucide-rotate-ccw" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M3 12a9 9 0 1 0 9-9 9.8 9.8 0 0 0-6.74 2.74L3 8"/>
                                            <path d="M3 3v5h5"/>
                                        </svg>
                                    </button>
                                </div>
                                <span class="media-audio-duration" data-audio-duration>0:00 / –:––</span>
                            </div>
                            <div class="media-audio-progress" role="progressbar" aria-label="Audio Fortschritt" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-audio-progress>
                                <div class="media-audio-progress-fill" data-audio-progress-fill></div>
                            </div>
                        </div>
                        <span class="<?= htmlspecialchars($badgeClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-hidden="true"><?= htmlspecialchars($typeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                    </div>
                    <?php else: ?>
                    <a class="media-manager-preview" href="/admin/media/edit?id=<?= (int) $media->id ?>" aria-label="Edit <?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <?php if ($isImage): ?>
                            <img src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($altText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" loading="lazy">
                        <?php elseif ($isVideo): ?>
                            <video class="media-manager-video" src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" muted preload="metadata" playsinline aria-label="Video preview"></video>
                        <?php elseif ($isPdf): ?>
                            <div class="media-manager-doc-preview media-manager-doc-pdf" aria-label="PDF document">PDF</div>
                        <?php else: ?>
                            <div class="media-manager-doc-preview" aria-label="Document"><?= htmlspecialchars($extension !== '' ? $extension : 'FILE', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                        <?php endif; ?>

                        <span class="<?= htmlspecialchars($badgeClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-hidden="true"><?= htmlspecialchars($typeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                    </a>
                    <?php endif; ?>

                    <div class="media-manager-body">
                        <div class="media-manager-title-row">
                            <h3 title="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                            <?php if ($extension !== ''): ?>
                                <span class="media-ext-pill" aria-label="File type"><?= htmlspecialchars($extension, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="media-manager-meta">
                            <?= htmlspecialchars($quotaService->formatBytes($fileSize), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            <?php if ($mimeType !== ''): ?>
                                · <?= htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
                        <p class="media-manager-meta">📁 <?= htmlspecialchars($folderName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

                        <?php if ($tagNames !== []): ?>
                            <ul class="media-tag-chips" aria-label="Tags">
                                <?php foreach ($tagNames as $tagName): ?>
                                    <li><?= htmlspecialchars($tagName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($rightsParts !== []): ?>
                            <p class="media-rights-line">© <?= htmlspecialchars(implode(' · ', $rightsParts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="media-manager-actions">
                        <a class="media-action-main" href="/admin/media/edit?id=<?= (int) $media->id ?>">Edit</a>
                        <form method="post" action="/admin/media/delete" onsubmit="return confirm('Delete this file and remove it from storage?');">
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $media->id ?>">
                            <button type="submit" class="media-action-danger">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    function formatDuration(s) {
        if (!isFinite(s) || s < 0) { return '–:––'; }
        return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
    }

    function updateProgress(audio, progressEl, progressFill, durationEl) {
        var duration = isFinite(audio.duration) && audio.duration > 0 ? audio.duration : 0;
        var current = isFinite(audio.currentTime) && audio.currentTime >= 0 ? audio.currentTime : 0;
        var percent = duration > 0 ? Math.min(100, Math.max(0, (current / duration) * 100)) : 0;

        if (progressFill) {
            progressFill.style.width = percent.toFixed(2) + '%';
        }
        if (progressEl) {
            progressEl.setAttribute('aria-valuenow', String(Math.round(percent)));
        }
        if (durationEl) {
            var totalLabel = duration > 0 ? formatDuration(duration) : '–:––';
            durationEl.textContent = formatDuration(current) + ' / ' + totalLabel;
        }
    }

    function setState(toggleBtn, resetBtn, playIcon, stopIcon, isPlaying, hasPlayed) {
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-label', isPlaying ? 'Pause' : 'Play');
        }
        if (playIcon) {
            playIcon.hidden = isPlaying;
        }
        if (stopIcon) {
            stopIcon.hidden = !isPlaying;
        }
        if (resetBtn) {
            resetBtn.hidden = !hasPlayed;
        }
    }

    document.querySelectorAll('[data-audio-player]').forEach(function (audio) {
        var controls = audio.nextElementSibling;
        var toggleBtn = controls ? controls.querySelector('[data-audio-toggle]') : null;
        var resetBtn = controls ? controls.querySelector('[data-audio-restart]') : null;
        var playIcon = controls ? controls.querySelector('[data-audio-icon-play]') : null;
        var stopIcon = controls ? controls.querySelector('[data-audio-icon-stop]') : null;
        var durEl    = controls ? controls.querySelector('[data-audio-duration]') : null;
        var progressEl = controls ? controls.querySelector('[data-audio-progress]') : null;
        var progressFill = controls ? controls.querySelector('[data-audio-progress-fill]') : null;
        if (!toggleBtn || !resetBtn || !playIcon || !stopIcon) { return; }

        audio.dataset.audioHasPlayed = '0';

        setState(toggleBtn, resetBtn, playIcon, stopIcon, false, false);
        updateProgress(audio, progressEl, progressFill, durEl);

        var rafId = 0;
        function tickProgress() {
            updateProgress(audio, progressEl, progressFill, durEl);
            if (!audio.paused && !audio.ended) {
                rafId = window.requestAnimationFrame(tickProgress);
            } else {
                rafId = 0;
            }
        }
        function startProgressLoop() {
            if (rafId === 0) {
                rafId = window.requestAnimationFrame(tickProgress);
            }
        }
        function stopProgressLoop() {
            if (rafId !== 0) {
                window.cancelAnimationFrame(rafId);
                rafId = 0;
            }
        }

        audio.addEventListener('loadedmetadata', function () {
            updateProgress(audio, progressEl, progressFill, durEl);
        });

        audio.addEventListener('ended', function () {
            stopProgressLoop();
            audio.currentTime = 0;
            setState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
            updateProgress(audio, progressEl, progressFill, durEl);
        });

        toggleBtn.addEventListener('click', function () {
            if (audio.paused) {
                document.querySelectorAll('[data-audio-player]').forEach(function (other) {
                    if (other !== audio && !other.paused) {
                        other.pause();
                        other.currentTime = 0;
                        var oc = other.nextElementSibling;
                        var ot = oc ? oc.querySelector('[data-audio-toggle]') : null;
                        var orBtn = oc ? oc.querySelector('[data-audio-restart]') : null;
                        var opIcon = oc ? oc.querySelector('[data-audio-icon-play]') : null;
                        var osIcon = oc ? oc.querySelector('[data-audio-icon-stop]') : null;
                        var hasPlayedOther = other.dataset.audioHasPlayed === '1';
                        setState(ot, orBtn, opIcon, osIcon, false, hasPlayedOther);
                    }
                });

                audio.dataset.audioHasPlayed = '1';
                audio.play();
                setState(toggleBtn, resetBtn, playIcon, stopIcon, true, true);
                startProgressLoop();
            } else {
                audio.pause();
                stopProgressLoop();
                setState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
                updateProgress(audio, progressEl, progressFill, durEl);
            }
        });

        resetBtn.addEventListener('click', function () {
            audio.pause();
            stopProgressLoop();
            audio.currentTime = 0;
            setState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
            updateProgress(audio, progressEl, progressFill, durEl);
        });

        audio.addEventListener('pause', function () {
            stopProgressLoop();
            if (!audio.ended) {
                setState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
                updateProgress(audio, progressEl, progressFill, durEl);
            }
        });

        audio.addEventListener('play', function () {
            audio.dataset.audioHasPlayed = '1';
            setState(toggleBtn, resetBtn, playIcon, stopIcon, true, true);
            startProgressLoop();
            updateProgress(audio, progressEl, progressFill, durEl);
        });

        audio.addEventListener('error', function () {
            stopProgressLoop();
            setState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
            if (durEl && durEl.textContent === '–:––') {
                durEl.textContent = 'Fehler';
            }
        });
    });
}());
</script>
