<?php

namespace App\Repositories;

use App\Models\CollectionEntry;
use PDO;

class CollectionEntryRepository extends BaseRepository
{
    protected string $table = 'collection_entries';
    protected string $modelClass = CollectionEntry::class;

    public function __construct()
    {
        parent::__construct();
        $this->ensureStatusColumn();
    }

    public function findByCollectionId(int $collectionId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE collection_id = ? ORDER BY created_at DESC, id DESC");
        $stmt->execute([$collectionId]);
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function findByCollectionIdPaginated(int $collectionId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE collection_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $collectionId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
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

    private function ensureStatusColumn(): void
    {
        $columns = $this->db->query("PRAGMA table_info({$this->table})")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'status') {
                return;
            }
        }

        $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN status TEXT NOT NULL DEFAULT 'draft'");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_collection_entries_status ON {$this->table} (status)");
    }
}
