<?php
$formatSize = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $size = (float) $bytes;
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }

    return number_format($size, $unitIndex === 0 ? 0 : 1) . ' ' . $units[$unitIndex];
};
?>

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
        <div class="media-filter-grid">
            <label>
                Search
                <input type="text" name="q" value="<?= htmlspecialchars((string) ($searchQuery ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Filename, title, MIME, tags, license...">
            </label>

            <label>
                Folder
                <select name="folder_id">
                    <option value="">All folders</option>
                    <?php foreach ($folderTree as $folder): ?>
                        <?php $indent = str_repeat('-- ', (int) $folder['depth']); ?>
                        <option value="<?= (int) $folder['id'] ?>" <?= (int) ($selectedFolderId ?? 0) === (int) $folder['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Tag
                <select name="tag_id">
                    <option value="">All tags</option>
                    <?php foreach (($availableTags ?? []) as $tag): ?>
                        <option value="<?= (int) $tag->id ?>" <?= (int) ($selectedTagId ?? 0) === (int) $tag->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $tag->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Type
                <select name="type">
                    <option value="">All types</option>
                    <option value="image" <?= (string) ($selectedType ?? '') === 'image' ? 'selected' : '' ?>>Image</option>
                    <option value="video" <?= (string) ($selectedType ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                    <option value="document" <?= (string) ($selectedType ?? '') === 'document' ? 'selected' : '' ?>>Document</option>
                </select>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit">Apply Filters</button>
            <a href="/admin/media">Reset</a>
        </div>
    </form>

    <?php if (empty($mediaItems)): ?>
        <div class="stat-card media-empty-state">
            <p>No media uploaded yet.</p>
            <a href="/admin/media/upload">Upload your first file</a>
        </div>
    <?php else: ?>
        <div class="media-grid" role="list">
            <?php foreach ($mediaItems as $media): ?>
                <?php
                $isImage = $media->isImage();
                $isVideo = $media->isVideo();
                $isPdf = strtolower((string) $media->extension) === 'pdf';

                $badgeLabel = 'File';
                $badgeClass = 'media-badge-file';
                if ($isImage) {
                    $badgeLabel = 'Image';
                    $badgeClass = 'media-badge-image';
                } elseif ($isVideo) {
                    $badgeLabel = 'Video';
                    $badgeClass = 'media-badge-video';
                } elseif ($isPdf) {
                    $badgeLabel = 'PDF';
                    $badgeClass = 'media-badge-pdf';
                }

                $fileSize = (int) ($media->file_size ?? 0);
                if ($fileSize <= 0) {
                    $fileSize = (int) ($media->size_bytes ?? 0);
                }

                $title = (string) $media->effectiveTitle();
                $mimeType = (string) ($media->mime_type ?? $media->type ?? 'unknown');
                $folderName = (string) ($media->folder_name ?? 'Root');
                $path = (string) $media->path;
                $typeLabel = ucfirst((string) ($media->type ?? 'file'));

                $tags = [];
                foreach (($media->tags ?? []) as $tag) {
                    if (is_object($tag) && property_exists($tag, 'name')) {
                        $name = trim((string) $tag->name);
                        if ($name !== '') {
                            $tags[] = $name;
                        }
                    }
                }

                $copyrightParts = [];
                if (!empty($media->copyright_author)) {
                    $copyrightParts[] = (string) $media->copyright_author;
                }
                if (!empty($media->license_name)) {
                    $copyrightParts[] = (string) $media->license_name;
                }
                ?>
                <article class="media-card" role="listitem">
                    <div class="media-preview-wrap">
                        <a class="media-preview-link" href="/admin/media/edit?id=<?= (int) $media->id ?>" aria-label="Edit <?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            <div class="media-preview" aria-hidden="true">
                                <?php if ($isImage): ?>
                                    <img src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($media->alt_text ?? $title), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" loading="lazy">
                                <?php elseif ($isVideo): ?>
                                    <video class="media-video" src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" muted preload="metadata" playsinline aria-label="Video preview"></video>
                                <?php elseif ($isPdf): ?>
                                    <div class="media-preview-file media-preview-pdf" aria-label="PDF file preview">PDF</div>
                                <?php else: ?>
                                    <div class="media-preview-file media-preview-generic" aria-label="File preview">FILE</div>
                                <?php endif; ?>
                            </div>
                            <span class="media-preview-gradient" aria-hidden="true"></span>
                            <span class="media-badge <?= $badgeClass ?>"><?= htmlspecialchars($badgeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        </a>

                        <div class="media-overlay" aria-hidden="true">
                            <a class="media-overlay-btn" href="/admin/media/edit?id=<?= (int) $media->id ?>">Edit</a>
                            <form method="post" action="/admin/media/delete" class="media-overlay-form" onsubmit="return confirm('Delete this media file and its physical file?');">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $media->id ?>">
                                <button type="submit" class="media-overlay-btn media-overlay-btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div class="media-info">
                        <h3 class="media-name" title="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                        <p class="media-meta-line"><?= htmlspecialchars($formatSize($fileSize), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> • <?= htmlspecialchars($typeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> • <?= htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <p class="media-meta-line">Folder: <?= htmlspecialchars($folderName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <?php if ($tags !== []): ?>
                            <p class="media-meta-line">Tags: <?= htmlspecialchars(implode(', ', $tags), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($copyrightParts !== []): ?>
                            <p class="media-meta-line">Rights: <?= htmlspecialchars(implode(' • ', $copyrightParts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="media-actions">
                        <a class="media-action-btn" href="/admin/media/edit?id=<?= (int) $media->id ?>">Edit</a>
                        <form method="post" action="/admin/media/delete" class="media-action-form" onsubmit="return confirm('Delete this media file and its physical file?');">
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $media->id ?>">
                            <button type="submit" class="media-action-btn media-action-btn-danger">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>