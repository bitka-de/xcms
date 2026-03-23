<?php

namespace App\Repositories;

use App\Models\Media;

class MediaRepository extends BaseRepository
{
    protected string $table = 'media';
    protected string $modelClass = Media::class;

    public function all(): array
    {
        return $this->allByFolder(null);
    }

    public function allByFolder(?int $folderId): array
    {
        $sql = "
            SELECT m.*, f.name AS folder_name
            FROM {$this->table} m
            LEFT JOIN media_folders f ON f.id = m.folder_id
        ";

        if ($folderId === null) {
            $sql .= ' ORDER BY m.created_at DESC, m.id DESC';
            $stmt = $this->db->query($sql);
        } else {
            $sql .= ' WHERE m.folder_id = ? ORDER BY m.created_at DESC, m.id DESC';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$folderId]);
        }

        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function findByFilename(string $filename): ?Media
    {
        return $this->findBy('filename', $filename);
    }

    public function findByStoredName(string $storedName): ?Media
    {
        return $this->findBy('stored_name', $storedName);
    }

    public function countByFolderId(int $folderId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE folder_id = ?");
        $stmt->execute([$folderId]);

        return (int) $stmt->fetchColumn();
    }

    public function allForHelper(?int $folderId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));

        $sql = "
            SELECT m.*, f.name AS folder_name
            FROM {$this->table} m
            LEFT JOIN media_folders f ON f.id = m.folder_id
        ";

        if ($folderId === null) {
            $sql .= ' ORDER BY m.created_at DESC, m.id DESC LIMIT ' . $limit;
            $stmt = $this->db->query($sql);
        } else {
            $sql .= ' WHERE m.folder_id = ? ORDER BY m.created_at DESC, m.id DESC LIMIT ' . $limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$folderId]);
        }

        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
}