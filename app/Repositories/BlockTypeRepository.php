<?php

namespace App\Repositories;

use App\Models\BlockType;

class BlockTypeRepository extends BaseRepository
{
    protected string $table = 'block_types';
    protected string $modelClass = BlockType::class;

    public function findByKey(string $key): ?BlockType
    {
        return $this->findBy('key', $key);
    }

    public function getAllByName(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
}
