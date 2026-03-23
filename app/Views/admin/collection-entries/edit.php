<section class="admin-page-header">
    <h2>Edit Entry</h2>
    <p>Update entry #<?= (int) $entry->id ?> in <?= htmlspecialchars((string) $collection->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/collections/<?= (int) $collection->id ?>/entries/<?= (int) $entry->id ?>/edit" class="stat-card admin-form">
        <div class="media-helper" data-media-helper>
            <h4>Media Helper</h4>

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

                <div class="form-actions">
                    <button type="button" data-helper-filter-apply>Filter</button>
                    <a href="/admin/collections/<?= (int) $collection->id ?>/entries/<?= (int) $entry->id ?>/edit">Reset</a>
                </div>
            </div>

            <?php if (!empty($mediaItems)): ?>
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Type</th>
                        <th>Tags</th>
                        <th>Insert</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($mediaItems as $media): ?>
                        <?php
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
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $media->filename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $media->type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($tagNames !== [] ? implode(', ', $tagNames) : '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td>
                                <div class="form-actions">
                                    <button type="button" class="media-action-btn" data-insert-value="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Insert Path</button>
                                    <button type="button" class="media-action-btn" data-insert-value="<?= htmlspecialchars((string) $snippet, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Insert Snippet</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
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
})();
</script>
