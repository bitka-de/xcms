<section class="admin-page-header">
    <h2>Pages</h2>
    <p>Manage your website pages and their visibility.</p>
    <p><a href="/admin/pages/create">Create New Page</a></p>
</section>

<section class="admin-grid">
    <div class="stat-card">
        <?php if (empty($pages)): ?>
            <p>No pages found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Visibility</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $page->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td>/<?= htmlspecialchars((string) $page->slug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $page->visibility, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td>
                            <a href="/admin/pages/<?= (int) $page->id ?>/edit">Edit</a>
                            <form method="post" action="/admin/pages" class="inline-form" onsubmit="return confirm('Delete this page?');">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $page->id ?>">
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
