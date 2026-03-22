<section class="admin-page-header">
    <h2>Design Settings</h2>
    <p>Manage the global design tokens used across the site.</p>
</section>

<section class="admin-grid">
    <form method="post" action="/admin/design" class="stat-card admin-form">
        <label>
            Primary Color
            <input type="color" name="primary_color" value="<?= htmlspecialchars((string) ($settings['primary_color'] ?? '#2563eb'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if (!empty($errors['primary_color'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['primary_color'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Secondary Color
            <input type="color" name="secondary_color" value="<?= htmlspecialchars((string) ($settings['secondary_color'] ?? '#111827'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if (!empty($errors['secondary_color'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['secondary_color'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Font Family
            <input type="text" name="font_family" value="<?= htmlspecialchars((string) ($settings['font_family'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if (!empty($errors['font_family'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['font_family'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Base Spacing
            <input type="text" name="base_spacing" value="<?= htmlspecialchars((string) ($settings['base_spacing'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if (!empty($errors['base_spacing'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['base_spacing'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Container Width
            <input type="text" name="container_width" value="<?= htmlspecialchars((string) ($settings['container_width'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if (!empty($errors['container_width'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['container_width'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <label>
            Border Radius
            <input type="text" name="border_radius" value="<?= htmlspecialchars((string) ($settings['border_radius'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if (!empty($errors['border_radius'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['border_radius'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
        </label>

        <h3>Additional Settings</h3>
        <p>Add or edit extra key/value settings already stored in the database.</p>

        <?php foreach ($extraSettings as $index => $extra): ?>
            <div class="stat-card admin-form">
                <label>
                    Key
                    <input type="text" name="extra_key[]" value="<?= htmlspecialchars((string) ($extra['key'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="custom_variable">
                    <?php if (!empty($errors['extra_key_' . $index])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['extra_key_' . $index], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                </label>

                <label>
                    Value
                    <input type="text" name="extra_value[]" value="<?= htmlspecialchars((string) ($extra['value'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="24px">
                    <?php if (!empty($errors['extra_value_' . $index])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['extra_value_' . $index], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small><?php endif; ?>
                </label>

                <label>
                    Type
                    <?php $extraType = (string) ($extra['type'] ?? 'text'); ?>
                    <select name="extra_type[]">
                        <option value="text" <?= $extraType === 'text' ? 'selected' : '' ?>>text</option>
                        <option value="color" <?= $extraType === 'color' ? 'selected' : '' ?>>color</option>
                        <option value="size" <?= $extraType === 'size' ? 'selected' : '' ?>>size</option>
                    </select>
                </label>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit">Save Design Settings</button>
            <a href="/admin">Back to dashboard</a>
        </div>
    </form>
</section>
