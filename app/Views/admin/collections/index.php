<section class="admin-page-header">
    <h2>Collections</h2>
    <p>Manage structured content collections.</p>
    <p><a href="/admin/collections/create">Create New Collection</a></p>
</section>

<section class="admin-grid">
    <div class="stat-card">
        <?php if (empty($collections)): ?>
            <p>No collections found.</p>
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
                <?php foreach ($collections as $collection): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $collection->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $collection->key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($collection->updated_at ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td>
                            <a href="/admin/collections/<?= (int) $collection->id ?>/edit">Edit</a>
                            <form method="post" action="/admin/collections" class="inline-form" onsubmit="return confirm('Delete this collection?');">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $collection->id ?>">
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
