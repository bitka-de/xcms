<?php

use App\Core\Database;

$pdo = Database::connection();
$now = date('c');

$pageId = $pdo->query("SELECT id FROM pages WHERE slug = 'home' LIMIT 1")->fetchColumn();
$heroTypeId = $pdo->query("SELECT id FROM block_types WHERE key = 'hero' LIMIT 1")->fetchColumn();
$textTypeId = $pdo->query("SELECT id FROM block_types WHERE key = 'text' LIMIT 1")->fetchColumn();

if ($pageId === false || $heroTypeId === false || $textTypeId === false) {
    throw new RuntimeException('Homepage or required block types are missing for homepage block seeds.');
}

$definitions = [
    [
        'page_id' => (int) $pageId,
        'block_type_id' => (int) $heroTypeId,
        'sort_order' => 10,
        'props_json' => json_encode([
            'eyebrow' => 'Local-first publishing',
            'title' => 'Build structured pages from reusable blocks.',
            'text' => 'xcms ships with a tiny block-based rendering system, SQLite persistence, and a clean admin UI so you can manage content without a framework.',
            'button_text' => 'Open Admin',
            'button_url' => '/admin',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'bindings_json' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ],
    [
        'page_id' => (int) $pageId,
        'block_type_id' => (int) $textTypeId,
        'sort_order' => 20,
        'props_json' => json_encode([
            'heading' => 'Seeded Demo Content',
            'body' => 'This homepage, its blocks, design settings, and blog collection were all inserted by the seed infrastructure. Run php setup.php and the CMS is immediately usable for local development.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'bindings_json' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ],
];

$select = $pdo->prepare('SELECT id FROM page_blocks WHERE page_id = ? AND sort_order = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO page_blocks (page_id, block_type_id, sort_order, props_json, bindings_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
$update = $pdo->prepare('UPDATE page_blocks SET block_type_id = ?, props_json = ?, bindings_json = ?, updated_at = ? WHERE id = ?');

foreach ($definitions as $definition) {
    $select->execute([$definition['page_id'], $definition['sort_order']]);
    $existingId = $select->fetchColumn();

    if ($existingId !== false) {
        $update->execute([
            $definition['block_type_id'],
            $definition['props_json'],
            $definition['bindings_json'],
            $now,
            $existingId,
        ]);
        continue;
    }

    $insert->execute([
        $definition['page_id'],
        $definition['block_type_id'],
        $definition['sort_order'],
        $definition['props_json'],
        $definition['bindings_json'],
        $now,
        $now,
    ]);
}
