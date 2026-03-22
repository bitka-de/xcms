<section class="admin-page-header">
    <h2>Edit Entry</h2>
    <p>Update entry #<?= (int) $entry->id ?> in <?= htmlspecialchars((string) $collection->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/collections/<?= (int) $collection->id ?>/entries/<?= (int) $entry->id ?>/edit" class="stat-card admin-form">
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
