<section class="admin-page-header">
    <h2>Edit Entry</h2>
    <p>Update entry #<?= (int) $entry->id ?> in <?= htmlspecialchars((string) $collection->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/collections/<?= (int) $collection->id ?>/entries/<?= (int) $entry->id ?>/edit" class="stat-card admin-form">
        <div class="media-helper" data-media-helper>
            <h4>Media Helper</h4>
            <?php
            $selectedMediaType = strtolower(trim((string) ($_GET['media_type'] ?? '')));
            if (!in_array($selectedMediaType, ['image', 'video', 'audio', 'document'], true)) {
                $selectedMediaType = '';
            }
            ?>

            <div class="admin-form media-helper-form">
                <label>
                    Search
                    <input type="text" name="media_q" value="<?= htmlspecialchars((string) ($mediaSearchQuery ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="filename, title, tag, mime...">
                </label>

                <label>
                    Folder
                    <select name="media_folder_id">
                        <option value="">All folders</option>
                        <?php foreach (($mediaFolders ?? []) as $folder): ?>
                            <?php $indent = str_repeat('-- ', (int) $folder['depth']); ?>
                            <option value="<?= (int) $folder['id'] ?>" <?= (int) ($selectedMediaFolderId ?? 0) === (int) $folder['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Tag
                    <select name="media_tag_id">
                        <option value="">All tags</option>
                        <?php foreach (($mediaTags ?? []) as $tag): ?>
                            <option value="<?= (int) $tag->id ?>" <?= (int) ($selectedMediaTagId ?? 0) === (int) $tag->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $tag->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Type
                    <select name="media_type" data-media-type-filter>
                        <option value="" <?= $selectedMediaType === '' ? 'selected' : '' ?>>All types</option>
                        <option value="image" <?= $selectedMediaType === 'image' ? 'selected' : '' ?>>Image</option>
                        <option value="video" <?= $selectedMediaType === 'video' ? 'selected' : '' ?>>Video</option>
                        <option value="audio" <?= $selectedMediaType === 'audio' ? 'selected' : '' ?>>Audio</option>
                        <option value="document" <?= $selectedMediaType === 'document' ? 'selected' : '' ?>>Document</option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="button" data-helper-filter-apply>Filter</button>
                    <a href="/admin/collections/<?= (int) $collection->id ?>/entries/<?= (int) $entry->id ?>/edit">Reset</a>
                </div>
            </div>

            <?php if (!empty($mediaItems)): ?>
                <div class="media-manager-grid media-helper-grid" role="list">
                    <?php foreach ($mediaItems as $media): ?>
                        <?php
                        $type = strtolower((string) ($media->type ?? 'document'));
                        if (!in_array($type, ['image', 'video', 'audio', 'document'], true)) {
                            $type = 'document';
                        }

                        $isImage = $media->isImage();
                        $isVideo = $media->isVideo();
                        $isAudio = method_exists($media, 'isAudio') && $media->isAudio();
                        $isPdf = strtolower((string) ($media->extension ?? '')) === 'pdf';

                        $tagNames = [];
                        foreach (($media->tags ?? []) as $tag) {
                            if (is_object($tag) && property_exists($tag, 'name')) {
                                $name = trim((string) $tag->name);
                                if ($name !== '') {
                                    $tagNames[] = $name;
                                }
                            }
                        }

                        $snippet = json_encode([
                            'media' => [
                                'path' => $media->path,
                                'filename' => $media->filename,
                                'type' => $media->type,
                            ],
                        ], JSON_UNESCAPED_SLASHES);

                        $path = (string) ($media->path ?? '');
                        $filename = (string) ($media->filename ?? '');
                        $mimeType = (string) ($media->mime_type ?? 'unknown');
                        $folderName = (string) ($media->folder_name ?? 'Root');
                        $badgeClass = 'media-type-badge media-type-' . $type;
                        ?>
                        <article class="media-manager-card media-helper-card" role="listitem" data-media-item data-media-type="<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            <div class="media-manager-preview">
                                <?php if ($isImage): ?>
                                    <img src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($filename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" loading="lazy">
                                <?php elseif ($isVideo): ?>
                                    <video class="media-manager-video" src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" muted preload="metadata" playsinline aria-label="Video preview"></video>
                                <?php elseif ($isAudio): ?>
                                    <div class="media-manager-audio-preview" aria-hidden="true">
                                        <span class="media-audio-icon">♪</span>
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
                                <?php elseif ($isPdf): ?>
                                    <div class="media-manager-doc-preview media-manager-doc-pdf" aria-label="PDF preview">PDF</div>
                                <?php else: ?>
                                    <div class="media-manager-doc-preview" aria-label="Document preview"><?= htmlspecialchars(strtoupper((string) ($media->extension ?? 'FILE')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                                <?php endif; ?>
                                <span class="<?= htmlspecialchars($badgeClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($type), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            </div>

                            <div class="media-manager-body">
                                <div class="media-manager-title-row">
                                    <h3 title="<?= htmlspecialchars($filename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($filename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                                    <span class="media-ext-pill"><?= htmlspecialchars(strtoupper((string) ($media->extension ?? 'FILE')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                </div>
                                <p class="media-manager-meta"><?= htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                                <p class="media-manager-meta">Folder: <?= htmlspecialchars($folderName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

                                <?php if ($tagNames !== []): ?>
                                    <ul class="media-tag-chips" aria-label="Tags">
                                        <?php foreach ($tagNames as $tagName): ?>
                                            <li><?= htmlspecialchars($tagName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <div class="media-manager-actions">
                                <button type="button" class="media-action-main" data-insert-value="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Insert Path</button>
                                <button type="button" class="media-action-main" data-insert-value="<?= htmlspecialchars((string) $snippet, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Insert Snippet</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="helper-text" data-media-helper-empty hidden>No media items match the selected type filter.</p>
            <?php else: ?>
                <p class="helper-text">No media items found for the selected filter.</p>
            <?php endif; ?>
        </div>

        <label>
            Status
            <?php $status = (string) ($form['status'] ?? 'draft'); ?>
            <select name="status" required>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>draft</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>published</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>archived</option>
            </select>
            <?php if (!empty($errors['status'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Data JSON
            <textarea name="data_json" rows="14" required><?= htmlspecialchars((string) ($form['data_json'] ?? '{}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
            <?php if (!empty($errors['data_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['data_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="/admin/collections/<?= (int) $collection->id ?>/edit">Back to collection</a>
        </div>
    </form>
</section>

<script>
(function () {
    const helper = document.querySelector('[data-media-helper]');
    if (!helper) {
        return;
    }

    const applyButton = helper.querySelector('[data-helper-filter-apply]');
    if (applyButton) {
        applyButton.addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);
            const q = helper.querySelector('input[name="media_q"]')?.value.trim() || '';
            const folderId = helper.querySelector('select[name="media_folder_id"]')?.value || '';
            const tagId = helper.querySelector('select[name="media_tag_id"]')?.value || '';
            const type = helper.querySelector('select[name="media_type"]')?.value || '';

            if (q === '') {
                params.delete('media_q');
            } else {
                params.set('media_q', q);
            }

            if (folderId === '') {
                params.delete('media_folder_id');
            } else {
                params.set('media_folder_id', folderId);
            }

            if (tagId === '') {
                params.delete('media_tag_id');
            } else {
                params.set('media_tag_id', tagId);
            }

            if (type === '') {
                params.delete('media_type');
            } else {
                params.set('media_type', type);
            }

            window.location.search = params.toString();
        });
    }

    const textarea = document.querySelector('textarea[name="data_json"]');
    const insertAtCaret = (text) => {
        if (!textarea) {
            return;
        }

        const start = textarea.selectionStart ?? textarea.value.length;
        const end = textarea.selectionEnd ?? textarea.value.length;
        textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
        const nextPos = start + text.length;
        textarea.selectionStart = nextPos;
        textarea.selectionEnd = nextPos;
        textarea.focus();
    };

    helper.querySelectorAll('[data-insert-value]').forEach((button) => {
        button.addEventListener('click', () => {
            const value = button.getAttribute('data-insert-value') || '';
            insertAtCaret(value);
        });
    });

    const typeFilter = helper.querySelector('[data-media-type-filter]');
    const helperCards = helper.querySelectorAll('[data-media-item]');
    const helperEmpty = helper.querySelector('[data-media-helper-empty]');

    const applyTypeFilter = () => {
        if (!typeFilter || helperCards.length === 0) {
            return;
        }

        const selectedType = (typeFilter.value || '').toLowerCase();
        let visibleCount = 0;

        helperCards.forEach((card) => {
            const itemType = (card.getAttribute('data-media-type') || '').toLowerCase();
            const visible = selectedType === '' || itemType === selectedType;
            card.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        if (helperEmpty) {
            helperEmpty.hidden = visibleCount > 0;
        }
    };

    if (typeFilter) {
        typeFilter.addEventListener('change', applyTypeFilter);
        applyTypeFilter();
    }

    function formatAudioDuration(s) {
        if (!isFinite(s) || s < 0) { return '–:––'; }
        return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
    }

    function updateAudioProgress(audio, progressEl, progressFill, durationEl) {
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
            var totalLabel = duration > 0 ? formatAudioDuration(duration) : '–:––';
            durationEl.textContent = formatAudioDuration(current) + ' / ' + totalLabel;
        }
    }

    function setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, isPlaying, hasPlayed) {
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

    helper.querySelectorAll('[data-audio-player]').forEach(function (audio) {
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

        setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, false, false);
        updateAudioProgress(audio, progressEl, progressFill, durEl);

        var rafId = 0;
        function tickProgress() {
            updateAudioProgress(audio, progressEl, progressFill, durEl);
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
            updateAudioProgress(audio, progressEl, progressFill, durEl);
        });

        audio.addEventListener('ended', function () {
            stopProgressLoop();
            audio.currentTime = 0;
            setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
            updateAudioProgress(audio, progressEl, progressFill, durEl);
        });

        toggleBtn.addEventListener('click', function () {
            if (audio.paused) {
                helper.querySelectorAll('[data-audio-player]').forEach(function (other) {
                    if (other !== audio && !other.paused) {
                        other.pause();
                        other.currentTime = 0;
                        var oc = other.nextElementSibling;
                        var ot = oc ? oc.querySelector('[data-audio-toggle]') : null;
                        var orBtn = oc ? oc.querySelector('[data-audio-restart]') : null;
                        var opIcon = oc ? oc.querySelector('[data-audio-icon-play]') : null;
                        var osIcon = oc ? oc.querySelector('[data-audio-icon-stop]') : null;
                        var hasPlayedOther = other.dataset.audioHasPlayed === '1';
                        setAudioState(ot, orBtn, opIcon, osIcon, false, hasPlayedOther);
                    }
                });

                audio.dataset.audioHasPlayed = '1';
                audio.play();
                setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, true, true);
                startProgressLoop();
            } else {
                audio.pause();
                stopProgressLoop();
                setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
                updateAudioProgress(audio, progressEl, progressFill, durEl);
            }
        });

        resetBtn.addEventListener('click', function () {
            audio.pause();
            stopProgressLoop();
            audio.currentTime = 0;
            setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
            updateAudioProgress(audio, progressEl, progressFill, durEl);
        });

        audio.addEventListener('pause', function () {
            stopProgressLoop();
            if (!audio.ended) {
                setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
                updateAudioProgress(audio, progressEl, progressFill, durEl);
            }
        });

        audio.addEventListener('play', function () {
            audio.dataset.audioHasPlayed = '1';
            setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, true, true);
            startProgressLoop();
            updateAudioProgress(audio, progressEl, progressFill, durEl);
        });

        audio.addEventListener('error', function () {
            stopProgressLoop();
            setAudioState(toggleBtn, resetBtn, playIcon, stopIcon, false, audio.dataset.audioHasPlayed === '1');
            if (durEl && durEl.textContent === '–:––') {
                durEl.textContent = 'Fehler';
            }
        });
    });
})();
</script>
