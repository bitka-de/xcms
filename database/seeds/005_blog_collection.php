<?php

use App\Core\Database;

$pdo = Database::connection();
$now = date('c');

$schema = json_encode([
    'fields' => [
        ['name' => 'title', 'type' => 'text', 'required' => true],
        ['name' => 'excerpt', 'type' => 'textarea', 'required' => true],
        ['name' => 'body', 'type' => 'textarea', 'required' => true],
        ['name' => 'author', 'type' => 'text', 'required' => true],
        ['name' => 'published_at', 'type' => 'text', 'required' => true],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$selectCollection = $pdo->prepare('SELECT id FROM collections WHERE key = ? LIMIT 1');
$insertCollection = $pdo->prepare('INSERT INTO collections (name, key, description, schema_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
$updateCollection = $pdo->prepare('UPDATE collections SET name = ?, description = ?, schema_json = ?, updated_at = ? WHERE key = ?');

$selectCollection->execute(['blog']);
$collectionId = $selectCollection->fetchColumn();

if ($collectionId !== false) {
    $updateCollection->execute([
        'Blog',
        'Example blog collection for the seeded demo.',
        $schema,
        $now,
        'blog',
    ]);
} else {
    $insertCollection->execute([
        'Blog',
        'blog',
        'Example blog collection for the seeded demo.',
        $schema,
        $now,
        $now,
    ]);
    $collectionId = $pdo->lastInsertId();
}

$entries = [
    [
        'title' => 'Welcome to xcms',
        'excerpt' => 'A quick overview of the local-first CMS demo system.',
        'body' => 'xcms is seeded with a homepage, reusable blocks, design settings, and this blog collection so you can verify the full content flow immediately after setup.',
        'author' => 'Jane Publisher',
        'published_at' => '2026-03-22',
        'status' => 'published',
    ],
    [
        'title' => 'Creating reusable block types',
        'excerpt' => 'Define HTML, CSS, and JS once and reuse blocks across multiple pages.',
        'body' => 'Block types in xcms store their markup, scoped styles, and optional JavaScript. Page blocks then provide JSON props and bindings to render each instance.',
        'author' => 'John Architect',
        'published_at' => '2026-03-23',
        'status' => 'published',
    ],
];

$selectEntry = $pdo->prepare('SELECT id FROM collection_entries WHERE collection_id = ? AND data_json LIKE ? LIMIT 1');
$insertEntry = $pdo->prepare('INSERT INTO collection_entries (collection_id, data_json, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
$updateEntry = $pdo->prepare('UPDATE collection_entries SET data_json = ?, status = ?, updated_at = ? WHERE id = ?');

foreach ($entries as $entry) {
    $dataJson = json_encode([
        'title' => $entry['title'],
        'excerpt' => $entry['excerpt'],
        'body' => $entry['body'],
        'author' => $entry['author'],
        'published_at' => $entry['published_at'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $selectEntry->execute([(int) $collectionId, '%"title":"' . $entry['title'] . '"%']);
    $existingId = $selectEntry->fetchColumn();

    if ($existingId !== false) {
        $updateEntry->execute([$dataJson, $entry['status'], $now, $existingId]);
        continue;
    }

    $insertEntry->execute([(int) $collectionId, $dataJson, $entry['status'], $now, $now]);
}
