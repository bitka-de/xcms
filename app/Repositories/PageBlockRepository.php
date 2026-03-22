<?php

namespace App\Repositories;

use App\Models\PageBlock;

class PageBlockRepository extends BaseRepository
{
    protected string $table = 'page_blocks';
    protected string $modelClass = PageBlock::class;

    public function findByPageIdOrdered(int $pageId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE page_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$pageId]);
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function findByPageId(int $pageId): array
    {
        return $this->findByPageIdOrdered($pageId);
    }

    public function deleteByPageId(int $pageId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE page_id = ?");
        return $stmt->execute([$pageId]);
    }

    public function countByPageId(int $pageId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE page_id = ?");
        $stmt->execute([$pageId]);
        return (int)$stmt->fetchColumn();
    }

    public function getMaxSortOrderForPage(int $pageId): int
    {
        $stmt = $this->db->prepare("SELECT MAX(sort_order) FROM {$this->table} WHERE page_id = ?");
        $stmt->execute([$pageId]);
        return (int)($stmt->fetchColumn() ?? 0);
    }
}
