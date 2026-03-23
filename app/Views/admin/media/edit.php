<?php
$isImage   = $media->isImage();
$isVideo   = $media->isVideo();
$isAudio   = method_exists($media, 'isAudio') && $media->isAudio();
$extension = strtolower((string) ($media->extension ?? ''));
$type      = strtolower((string) ($media->type ?? 'document'));
$publicPath = (string) ($media->path ?? '');
$attributionRequired = !empty($form['attribution_required'])
    || (empty($form) && (int) ($media->attribution_required ?? 0) === 1);

$currentTagNames = [];
foreach (($media->tags ?? []) as $tag) {
    if (!is_object($tag) || !property_exists($tag, 'name')) {
        continue;
    }
    $name = trim((string) $tag->name);
    if ($name !== '') {
        $currentTagNames[] = $name;
    }
}
?>

<section class="admin-page-header media-edit-header">
    <h2>Edit Media</h2>
    <p>Update organization, metadata, and rights details for this file.</p>
</section>

<section class="admin-grid media-edit-page">
    <div class="media-edit-layout">

        <!-- Preview panel -->
        <aside class="media-edit-preview-panel">
            <div class="media-edit-preview-shell">
                <?php if ($isImage): ?>
                    <img
                        src="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars((string) ($media->alt_text ?? $media->effectiveTitle()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        loading="lazy"
                    >
                <?php elseif ($isVideo): ?>
                    <video class="media-edit-video" src="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" controls preload="metadata"></video>
                <?php elseif ($isAudio): ?>
                    <div class="media-edit-audio-hero" aria-hidden="true">
                        <span class="media-edit-audio-icon">♪</span>
                        <span>Audio File</span>
                    </div>
                    <audio class="media-edit-audio-player" src="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" controls preload="none"></audio>
                <?php else: ?>
                    <div class="media-edit-doc-hero">
                        <strong><?= htmlspecialchars(strtoupper($extension !== '' ? $extension : 'FILE'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                        <a href="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer">Open document ↗</a>
                    </div>
                <?php endif; ?>

                <span class="media-edit-type-badge media-edit-type-<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-hidden="true">
                    <?= htmlspecialchars(ucfirst($type), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </span>
            </div>

            <dl class="media-edit-file-facts">
                <div>
                    <dt>Original filename</dt>
                    <dd title="<?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $media->original_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Stored as</dt>
                    <dd><?= htmlspecialchars((string) $media->stored_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>MIME type</dt>
                    <dd><?= htmlspecialchars((string) $media->mime_type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Public URL</dt>
                    <dd><a href="<?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noreferrer"><?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></dd>
                </div>
            </dl>

            <p class="media-edit-helper-text">Path for use in block props or collection JSON</p>
            <p class="media-edit-path"><code><?= htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></p>
        </aside>

        <!-- Edit form -->
        <form method="post" action="/admin/media/edit?id=<?= (int) $media->id ?>" class="media-edit-form">
            <input type="hidden" name="id" value="<?= (int) $media->id ?>">

            <!-- Organization -->
            <section class="media-edit-group">
                <header>
                    <h3>Organization</h3>
                    <p>Control where this file appears and how it is grouped.</p>
                </header>

                <div class="media-edit-fields media-edit-fields-2col">
                    <label>
                        Folder
                        <select name="folder_id">
                            <option value="">Root (no folder)</option>
                            <?php foreach ($folderTree as $folder): ?>
                                <?php $indent = str_repeat('– ', (int) $folder['depth']); ?>
                                <option value="<?= (int) $folder['id'] ?>" <?= (string) ($form['folder_id'] ?? '') === (string) $folder['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($indent . $folder['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['folder_id'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['folder_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Tags
                        <input type="text" name="tags" value="<?= htmlspecialchars((string) ($form['tags'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="hero, brand, homepage">
                        <small class="media-edit-inline-help">Comma-separated. Auto-created, duplicates removed on save.</small>
                    </label>
                </div>

                <?php if ($currentTagNames !== []): ?>
                    <div class="media-edit-tag-list" aria-label="Current tags">
                        <?php foreach ($currentTagNames as $tagName): ?>
                            <span><?= htmlspecialchars($tagName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Metadata -->
            <section class="media-edit-group">
                <header>
                    <h3>Metadata</h3>
                    <p>Used in the admin, content references, and accessibility contexts.</p>
                </header>

                <div class="media-edit-fields media-edit-fields-2col">
                    <label>
                        Display Filename *
                        <input type="text" name="filename" value="<?= htmlspecialchars((string) ($form['filename'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['filename'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['filename'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Title *
                        <input type="text" name="title" value="<?= htmlspecialchars((string) ($form['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label class="media-edit-field-full">
                        Alt Text
                        <input type="text" name="alt_text" value="<?= htmlspecialchars((string) ($form['alt_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Describe the visual content for screen readers">
                    </label>
                </div>
            </section>

            <!-- Copyright & License -->
            <section class="media-edit-group">
                <header>
                    <h3>Copyright &amp; License</h3>
                    <p>Track rights and source details for safe reuse across pages and collections.</p>
                </header>

                <div class="media-edit-fields media-edit-fields-2col">
                    <label class="media-edit-field-full">
                        Copyright Text
                        <textarea name="copyright_text" rows="2" placeholder="e.g. © 2026 Example Studio. All rights reserved."><?= htmlspecialchars((string) ($form['copyright_text'] ?? $media->copyright_text ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                    </label>

                    <label>
                        Copyright Author
                        <input type="text" name="copyright_author" value="<?= htmlspecialchars((string) ($form['copyright_author'] ?? $media->copyright_author ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </label>

                    <label>
                        License Name
                        <input type="text" name="license_name" value="<?= htmlspecialchars((string) ($form['license_name'] ?? $media->license_name ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="e.g. CC BY 4.0">
                    </label>

                    <label>
                        License URL
                        <input type="url" name="license_url" value="<?= htmlspecialchars((string) ($form['license_url'] ?? $media->license_url ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="https://">
                        <?php if (!empty($errors['license_url'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['license_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label>
                        Source URL
                        <input type="url" name="source_url" value="<?= htmlspecialchars((string) ($form['source_url'] ?? $media->source_url ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="https://">
                        <?php if (!empty($errors['source_url'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['source_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                    </label>

                    <label class="media-edit-field-full">
                        Usage Notes
                        <textarea name="usage_notes" rows="3" placeholder="Internal notes on restrictions or required attribution format."><?= htmlspecialchars((string) ($form['usage_notes'] ?? $media->usage_notes ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                    </label>

                    <label class="media-edit-checkbox media-edit-field-full">
                        <input type="checkbox" name="attribution_required" value="1" <?= $attributionRequired ? 'checked' : '' ?>>
                        Attribution required when this media is used publicly
                    </label>
                </div>
            </section>

            <!-- File Maintenance -->
            <section class="media-edit-group">
                <header>
                    <h3>File Maintenance</h3>
                    <p>Optional operations that affect the physical file on disk.</p>
                </header>

                <div class="media-edit-fields">
                    <label class="media-edit-checkbox">
                        <input type="checkbox" name="rename_physical" value="1" <?= !empty($form['rename_physical']) ? 'checked' : '' ?>>
                        Rename physical file on disk (this changes the stored path)
                    </label>
                </div>
            </section>

            <div class="form-actions media-edit-actions">
                <button type="submit">Save Changes</button>
                <a href="/admin/media">← Back to library</a>
            </div>
        </form>
    </div>
</section>
