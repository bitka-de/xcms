<section class="admin-page-header">
    <h2>Edit Collection</h2>
    <p>Update collection structure and schema.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/collections/<?= (int) $collection->id ?>/edit" class="stat-card admin-form">
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
            Schema JSON *
            <textarea name="schema_json" rows="12" required><?= htmlspecialchars((string) ($form['schema_json'] ?? '{}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
            <?php if (!empty($errors['schema_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['schema_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="/admin/collections">Back to list</a>
        </div>
    </form>

    <section class="stat-card">
        <h3>Collection Entries</h3>
        <p>Manage items stored in this collection.</p>
        <p><a href="/admin/collections/<?= (int) $collection->id ?>/entries/create">Add entry</a></p>
        <p><a href="/admin/collections/<?= (int) $collection->id ?>/entries">View entries</a></p>
    </section>
</section>
