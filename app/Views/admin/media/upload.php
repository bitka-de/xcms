<section class="admin-page-header">
    <h2>Upload Media</h2>
    <p>Allowed types: <?= htmlspecialchars(implode(', ', $allowedExtensions), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>. Maximum size: <?= (int) $maxFileSizeMb ?> MB.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="stat-card admin-form">
        <label>
            File *
            <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.mp4,.webm,.mov,.pdf" required>
            <?php if (!empty($errors['file'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

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
            Display Filename (optional)
            <input type="text" name="filename" value="<?= htmlspecialchars((string) ($form['filename'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="example-image.jpg">
            <?php if (!empty($errors['filename'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['filename'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Title
            <input type="text" name="title" value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Alt Text
            <input type="text" name="alt_text" value="<?= htmlspecialchars((string) ($form['alt_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <p class="helper-text">Physical file names are always generated server-side and uniquely stored under <code>/public/uploads/media/</code>.</p>

        <div class="form-actions">
            <button type="submit">Upload Media</button>
            <a href="/admin/media">Back to media library</a>
        </div>
    </form>
</section>