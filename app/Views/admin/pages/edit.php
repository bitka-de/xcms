<section class="admin-page-header">
    <h2>Edit Page</h2>
    <p>Update page content and metadata.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="stat-card admin-form">
        <input type="hidden" name="_action" value="save_page">

        <label>
            Title *
            <input type="text" name="title" value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
            <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Slug *
            <input type="text" name="slug" value="<?= htmlspecialchars((string) ($form['slug'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
            <?php if (!empty($errors['slug'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['slug'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Visibility *
            <select name="visibility" required>
                <?php $visibility = (string) ($form['visibility'] ?? 'draft'); ?>
                <option value="draft" <?= $visibility === 'draft' ? 'selected' : '' ?>>draft</option>
                <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>private</option>
                <option value="public" <?= $visibility === 'public' ? 'selected' : '' ?>>public</option>
            </select>
            <?php if (!empty($errors['visibility'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['visibility'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            SEO Title
            <input type="text" name="seo_title" value="<?= htmlspecialchars((string) ($form['seo_title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <label>
            SEO Description
            <textarea name="seo_description" rows="4"><?= htmlspecialchars((string) ($form['seo_description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="/admin/pages">Back to list</a>
        </div>
    </form>

    <section class="stat-card admin-form">
        <h3>Block Management</h3>
        <p>Manage block instances for this page. Blocks are rendered in ascending <code>sort_order</code>.</p>

        <div class="media-helper" data-media-helper>
            <h4>Media Helper</h4>
            <?php
            $selectedMediaType = strtolower(trim((string) ($_GET['media_type'] ?? '')));
            if (!in_array($selectedMediaType, ['image', 'video', 'audio', 'document'], true)) {
                $selectedMediaType = '';
            }
            ?>
            <form method="get" action="/admin/pages/<?= (int) $page->id ?>/edit" class="admin-form media-helper-form">
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
                    <button type="submit">Filter</button>
                    <a href="/admin/pages/<?= (int) $page->id ?>/edit">Reset</a>
                </div>
            </form>

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

        <h4>Existing Blocks</h4>
        <?php if (empty($pageBlocks)): ?>
            <p>No blocks added yet.</p>
        <?php else: ?>
            <?php foreach ($pageBlocks as $pageBlock): ?>
                <?php
                $blockType = $blockTypeMap[(int) $pageBlock->block_type_id] ?? null;
                $existingErrors = $blockErrors['existing_' . (int) $pageBlock->id] ?? [];
                ?>
                <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="stat-card admin-form">
                    <input type="hidden" name="_action" value="update_block">
                    <input type="hidden" name="block_id" value="<?= (int) $pageBlock->id ?>">

                    <p>
                        <strong>Block #<?= (int) $pageBlock->id ?></strong>
                        <?php if ($blockType !== null): ?>
                            <span> - <?= htmlspecialchars((string) $blockType->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </p>

                    <label>
                        Block Type
                        <select name="block_type_id" required>
                            <?php foreach ($blockTypes as $type): ?>
                                <option value="<?= (int) $type->id ?>" <?= (int) $type->id === (int) $pageBlock->block_type_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $type->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($existingErrors['block_type_id'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['block_type_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Sort Order
                        <input type="number" name="sort_order" value="<?= (int) $pageBlock->sort_order ?>" required>
                        <?php if (!empty($existingErrors['sort_order'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['sort_order'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Props JSON
                        <textarea name="props_json" rows="6"><?= htmlspecialchars((string) $pageBlock->props_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                        <?php if (!empty($existingErrors['props_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['props_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Bindings JSON
                        <textarea name="bindings_json" rows="6"><?= htmlspecialchars((string) $pageBlock->bindings_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                        <?php if (!empty($existingErrors['bindings_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['bindings_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <div class="form-actions">
                        <button type="submit">Save Block</button>
                    </div>
                </form>

                <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="inline-form" onsubmit="return confirm('Delete this block instance?');">
                    <input type="hidden" name="_action" value="delete_block">
                    <input type="hidden" name="block_id" value="<?= (int) $pageBlock->id ?>">
                    <button type="submit" class="link-button">Delete Block</button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <h4>Add New Block</h4>
        <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="stat-card admin-form">
            <input type="hidden" name="_action" value="add_block">

            <label>
                Block Type
                <select name="block_type_id" required>
                    <option value="">Select a block type</option>
                    <?php foreach ($blockTypes as $type): ?>
                        <option value="<?= (int) $type->id ?>" <?= (string) ($newBlockForm['block_type_id'] ?? '') === (string) $type->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $type->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($blockErrors['block_type_id'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['block_type_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Sort Order
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string) ($newBlockForm['sort_order'] ?? '0'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                <?php if (!empty($blockErrors['sort_order'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['sort_order'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Props JSON
                <textarea name="props_json" rows="6"><?= htmlspecialchars((string) ($newBlockForm['props_json'] ?? '{}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                <?php if (!empty($blockErrors['props_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['props_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Bindings JSON
                <textarea name="bindings_json" rows="6"><?= htmlspecialchars((string) ($newBlockForm['bindings_json'] ?? '{}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                <?php if (!empty($blockErrors['bindings_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['bindings_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <div class="form-actions">
                <button type="submit">Add Block</button>
            </div>
        </form>
    </section>
</section>

<script>
(function () {
    const helper = document.querySelector('[data-media-helper]');
    if (!helper) {
        return;
    }

    let activeTextarea = null;
    const targets = document.querySelectorAll('textarea[name="props_json"], textarea[name="bindings_json"]');
    targets.forEach((el) => {
        el.addEventListener('focus', () => {
            activeTextarea = el;
        });
    });

    const insertAtCaret = (textarea, text) => {
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
            const target = activeTextarea || document.querySelector('textarea[name="props_json"], textarea[name="bindings_json"]');
            if (!target) {
                return;
            }
            insertAtCaret(target, value);
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
