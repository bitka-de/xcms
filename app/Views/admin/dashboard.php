<section class="admin-page-header">
    <h2>Dashboard</h2>
    <p>Quick overview of your CMS content and configuration.</p>
</section>

<section class="admin-grid stats-grid">
    <article class="stat-card">
        <h3>Pages</h3>
        <p class="stat-value"><?= (int) ($stats['pages'] ?? 0) ?></p>
        <a href="/admin/pages">Manage pages</a>
    </article>

    <article class="stat-card">
        <h3>Public Pages</h3>
        <p class="stat-value"><?= (int) ($stats['public_pages'] ?? 0) ?></p>
        <a href="/admin/pages">Review visibility</a>
    </article>

    <article class="stat-card">
        <h3>Block Types</h3>
        <p class="stat-value"><?= (int) ($stats['block_types'] ?? 0) ?></p>
        <a href="/admin/block-types">Manage block types</a>
    </article>

    <article class="stat-card">
        <h3>Collections</h3>
        <p class="stat-value"><?= (int) ($stats['collections'] ?? 0) ?></p>
        <a href="/admin/collections">Manage collections</a>
    </article>

    <article class="stat-card">
        <h3>Media</h3>
        <p class="stat-value"><?= (int) ($stats['media'] ?? 0) ?></p>
        <a href="/admin/media">Manage media</a>
    </article>

    <article class="stat-card">
        <h3>Media Folders</h3>
        <p class="stat-value"><?= (int) ($stats['media_folders'] ?? 0) ?></p>
        <a href="/admin/media/folders">Manage folders</a>
    </article>

    <article class="stat-card">
        <h3>Design Settings</h3>
        <p class="stat-value"><?= (int) ($stats['design_settings'] ?? 0) ?></p>
        <a href="/admin/design">Edit design settings</a>
    </article>
</section>
