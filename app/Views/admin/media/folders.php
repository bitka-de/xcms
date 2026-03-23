<section class="admin-page-header">
    <h2>Media Folders</h2>
    <p>Create and organize nested folders for media items.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/media/folders/create" class="stat-card admin-form">
        <h3>Create Folder</h3>

        <label>
            Name *
            <input type="text" name="name" required>
        </label>

        <label>
            Parent Folder
            <select name="parent_id">
                <option value="">Root</option>
                <?php foreach ($folderTree as $folder): ?>
                    <?php $indent = str_repeat('-- ', (int) $folder['depth']); ?>
                    <option value="<?= (int) $folder['id'] ?>"><?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-actions">
            <button type="submit">Create Folder</button>
        </div>
    </form>

    <div class="stat-card">
        <h3>Existing Folders</h3>

        <?php if (empty($folders)): ?>
            <p>No folders created yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Parent</th>
                    <th>Children</th>
                    <th>Media</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($folders as $folder): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $folder->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $folder->slug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($folder->parent_name ?? 'Root'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= (int) ($childCountByFolder[(int) $folder->id] ?? 0) ?></td>
                        <td><?= (int) ($mediaCountByFolder[(int) $folder->id] ?? 0) ?></td>
                        <td>
                            <details>
                                <summary>Edit</summary>
                                <form method="post" action="/admin/media/folders/edit" class="admin-form" style="margin-top: 8px;">
                                    <input type="hidden" name="id" value="<?= (int) $folder->id ?>">

                                    <label>
                                        Name
                                        <input type="text" name="name" value="<?= htmlspecialchars((string) $folder->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                                    </label>

                                    <label>
                                        Parent Folder
                                        <select name="parent_id">
                                            <option value="">Root</option>
                                            <?php foreach ($folderTree as $option): ?>
                                                <?php if ((int) $option['id'] === (int) $folder->id) { continue; } ?>
                                                <?php $indent = str_repeat('-- ', (int) $option['depth']); ?>
                                                <option value="<?= (int) $option['id'] ?>" <?= (int) ($folder->parent_id ?? 0) === (int) $option['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($indent . $option['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <div class="form-actions">
                                        <button type="submit">Save</button>
                                    </div>
                                </form>
                            </details>

                            <form method="post" action="/admin/media/folders/delete" class="inline-form" onsubmit="return confirm('Delete this folder? Deletion is blocked if it has children or media.');">
                                <input type="hidden" name="id" value="<?= (int) $folder->id ?>">
                                <button type="submit" class="link-button">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>