<section class="admin-page-header">
    <h2>Media Library</h2>
    <p>Upload and organize images, videos, and documents. Reuse paths in pages, blocks, and collections.</p>
    <p>
        <a href="/admin/media/upload">Upload Media</a>
        <a href="/admin/media/folders">Manage Folders</a>
    </p>
</section>

<section class="admin-grid">
    <form method="get" action="/admin/media" class="stat-card admin-form">
        <label>
            Filter by Folder
            <select name="folder_id" onchange="this.form.submit()">
                <option value="">All folders</option>
                <?php foreach ($folderTree as $folder): ?>
                    <?php $indent = str_repeat('-- ', (int) $folder['depth']); ?>
                    <option value="<?= (int) $folder['id'] ?>" <?= (int) $selectedFolderId === (int) $folder['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

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
                            <img src="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($media->alt_text ?? $media->effectiveTitle()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <?php elseif ($media->isVideo()): ?>
                            <video class="media-video" src="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" controls preload="metadata"></video>
                        <?php else: ?>
                            <div class="media-preview-file">PDF</div>
                        <?php endif; ?>
                    </div>

                    <div class="media-meta">
                        <strong><?= htmlspecialchars((string) $media->effectiveTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars((string) strtoupper($media->extension), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, <?= number_format(((int) $media->file_size) / 1024, 1) ?> KB</span>
                        <span>Type: <?= htmlspecialchars((string) $media->type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <span>MIME: <?= htmlspecialchars((string) $media->mime_type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <span>Folder: <?= htmlspecialchars((string) ($media->folder_name ?? 'Root'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <?php if ($media->width !== null && $media->height !== null): ?>
                            <span><?= (int) $media->width ?> x <?= (int) $media->height ?> px</span>
                        <?php endif; ?>
                    </div>

                    <label>
                        Public URL
                        <input class="media-url" type="text" value="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" readonly onclick="this.select();">
                    </label>

                    <div class="form-actions">
                        <a href="/admin/media/edit?id=<?= (int) $media->id ?>">Edit</a>
                        <a href="<?= htmlspecialchars((string) $media->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer">Open</a>
                        <form method="post" action="/admin/media/delete" class="inline-form" onsubmit="return confirm('Delete this media file and its physical file?');">
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