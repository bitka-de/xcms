<?php
$isImage   = $media->isImage();
$isVideo   = $media->isVideo();
$isAudio   = method_exists($media, 'isAudio') && $media->isAudio();
$extension = strtolower((string) ($media->extension ?? ''));
$type      = strtolower((string) ($media->type ?? 'document'));
$publicPath = (string) ($media->path ?? '');
$attributionRequired = !empty($form['attribution_required'])
    || (empty($form) && (int) ($media->attribution_required ?? 0) === 1);

$currentTagNames = [];
foreach (($media->tags ?? []) as $tag) {
    if (!is_object($tag) || !property_exists($tag, 'name')) {
        continue;
    }
    $name = trim((string) $tag->name);
    if ($name !== '') {
        $currentTagNames[] = $name;
    }
}
?>

<section class="admin-page-header media-edit-header">
    <h2>Edit Media</h2>
    <p>Update organization, metadata, and rights details for this file.</p>
</section>

<section class="admin-grid media-edit-page">
    <div class="media-edit-layout">

        <!-- Preview panel -->
        <aside class="media-edit-preview-panel">
            <div class="media-edit-preview-shell">
                <?php if ($isImage): ?>
                    <img
                        src="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars((string) ($media->alt_text ?? $media->effectiveTitle()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        loading="lazy"
                    >
                <?php elseif ($isVideo): ?>
                    <video class="media-edit-video" src="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" controls preload="metadata"></video>
                <?php elseif ($isAudio): ?>
                    <div class="media-edit-audio-hero" aria-hidden="true">
                        <span class="media-edit-audio-icon">♪</span>
                        <span>Audio File</span>
                    </div>
                    <audio class="media-edit-audio-player" src="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" controls preload="none"></audio>
                <?php else: ?>
                    <div class="media-edit-doc-hero">
                        <strong><?= htmlspecialchars(strtoupper($extension !== '' ? $extension : 'FILE'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                        <a href="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer">Open document ↗</a>
                    </div>
                <?php endif; ?>

                <span class="media-edit-type-badge media-edit-type-<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-hidden="true">
                    <?= htmlspecialchars(ucfirst($type), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </span>
            </div>

            <dl class="media-edit-file-facts">
                <div>
                    <dt>Original filename</dt>
                    <dd title="<?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Stored as</dt>
                    <dd><?= htmlspecialchars((string) $media->stored_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>MIME type</dt>
                    <dd><?= htmlspecialchars((string) $media->mime_type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Public URL</dt>
                    <dd><a href="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer"><?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></dd>
                </div>
            </dl>

            <p class="media-edit-helper-text">Path for use in block props or collection JSON</p>
            <p class="media-edit-path"><code><?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></p>
        </aside>

        <!-- Edit form -->
        <form method="post" action="/admin/media/edit?id=<?= (int) $media->id ?>" class="media-edit-form">
            <input type="hidden" name="id" value="<?= (int) $media->id ?>">

            <!-- Organization -->
            <section class="media-edit-group">
                <header>
                    <h3>Organization</h3>
                    <p>Control where this file appears and how it is grouped.</p>
                </header>

                <div class="media-edit-fields media-edit-fields-2col">
                    <label>
                        Folder
                        <select name="folder_id">
                            <option value="">Root (no folder)</option>
                            <?php foreach ($folderTree as $folder): ?>
                                <?php $indent = str_repeat('– ', (int) $folder['depth']); ?>
                                <option value="<?= (int) $folder['id'] ?>" <?= (string) ($form['folder_id'] ?? '') === (string) $folder['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['folder_id'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['folder_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

<?php
    $availableTagNamesJson = json_encode(
        array_map(fn($t) => $t->name, $availableTags ?? []),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT
    );
    $initialTagsJson = json_encode(
        array_values(array_filter(array_map('trim', explode(',', (string) ($form['tags'] ?? ''))))),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT
    );
?>
                    <div class="tag-chip-field">
                        Tags
                        <div class="tag-chip-input" id="tag-chip-input">
                            <input type="text" id="tag-chip-text" class="tag-chip-text" placeholder="Tag hinzufügen…" autocomplete="off" aria-label="Tag hinzufügen" aria-autocomplete="list" aria-controls="tag-chip-suggestions">
                            <ul class="tag-chip-suggestions" id="tag-chip-suggestions" role="listbox" hidden></ul>
                        </div>
                        <input type="hidden" name="tags" id="tags-value">
                        <?php if (!empty($errors['tags'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['tags'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                        <small class="media-edit-inline-help">Maximal 3 Tags · Enter oder Komma zum Hinzufügen · Backspace zum Entfernen.</small>
                    </div>
                </div>

            </section>

            <!-- Metadata -->
            <section class="media-edit-group">
                <header>
                    <h3>Metadata</h3>
                    <p>Used in the admin, content references, and accessibility contexts.</p>
                </header>

                <div class="media-edit-fields media-edit-fields-2col">
                    <label>
                        Display Filename *
                        <input type="text" name="filename" value="<?= htmlspecialchars((string) ($form['filename'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['filename'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['filename'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Title *
                        <input type="text" name="title" value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label class="media-edit-field-full">
                        Alt Text
                        <input type="text" name="alt_text" value="<?= htmlspecialchars((string) ($form['alt_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Describe the visual content for screen readers">
                    </label>
                </div>
            </section>

            <!-- Copyright & License -->
            <section class="media-edit-group">
                <header>
                    <h3>Copyright &amp; License</h3>
                    <p>Track rights and source details for safe reuse across pages and collections.</p>
                </header>

                <div class="media-edit-fields media-edit-fields-2col">
                    <label class="media-edit-field-full">
                        Copyright Text
                        <textarea name="copyright_text" rows="2" placeholder="e.g. © 2026 Example Studio. All rights reserved."><?= htmlspecialchars((string) ($form['copyright_text'] ?? $media->copyright_text ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                    </label>

                    <label>
                        Copyright Author
                        <input type="text" name="copyright_author" value="<?= htmlspecialchars((string) ($form['copyright_author'] ?? $media->copyright_author ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </label>

                    <label>
                        License Name
                        <input type="text" name="license_name" value="<?= htmlspecialchars((string) ($form['license_name'] ?? $media->license_name ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="e.g. CC BY 4.0">
                    </label>

                    <label>
                        License URL
                        <input type="url" name="license_url" value="<?= htmlspecialchars((string) ($form['license_url'] ?? $media->license_url ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="https://">
                        <?php if (!empty($errors['license_url'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['license_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Source URL
                        <input type="url" name="source_url" value="<?= htmlspecialchars((string) ($form['source_url'] ?? $media->source_url ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="https://">
                        <?php if (!empty($errors['source_url'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['source_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label class="media-edit-field-full">
                        Usage Notes
                        <textarea name="usage_notes" rows="3" placeholder="Internal notes on restrictions or required attribution format."><?= htmlspecialchars((string) ($form['usage_notes'] ?? $media->usage_notes ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                    </label>

                    <label class="media-edit-checkbox media-edit-field-full">
                        <input type="checkbox" name="attribution_required" value="1" <?= $attributionRequired ? 'checked' : '' ?>>
                        Attribution required when this media is used publicly
                    </label>
                </div>
            </section>

            <!-- File Maintenance -->
            <section class="media-edit-group">
                <header>
                    <h3>File Maintenance</h3>
                    <p>Optional operations that affect the physical file on disk.</p>
                </header>

                <div class="media-edit-fields">
                    <label class="media-edit-checkbox">
                        <input type="checkbox" name="rename_physical" value="1" <?= !empty($form['rename_physical']) ? 'checked' : '' ?>>
                        Rename physical file on disk (this changes the stored path)
                    </label>
                </div>
            </section>

            <div class="form-actions media-edit-actions">
                <button type="submit">Save Changes</button>
                <a href="/admin/media">← Back to library</a>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    'use strict';
    const allSuggestions = Array.isArray(<?= $availableTagNamesJson ?>) ? <?= $availableTagNamesJson ?> : [];
    const initialTags    = Array.isArray(<?= $initialTagsJson ?>) ? <?= $initialTagsJson ?> : [];

    const wrapper     = document.getElementById('tag-chip-input');
    const textInput   = document.getElementById('tag-chip-text');
    const suggestList = document.getElementById('tag-chip-suggestions');
    const hiddenInput = document.getElementById('tags-value');

    const MAX_TAGS = 3;
    let tags = initialTags.map(function (t) {
        return String(t || '').trim();
    }).filter(Boolean);
    let activeIdx = -1;

    function normalizeTag(value) {
        return String(value || '').trim().toLowerCase();
    }

    function hasTag(value) {
        const needle = normalizeTag(value);
        if (!needle) {
            return false;
        }

        return tags.some(function (tag) {
            return normalizeTag(tag) === needle;
        });
    }

    function dedupeTags(values) {
        const seen = new Set();
        const unique = [];

        values.forEach(function (value) {
            const clean = String(value || '').trim();
            if (!clean) {
                return;
            }

            const key = normalizeTag(clean);
            if (seen.has(key)) {
                return;
            }

            seen.add(key);
            unique.push(clean);
        });

        return unique;
    }

    function syncHidden() {
        hiddenInput.value = tags.join(', ');
    }

    function renderChips() {
        wrapper.querySelectorAll('.tag-chip-item').forEach(el => el.remove());
        tags.forEach(tag => {
            const chip  = document.createElement('span');
            chip.className = 'tag-chip-item';
            chip.dataset.tag = tag;

            const label = document.createElement('span');
            label.textContent = tag;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tag-chip-remove';
            btn.setAttribute('aria-label', 'Tag entfernen: ' + tag);
            btn.textContent = '×';
            btn.addEventListener('click', function (e) { e.stopPropagation(); removeTag(tag); });

            chip.append(label, btn);
            wrapper.insertBefore(chip, textInput);
        });
        syncHidden();
    }

    function addTag(raw) {
        const name = String(raw || '').trim();
        if (!name || hasTag(name)) { textInput.value = ''; return; }
        if (tags.length >= MAX_TAGS) { textInput.value = ''; hideSuggestions(); return; }
        tags.push(name);
        tags = dedupeTags(tags).slice(0, MAX_TAGS);
        renderChips();
        textInput.value = '';
        hideSuggestions();
    }

    function removeTag(name) {
        tags = tags.filter(function (t) { return t !== name; });
        renderChips();
    }

    function showSuggestions(query) {
        if (tags.length >= MAX_TAGS) { hideSuggestions(); return; }
        const q = query.toLowerCase().trim();
        if (!q) { hideSuggestions(); return; }
        const matches = allSuggestions
            .filter(function (s) { return String(s).toLowerCase().includes(q) && !hasTag(s); })
            .slice(0, 10);
        if (!matches.length) { hideSuggestions(); return; }

        suggestList.innerHTML = '';
        activeIdx = -1;
        matches.forEach(function (s) {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.textContent = s;
            li.addEventListener('mousedown', function (e) { e.preventDefault(); addTag(s); });
            suggestList.appendChild(li);
        });
        suggestList.hidden = false;
    }

    function hideSuggestions() {
        suggestList.hidden = true;
        suggestList.innerHTML = '';
        activeIdx = -1;
    }

    function updateActive() {
        const items = suggestList.querySelectorAll('li');
        items.forEach(function (li, i) { li.classList.toggle('is-active', i === activeIdx); });
    }

    textInput.addEventListener('input', function () { showSuggestions(textInput.value); });

    textInput.addEventListener('keydown', function (e) {
        const items = Array.from(suggestList.querySelectorAll('li'));
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            updateActive();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, -1);
            updateActive();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIdx >= 0 && items[activeIdx]) {
                addTag(items[activeIdx].textContent);
            } else {
                addTag(textInput.value);
            }
        } else if (e.key === ',') {
            e.preventDefault();
            addTag(textInput.value);
        } else if (e.key === 'Escape') {
            hideSuggestions();
        } else if (e.key === 'Backspace' && textInput.value === '' && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    textInput.addEventListener('blur', function () {
        setTimeout(function () {
            if (textInput.value.trim()) addTag(textInput.value);
            hideSuggestions();
        }, 150);
    });

    wrapper.addEventListener('click', function () { textInput.focus(); });

    tags = dedupeTags(tags).slice(0, MAX_TAGS);
    renderChips();
}());
</script>
