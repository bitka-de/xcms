<?php

use App\Core\Database;

$pdo = Database::connection();
$now = date('c');

$select = $pdo->prepare('SELECT id FROM pages WHERE slug = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO pages (slug, title, description, visibility, seo_title, seo_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$update = $pdo->prepare('UPDATE pages SET title = ?, description = ?, visibility = ?, seo_title = ?, seo_description = ?, updated_at = ? WHERE slug = ?');

$slug = 'home';
$select->execute([$slug]);
$existingId = $select->fetchColumn();

if ($existingId !== false) {
    $update->execute([
        'xcms Demo Homepage',
        'A simple local-first CMS demo homepage.',
        'public',
        'xcms Demo Homepage',
        'A block-based CMS demo homepage rendered from seeded content.',
        $now,
        $slug,
    ]);
} else {
    $insert->execute([
        $slug,
        'xcms Demo Homepage',
        'A simple local-first CMS demo homepage.',
        'public',
        'xcms Demo Homepage',
        'A block-based CMS demo homepage rendered from seeded content.',
        $now,
        $now,
    ]);
}
