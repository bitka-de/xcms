<section class="admin-page-header">
    <h2>Edit Block Type</h2>
    <p>Update block template code and metadata.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/block-types/<?= (int) $blockType->id ?>/edit" class="stat-card admin-form">
        <p class="flash flash-error">Warning: HTML/CSS/JS editing is for trusted users only.</p>

        <label>
            Name *
            <input type="text" name="name" value="<?= htmlspecialchars((string) ($form['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Slug *
            <input type="text" name="slug" value="<?= htmlspecialchars((string) ($form['slug'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
            <?php if (!empty($errors['slug'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['slug'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            HTML Template *
            <textarea name="html_template" rows="8" required><?= htmlspecialchars((string) ($form['html_template'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
            <?php if (!empty($errors['html_template'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['html_template'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            CSS Code
            <textarea name="css_code" rows="8"><?= htmlspecialchars((string) ($form['css_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </label>

        <label>
            JS Code
            <textarea name="js_code" rows="8"><?= htmlspecialchars((string) ($form['js_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </label>

        <label>
            Schema JSON
            <textarea name="schema_json" rows="6"><?= htmlspecialchars((string) ($form['schema_json'] ?? '{}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
            <?php if (!empty($errors['schema_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['schema_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Preview Image
            <input type="text" name="preview_image" value="<?= htmlspecialchars((string) ($form['preview_image'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="/assets/preview/block.png">
        </label>

        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="/admin/block-types">Back to list</a>
        </div>
    </form>
</section>
