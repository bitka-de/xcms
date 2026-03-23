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

    <article class="stat-card stat-card-media">
        <div class="stat-card-header">
            <h3>Media</h3>
            <span class="stat-card-badge"><?= (int) ($stats['media'] ?? 0) ?> files</span>
        </div>
        <div class="stat-card-media-content">
            <div class="stat-card-storage">
                <div class="stat-card-storage-label">
                    <span>Storage Used</span>
                    <span class="stat-card-storage-percent"><?= (int) ($stats['storage_percent'] ?? 0) ?>%</span>
                </div>
                <div class="stat-card-storage-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) ($stats['storage_percent'] ?? 0) ?>">
                    <span class="stat-card-storage-fill" style="width: <?= number_format((float) ($stats['storage_percent'] ?? 0), 2, '.', '') ?>%"></span>
                </div>
                <div class="stat-card-storage-info">
                    <span><?= htmlspecialchars((string) ($stats['storage_used'] ?? '0 B'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> used</span>
                    <span><?= htmlspecialchars((string) ($stats['storage_remaining'] ?? '5 GB'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> free</span>
                </div>
            </div>
        </div>
        <a href="/admin/media" class="stat-card-action">Manage media</a>
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
