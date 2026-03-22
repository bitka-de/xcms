<section class="admin-page-header">
    <h2>Block Types</h2>
    <p>Manage reusable blocks used across pages.</p>
    <p><a href="/admin/block-types/create">Create New Block Type</a></p>
</section>

<section class="admin-grid">
    <div class="stat-card">
        <?php if (empty($blockTypes)): ?>
            <p>No block types found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($blockTypes as $blockType): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $blockType->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $blockType->key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($blockType->updated_at ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td>
                            <a href="/admin/block-types/<?= (int) $blockType->id ?>/edit">Edit</a>
                            <form method="post" action="/admin/block-types" class="inline-form" onsubmit="return confirm('Delete this block type?');">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $blockType->id ?>">
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
