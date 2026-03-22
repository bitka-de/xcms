<?php

use App\Core\Database;

$pdo = Database::connection();
$now = date('c');

$settings = [
    ['key' => 'primary_color', 'value' => '#2563eb', 'type' => 'color'],
    ['key' => 'secondary_color', 'value' => '#0f172a', 'type' => 'color'],
    ['key' => 'font_family', 'value' => 'Georgia, serif', 'type' => 'text'],
    ['key' => 'base_spacing', 'value' => '16px', 'type' => 'text'],
    ['key' => 'container_width', 'value' => '1100px', 'type' => 'text'],
    ['key' => 'border_radius', 'value' => '12px', 'type' => 'text'],
];

$select = $pdo->prepare('SELECT id FROM design_settings WHERE key = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO design_settings (key, value, type, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
$update = $pdo->prepare('UPDATE design_settings SET value = ?, type = ?, updated_at = ? WHERE key = ?');

foreach ($settings as $setting) {
    $select->execute([$setting['key']]);
    $existingId = $select->fetchColumn();

    if ($existingId !== false) {
        $update->execute([$setting['value'], $setting['type'], $now, $setting['key']]);
        continue;
    }

    $insert->execute([
        $setting['key'],
        $setting['value'],
        $setting['type'],
        $now,
        $now,
    ]);
}
