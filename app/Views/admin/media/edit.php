<section class="admin-page-header">
    <h2>Edit Media</h2>
    <p>Update metadata for this uploaded file.</p>
</section>

<section class="admin-grid">
    <article class="stat-card media-card">
        <div class="media-preview">
            <?php if ($media->isImage()): ?>
                <img src="<?= htmlspecialchars((string) $media->public_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($media->alt_text ?? $media->title), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php else: ?>
                <div class="media-preview-file">PDF</div>
            <?php endif; ?>
        </div>

        <div class="media-meta">
            <span>Original file: <?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span>MIME type: <?= htmlspecialchars((string) $media->mime_type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span>Public URL: <a href="<?= htmlspecialchars((string) $media->public_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer"><?= htmlspecialchars((string) $media->public_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></span>
        </div>
    </article>

    <form method="post" action="/admin/media/<?= (int) $media->id ?>/edit" class="stat-card admin-form">
        <label>
            Title *
            <input type="text" name="title" value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
            <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Alt Text
            <input type="text" name="alt_text" value="<?= htmlspecialchars((string) ($form['alt_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <p class="helper-text">Use <code><?= htmlspecialchars((string) $media->public_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code> in block props or collection entry JSON fields.</p>

        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="/admin/media">Back to media library</a>
        </div>
    </form>
</section>