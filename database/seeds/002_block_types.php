<?php

use App\Core\Database;

$pdo = Database::connection();
$now = date('c');

$heroMetadata = [
    'schema_json' => json_encode([
        'fields' => [
            'eyebrow' => ['type' => 'text'],
            'title' => ['type' => 'text'],
            'text' => ['type' => 'textarea'],
            'button_text' => ['type' => 'text'],
            'button_url' => ['type' => 'text'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'preview_image' => '/assets/hero-preview.png',
];

$textMetadata = [
    'schema_json' => json_encode([
        'fields' => [
            'heading' => ['type' => 'text'],
            'body' => ['type' => 'textarea'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'preview_image' => '/assets/text-preview.png',
];

$blockTypes = [
    [
        'name' => 'Hero',
        'key' => 'hero',
        'description' => json_encode($heroMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'html_template' => '<section class="hero-block"><p class="hero-eyebrow">{{ eyebrow }}</p><h1>{{ title }}</h1><p class="hero-text">{{ text }}</p><a class="hero-button" href="{{ button_url }}">{{ button_text }}</a></section>',
        'css_template' => '.hero-block { max-width: var(--container_width); margin: 0 auto; padding: calc(var(--base_spacing) * 5) var(--base_spacing); background: linear-gradient(135deg, var(--secondary_color), #1e293b); color: #ffffff; border-radius: var(--border_radius); } .hero-eyebrow { margin: 0 0 12px; text-transform: uppercase; letter-spacing: 0.12em; font-size: 0.8rem; opacity: 0.75; } .hero-block h1 { margin: 0 0 16px; font-size: clamp(2.5rem, 6vw, 4.5rem); line-height: 1; } .hero-text { max-width: 52rem; margin: 0 0 24px; font-size: 1.1rem; line-height: 1.7; } .hero-button { display: inline-block; padding: 14px 20px; background: var(--primary_color); color: #ffffff; text-decoration: none; border-radius: var(--border_radius); font-weight: 700; }',
        'js_template' => 'document.querySelectorAll(".hero-button").forEach(function(button){ button.addEventListener("mouseenter", function(){ button.style.opacity = "0.9"; }); button.addEventListener("mouseleave", function(){ button.style.opacity = "1"; }); });',
    ],
    [
        'name' => 'Text',
        'key' => 'text',
        'description' => json_encode($textMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'html_template' => '<section class="text-block"><h2>{{ heading }}</h2><p>{{ body }}</p></section>',
        'css_template' => '.text-block { max-width: var(--container_width); margin: calc(var(--base_spacing) * 2) auto; padding: 0 var(--base_spacing); } .text-block h2 { margin: 0 0 12px; color: var(--secondary_color); font-size: 2rem; } .text-block p { margin: 0; color: #334155; line-height: 1.8; font-size: 1rem; }',
        'js_template' => '',
    ],
];

$select = $pdo->prepare('SELECT id FROM block_types WHERE key = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO block_types (name, key, description, html_template, css_template, js_template, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$update = $pdo->prepare('UPDATE block_types SET name = ?, description = ?, html_template = ?, css_template = ?, js_template = ?, updated_at = ? WHERE key = ?');

foreach ($blockTypes as $blockType) {
    $select->execute([$blockType['key']]);
    $existingId = $select->fetchColumn();

    if ($existingId !== false) {
        $update->execute([
            $blockType['name'],
            $blockType['description'],
            $blockType['html_template'],
            $blockType['css_template'],
            $blockType['js_template'],
            $now,
            $blockType['key'],
        ]);
        continue;
    }

    $insert->execute([
        $blockType['name'],
        $blockType['key'],
        $blockType['description'],
        $blockType['html_template'],
        $blockType['css_template'],
        $blockType['js_template'],
        $now,
        $now,
    ]);
}
