<?php

namespace App\Repositories;

use App\Models\Page;

class PageRepository extends BaseRepository
{
    protected string $table = 'pages';
    protected string $modelClass = Page::class;

    public function findBySlug(string $slug): ?Page
    {
        return $this->findBy('slug', $slug);
    }

    public function findPublicBySlug(string $slug): ?Page
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? AND visibility = ?");
        $stmt->execute([$slug, 'public']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function findPublic(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE visibility = 'public' ORDER BY created_at DESC");
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function countPublic(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE visibility = 'public'");
        return (int)$stmt->fetchColumn();
    }
}
