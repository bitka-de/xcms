<section class="admin-page-header">
    <h2>Edit Media</h2>
    <p>Update metadata, rights information, folder assignment, and tag assignments.</p>
</section>

<section class="admin-grid">
    <article class="stat-card media-card">
        <div class="media-preview">
            <?php if ($media->isImage()): ?>
                <img src="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($media->alt_text ?? $media->effectiveTitle()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php elseif ($media->isVideo()): ?>
                <video class="media-video" src="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" controls preload="metadata"></video>
            <?php else: ?>
                <div class="media-preview-file">PDF</div>
            <?php endif; ?>
        </div>

        <div class="media-meta">
            <span>Original file: <?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span>Stored name: <?= htmlspecialchars((string) $media->stored_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span>MIME type: <?= htmlspecialchars((string) $media->mime_type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span>Type: <?= htmlspecialchars((string) ucfirst((string) $media->type), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span>Public URL: <a href="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer"><?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></span>
        </div>
    </article>

    <form method="post" action="/admin/media/edit?id=<?= (int) $media->id ?>" class="stat-card admin-form">
        <input type="hidden" name="id" value="<?= (int) $media->id ?>">

        <label>
            Folder
            <select name="folder_id">
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
            Display Filename *
            <input type="text" name="filename" value="<?= htmlspecialchars((string) ($form['filename'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
            <?php if (!empty($errors['filename'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['filename'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Title *
            <input type="text" name="title" value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
            <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Alt Text
            <input type="text" name="alt_text" value="<?= htmlspecialchars((string) ($form['alt_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <label>
            Tags (comma separated)
            <input type="text" name="tags" value="<?= htmlspecialchars((string) ($form['tags'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="hero, brand, homepage">
        </label>

        <label>
            Copyright Text
            <textarea name="copyright_text" rows="3"><?= htmlspecialchars((string) ($form['copyright_text'] ?? $media->copyright_text ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </label>

        <label>
            Copyright Author
            <input type="text" name="copyright_author" value="<?= htmlspecialchars((string) ($form['copyright_author'] ?? $media->copyright_author ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <label>
            License Name
            <input type="text" name="license_name" value="<?= htmlspecialchars((string) ($form['license_name'] ?? $media->license_name ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <label>
            License URL
            <input type="url" name="license_url" value="<?= htmlspecialchars((string) ($form['license_url'] ?? $media->license_url ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="https://">
        </label>

        <label>
            Source URL
            <input type="url" name="source_url" value="<?= htmlspecialchars((string) ($form['source_url'] ?? $media->source_url ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="https://">
        </label>

        <label class="checkbox-label">
            <?php $attributionRequired = !empty($form['attribution_required']) || (empty($form) && (int) ($media->attribution_required ?? 0) === 1); ?>
            <input type="checkbox" name="attribution_required" value="1" <?= $attributionRequired ? 'checked' : '' ?>>
            Attribution required
        </label>

        <label>
            Usage Notes
            <textarea name="usage_notes" rows="4"><?= htmlspecialchars((string) ($form['usage_notes'] ?? $media->usage_notes ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </label>

        <label class="checkbox-label">
            <input type="checkbox" name="rename_physical" value="1" <?= !empty($form['rename_physical']) ? 'checked' : '' ?>>
            Rename physical file on disk too (path changes)
        </label>

        <p class="helper-text">Use <code><?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code> in block props or collection entry JSON fields.</p>

        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="/admin/media">Back to media library</a>
        </div>
    </form>
</section>