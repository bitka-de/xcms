<section class="admin-page-header">
    <h2>Edit Page</h2>
    <p>Update page content and metadata.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="stat-card admin-form">
        <input type="hidden" name="_action" value="save_page">

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

    <section class="stat-card admin-form">
        <h3>Block Management</h3>
        <p>Manage block instances for this page. Blocks are rendered in ascending <code>sort_order</code>.</p>

        <div class="media-helper">
            <h4>Media Helper</h4>
            <form method="get" action="/admin/pages/<?= (int) $page->id ?>/edit" class="admin-form media-helper-form">
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
                        <th>File</th>
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

        <h4>Existing Blocks</h4>
        <?php if (empty($pageBlocks)): ?>
            <p>No blocks added yet.</p>
        <?php else: ?>
            <?php foreach ($pageBlocks as $pageBlock): ?>
                <?php
                $blockType = $blockTypeMap[(int) $pageBlock->block_type_id] ?? null;
                $existingErrors = $blockErrors['existing_' . (int) $pageBlock->id] ?? [];
                ?>
                <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="stat-card admin-form">
                    <input type="hidden" name="_action" value="update_block">
                    <input type="hidden" name="block_id" value="<?= (int) $pageBlock->id ?>">

                    <p>
                        <strong>Block #<?= (int) $pageBlock->id ?></strong>
                        <?php if ($blockType !== null): ?>
                            <span> - <?= htmlspecialchars((string) $blockType->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </p>

                    <label>
                        Block Type
                        <select name="block_type_id" required>
                            <?php foreach ($blockTypes as $type): ?>
                                <option value="<?= (int) $type->id ?>" <?= (int) $type->id === (int) $pageBlock->block_type_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $type->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($existingErrors['block_type_id'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['block_type_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Sort Order
                        <input type="number" name="sort_order" value="<?= (int) $pageBlock->sort_order ?>" required>
                        <?php if (!empty($existingErrors['sort_order'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['sort_order'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Props JSON
                        <textarea name="props_json" rows="6"><?= htmlspecialchars((string) $pageBlock->props_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                        <?php if (!empty($existingErrors['props_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['props_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Bindings JSON
                        <textarea name="bindings_json" rows="6"><?= htmlspecialchars((string) $pageBlock->bindings_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                        <?php if (!empty($existingErrors['bindings_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $existingErrors['bindings_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <div class="form-actions">
                        <button type="submit">Save Block</button>
                    </div>
                </form>

                <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="inline-form" onsubmit="return confirm('Delete this block instance?');">
                    <input type="hidden" name="_action" value="delete_block">
                    <input type="hidden" name="block_id" value="<?= (int) $pageBlock->id ?>">
                    <button type="submit" class="link-button">Delete Block</button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <h4>Add New Block</h4>
        <form method="post" action="/admin/pages/<?= (int) $page->id ?>/edit" class="stat-card admin-form">
            <input type="hidden" name="_action" value="add_block">

            <label>
                Block Type
                <select name="block_type_id" required>
                    <option value="">Select a block type</option>
                    <?php foreach ($blockTypes as $type): ?>
                        <option value="<?= (int) $type->id ?>" <?= (string) ($newBlockForm['block_type_id'] ?? '') === (string) $type->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $type->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($blockErrors['block_type_id'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['block_type_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Sort Order
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string) ($newBlockForm['sort_order'] ?? '0'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                <?php if (!empty($blockErrors['sort_order'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['sort_order'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Props JSON
                <textarea name="props_json" rows="6"><?= htmlspecialchars((string) ($newBlockForm['props_json'] ?? '{}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                <?php if (!empty($blockErrors['props_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['props_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <label>
                Bindings JSON
                <textarea name="bindings_json" rows="6"><?= htmlspecialchars((string) ($newBlockForm['bindings_json'] ?? '{}'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                <?php if (!empty($blockErrors['bindings_json'])): ?><small class="field-error"><?= htmlspecialchars((string) $blockErrors['bindings_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
            </label>

            <div class="form-actions">
                <button type="submit">Add Block</button>
            </div>
        </form>
    </section>
</section>
