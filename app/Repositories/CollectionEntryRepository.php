<?php

namespace App\Repositories;

use App\Models\CollectionEntry;

class CollectionEntryRepository extends BaseRepository
{
    protected string $table = 'collection_entries';
    protected string $modelClass = CollectionEntry::class;

    public function findByCollectionId(int $collectionId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE collection_id = ? ORDER BY created_at DESC");
        $stmt->execute([$collectionId]);
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function findByCollectionIdPaginated(int $collectionId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE collection_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$collectionId, $limit, $offset]);
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function countByCollectionId(int $collectionId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE collection_id = ?");
        $stmt->execute([$collectionId]);
        return (int)$stmt->fetchColumn();
    }

    public function deleteByCollectionId(int $collectionId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE collection_id = ?");
        return $stmt->execute([$collectionId]);
    }
}
