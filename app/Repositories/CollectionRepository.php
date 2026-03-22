<?php

namespace App\Repositories;

use App\Models\CmsCollection;

class CollectionRepository extends BaseRepository
{
    protected string $table = 'collections';
    protected string $modelClass = CmsCollection::class;

    public function findByKey(string $key): ?CmsCollection
    {
        return $this->findBy('key', $key);
    }

    public function getAllByName(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
}
