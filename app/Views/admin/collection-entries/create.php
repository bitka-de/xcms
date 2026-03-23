<section class="admin-page-header">
    <h2>Create Entry</h2>
    <p>Add a new entry to <?= htmlspecialchars((string) $collection->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/collections/<?= (int) $collection->id ?>/entries/create" class="stat-card admin-form">
        <div class="media-helper">
            <h4>Media Helper</h4>
            <form method="get" action="/admin/collections/<?= (int) $collection->id ?>/entries/create" class="admin-form media-helper-form">
                <label>
                    Filter media by folder
                    <select name="media_folder_id" onchange="this.form.submit()">
                        <option value="">All folders</option>
                        <?php foreach (($mediaFolders ?? []) as $folder): ?>
                            <?php $indent = str_repeat('-- ', (int) $folder['depth']); ?>
                            <option value="<?= (int) $folder['id'] ?>" <?= (int) ($selectedMediaFolderId ?? 0) === (int) $folder['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>

            <?php if (!empty($mediaItems)): ?>
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Type</th>
                        <th>Path</th>
                        <th>JSON Snippet</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($mediaItems as $media): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $media->filename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $media->type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td><input class="media-url" readonly value="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" onclick="this.select();"></td>
                            <td><input class="media-url" readonly value="<?= htmlspecialchars((string) ('{"media":{"path":"' . $media->path . '","filename":"' . $media->filename . '","type":"' . $media->type . '"}}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" onclick="this.select();"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="helper-text">No media items found for the selected folder.</p>
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
            <button type="submit">Create Entry</button>
            <a href="/admin/collections/<?= (int) $collection->id ?>/edit">Back to collection</a>
        </div>
    </form>
</section>
