<?php
$selectedFolderId = isset($selectedFolderId) ? (int) $selectedFolderId : 0;
$selectedFolderName = isset($selectedFolderName) ? (string) $selectedFolderName : '';
$selectedParentId = isset($selectedParentId) && is_int($selectedParentId) ? $selectedParentId : null;
$selectedParentName = isset($selectedParentName) ? (string) $selectedParentName : '';
$selectedFolderMedia = isset($selectedFolderMedia) && is_array($selectedFolderMedia) ? $selectedFolderMedia : [];
$explorerFolders = isset($explorerFolders) && is_array($explorerFolders) ? $explorerFolders : $folders;

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $size = (float) $bytes;
    $idx = 0;
    while ($size >= 1024 && $idx < count($units) - 1) {
        $size /= 1024;
        $idx++;
    }

    $precision = $idx <= 1 ? 0 : 1;
    return number_format($size, $precision, '.', ',') . ' ' . $units[$idx];
};
?>

<section class="admin-page-header media-folders-header">
    <div>
        <h2>Media Folders</h2>
        <p>Browse folders like a file explorer. Click a folder to open its files.</p>
    </div>
    <a class="media-header-btn" href="/admin/media">Back to Media Library</a>
</section>

<section class="admin-grid media-folder-layout">
    <form method="post" action="/admin/media/folders/create" class="stat-card admin-form media-folder-create-card">
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

    <article class="stat-card media-folder-explorer-card">
        <header class="media-folder-explorer-head">
            <h3><?= $selectedFolderId > 0 ? 'Subfolders' : 'Folders' ?></h3>
            <span><?= count($explorerFolders) ?> total</span>
        </header>

        <?php if ($selectedFolderId > 0): ?>
            <div class="media-folder-breadcrumbs">
                <a href="/admin/media/folders">Root</a>
                <span>/</span>
                <?php if ($selectedParentId !== null): ?>
                    <a href="/admin/media/folders?folder_id=<?= $selectedParentId ?>"><?= htmlspecialchars($selectedParentName !== '' ? $selectedParentName : 'Parent', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
                    <span>/</span>
                <?php endif; ?>
                <strong><?= htmlspecialchars($selectedFolderName !== '' ? $selectedFolderName : 'Folder', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
            </div>
        <?php endif; ?>

        <?php if (count($explorerFolders) > 1): ?>
            <p class="media-folder-dnd-hint">Drag folders to reorder them.</p>
            <form method="post" action="/admin/media/folders/reorder" id="folder-reorder-form" class="media-folder-reorder-form" novalidate>
                <input type="hidden" name="parent_id" value="<?= $selectedFolderId > 0 ? $selectedFolderId : '' ?>">
                <input type="hidden" name="order_ids" id="folder-reorder-order" value="">
            </form>
        <?php endif; ?>

        <?php if (empty($explorerFolders)): ?>
            <p><?= $selectedFolderId > 0 ? 'No subfolders in this folder yet.' : 'No folders created yet.' ?></p>
        <?php else: ?>
            <div class="media-folder-grid" role="list">
                <?php foreach ($explorerFolders as $folder): ?>
                    <?php
                    $id = (int) $folder->id;
                    $mediaCount = (int) ($mediaCountByFolder[$id] ?? 0);
                    $childCount = (int) ($childCountByFolder[$id] ?? 0);
                    $isActive = $selectedFolderId === $id;
                    ?>
                    <a
                        class="media-folder-card<?= $isActive ? ' is-active' : '' ?>"
                        role="listitem"
                        href="/admin/media/folders?folder_id=<?= $id ?>"
                        data-folder-id="<?= $id ?>"
                        draggable="true"
                        aria-label="Open folder <?= htmlspecialchars((string) $folder->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    >
                        <div class="media-folder-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" focusable="false">
                                <path d="M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                        </div>
                        <div class="media-folder-body">
                            <h4><?= htmlspecialchars((string) $folder->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h4>
                            <p><?= $mediaCount ?> <?= $mediaCount === 1 ? 'item' : 'items' ?> · <?= $childCount ?> <?= $childCount === 1 ? 'subfolder' : 'subfolders' ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php if ($selectedFolderId > 0): ?>
    <section class="stat-card media-folder-files-card">
        <header class="media-folder-files-head">
            <div>
                <h3><?= htmlspecialchars($selectedFolderName !== '' ? $selectedFolderName : 'Folder', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                <p><?= count($selectedFolderMedia) ?> <?= count($selectedFolderMedia) === 1 ? 'file' : 'files' ?></p>
            </div>
            <a class="media-header-btn" href="/admin/media/folders">Back to all folders</a>
        </header>

        <?php if ($selectedFolderMedia === []): ?>
            <p class="media-folder-files-empty">This folder is empty.</p>
        <?php else: ?>
            <div class="media-folder-files-grid" role="list">
                <?php foreach ($selectedFolderMedia as $media): ?>
                    <?php
                    $title = (string) $media->effectiveTitle();
                    $ext = strtoupper((string) $media->extension);
                    $mime = (string) ($media->mime_type ?? '');
                    $size = (int) ($media->file_size ?? $media->size_bytes ?? 0);
                    $path = (string) ($media->path ?? '');
                    ?>
                    <a class="media-folder-file-card" role="listitem" href="/admin/media/edit?id=<?= (int) $media->id ?>">
                        <div class="media-folder-file-preview" aria-hidden="true">
                            <?php if ($media->isImage()): ?>
                                <img src="<?= htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span><?= htmlspecialchars($ext !== '' ? $ext : 'FILE', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="media-folder-file-body">
                            <h4 title="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h4>
                            <p>
                                <?= htmlspecialchars($formatBytes($size), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                <?php if ($mime !== ''): ?>
                                    · <?= htmlspecialchars($mime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<details class="stat-card media-folder-manage-panel">
    <summary>Manage folders (rename, move, delete)</summary>
    <?php if (!empty($folders)): ?>
        <table class="admin-table" style="margin-top: 14px;">
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
</details>

<?php if (count($explorerFolders) > 1): ?>
    <script>
        (function () {
            const grid = document.querySelector('.media-folder-grid');
            const form = document.getElementById('folder-reorder-form');
            const orderInput = document.getElementById('folder-reorder-order');

            if (!grid || !form || !orderInput) {
                return;
            }

            let dragged = null;
            let orderChanged = false;
            let suppressClickUntil = 0;

            const cards = Array.from(grid.querySelectorAll('.media-folder-card[data-folder-id]'));
            const indexOfCard = (card) => Array.from(grid.children).indexOf(card);

            const collectOrder = () => Array.from(grid.querySelectorAll('.media-folder-card[data-folder-id]'))
                .map((el) => el.getAttribute('data-folder-id'))
                .filter((id) => typeof id === 'string' && id !== '')
                .join(',');

            cards.forEach((card) => {
                card.addEventListener('dragstart', (event) => {
                    dragged = card;
                    orderChanged = false;
                    card.classList.add('is-dragging');
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                    }
                });

                card.addEventListener('dragend', () => {
                    card.classList.remove('is-dragging');

                    if (orderChanged) {
                        suppressClickUntil = Date.now() + 350;
                        orderInput.value = collectOrder();
                        form.submit();
                    }

                    dragged = null;
                });

                card.addEventListener('click', (event) => {
                    if (Date.now() < suppressClickUntil) {
                        event.preventDefault();
                    }
                });
            });

            grid.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!dragged) {
                    return;
                }

                const target = event.target.closest('.media-folder-card[data-folder-id]');
                if (!target || target === dragged) {
                    return;
                }

                const draggedIndex = indexOfCard(dragged);
                const targetIndex = indexOfCard(target);
                if (draggedIndex < 0 || targetIndex < 0 || draggedIndex === targetIndex) {
                    return;
                }

                if (draggedIndex < targetIndex) {
                    grid.insertBefore(dragged, target.nextSibling);
                } else {
                    grid.insertBefore(dragged, target);
                }

                orderChanged = true;
            });
        })();
    </script>
<?php endif; ?>