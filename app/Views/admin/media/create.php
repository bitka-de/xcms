<section class="admin-page-header">
    <h2>Upload Media</h2>
    <p>Allowed types: <?= htmlspecialchars(implode(', ', $allowedExtensions), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>. Maximum size: <?= (int) $maxFileSizeMb ?> MB.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="stat-card admin-form">
        <label>
            File *
            <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" required>
            <?php if (!empty($errors['file'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
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

        <p class="helper-text">After upload, copy the public URL from the media list into page block <code>props_json</code> or collection entry <code>data_json</code>.</p>

        <div class="form-actions">
            <button type="submit">Upload Media</button>
            <a href="/admin/media">Back to media library</a>
        </div>
    </form>
</section>