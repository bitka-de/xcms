<?php

namespace App\Repositories;

use App\Models\Media;

class MediaRepository extends BaseRepository
{
    protected string $table = 'media';
    protected string $modelClass = Media::class;

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC, id DESC");
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function findByFilename(string $filename): ?Media
    {
        return $this->findBy('filename', $filename);
    }
}