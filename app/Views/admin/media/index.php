<section class="admin-page-header">
    <h2>Media Library</h2>
    <p>Upload files once, then reuse their public URLs in page block props and collection entry JSON.</p>
    <p><a href="/admin/media/upload">Upload Media</a></p>
</section>

<section class="admin-grid">
    <?php if (empty($mediaItems)): ?>
        <div class="stat-card">
            <p>No media uploaded yet.</p>
        </div>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($mediaItems as $media): ?>
                <article class="stat-card media-card">
                    <div class="media-preview">
                        <?php if ($media->isImage()): ?>
                            <img src="<?= htmlspecialchars((string) $media->public_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($media->alt_text ?? $media->title), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="media-preview-file">PDF</div>
                        <?php endif; ?>
                    </div>

                    <div class="media-meta">
                        <strong><?= htmlspecialchars((string) $media->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars((string) strtoupper($media->extension), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, <?= number_format(((int) $media->size_bytes) / 1024, 1) ?> KB</span>
                        <?php if ($media->width !== null && $media->height !== null): ?>
                            <span><?= (int) $media->width ?> x <?= (int) $media->height ?> px</span>
                        <?php endif; ?>
                    </div>

                    <label>
                        Public URL
                        <input class="media-url" type="text" value="<?= htmlspecialchars((string) $media->public_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" readonly onclick="this.select();">
                    </label>

                    <div class="form-actions">
                        <a href="/admin/media/<?= (int) $media->id ?>/edit">Edit</a>
                        <a href="<?= htmlspecialchars((string) $media->public_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer">Open</a>
                        <form method="post" action="/admin/media" class="inline-form" onsubmit="return confirm('Delete this media file and its physical file?');">
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $media->id ?>">
                            <button type="submit" class="link-button">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>