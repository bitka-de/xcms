<section class="admin-page-header">
    <h2>Edit Page</h2>
    <p>Update page content and metadata.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="stat-card admin-form">
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

    <section class="stat-card">
        <h3>Block Management</h3>
        <p>This section is reserved for managing blocks on this page.</p>
        <p>Block add/edit/sort tools will be added in the next step.</p>
    </section>
</section>
