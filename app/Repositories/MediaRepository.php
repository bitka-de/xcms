<?php

namespace App\Repositories;

use App\Models\Media;
use App\Models\MediaTag;

class MediaRepository extends BaseRepository
{
    protected string $table = 'media';
    protected string $modelClass = Media::class;

    public function all(): array
    {
        return $this->searchWithFilters([]);
    }

    public function allByFolder(?int $folderId, bool $withTags = false): array
    {
        $items = $this->searchWithFilters([
            'folder_id' => $folderId,
        ]);

        if ($withTags) {
            return $this->attachTagsToMediaList($items);
        }

        return $items;
    }

    public function searchWithFilters(array $filters): array
    {
        $whereParts = [];
        $params = [];

        $q = isset($filters['q']) && is_string($filters['q']) ? trim($filters['q']) : '';
        $folderId = isset($filters['folder_id']) && is_int($filters['folder_id']) && $filters['folder_id'] > 0
            ? $filters['folder_id']
            : null;
        $tagId = isset($filters['tag_id']) && is_int($filters['tag_id']) && $filters['tag_id'] > 0
            ? $filters['tag_id']
            : null;
        $type = isset($filters['type']) && is_string($filters['type']) ? trim($filters['type']) : '';

        if ($folderId !== null) {
            $whereParts[] = 'm.folder_id = ?';
            $params[] = $folderId;
        }

        if ($tagId !== null) {
            $whereParts[] = 'EXISTS (SELECT 1 FROM media_tag_assignments ta WHERE ta.media_id = m.id AND ta.tag_id = ?)';
            $params[] = $tagId;
        }

        if (in_array($type, ['image', 'video', 'audio', 'document'], true)) {
            $whereParts[] = 'm.type = ?';
            $params[] = $type;
        }

        if ($q !== '') {
            $like = '%' . $q . '%';
            $whereParts[] = '(
                m.filename LIKE ? OR
                m.original_name LIKE ? OR
                m.title LIKE ? OR
                m.alt_text LIKE ? OR
                m.mime_type LIKE ? OR
                m.copyright_text LIKE ? OR
                m.copyright_author LIKE ? OR
                m.license_name LIKE ? OR
                m.source_url LIKE ? OR
                EXISTS (
                    SELECT 1
                    FROM media_tag_assignments ta2
                    INNER JOIN media_tags t2 ON t2.id = ta2.tag_id
                    WHERE ta2.media_id = m.id
                      AND (t2.name LIKE ? OR t2.slug LIKE ?)
                )
            )';
            for ($i = 0; $i < 11; $i++) {
                $params[] = $like;
            }
        }

        $sql = "
            SELECT DISTINCT m.*, f.name AS folder_name
            FROM {$this->table} m
            LEFT JOIN media_folders f ON f.id = m.folder_id
        ";

        if ($whereParts !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        $sql .= ' ORDER BY m.created_at DESC, m.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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

    public function findByPath(string $path): ?Media
    {
        return $this->findBy('path', $path);
    }

    public function findByPathWithTags(string $path): ?Media
    {
        $media = $this->findByPath($path);

        if ($media === null) {
            return null;
        }

        return $this->attachTagsToMedia($media);
    }

    public function countByFolderId(int $folderId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE folder_id = ?");
        $stmt->execute([$folderId]);

        return (int) $stmt->fetchColumn();
    }

    public function allForHelper(?int $folderId, int $limit = 50, bool $withTags = false): array
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

        $items = $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));

        if ($withTags) {
            return $this->attachTagsToMediaList($items);
        }

        return $items;
    }

    public function findWithTags(int $id): ?Media
    {
        $stmt = $this->db->prepare("
            SELECT m.*, f.name AS folder_name
            FROM {$this->table} m
            LEFT JOIN media_folders f ON f.id = m.folder_id
            WHERE m.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $media = $this->hydrate($row);
        $this->attachTagsToMedia($media);

        return $media;
    }

    public function tagsForMedia(int $mediaId): array
    {
        $stmt = $this->db->prepare("
            SELECT t.*
            FROM media_tags t
            INNER JOIN media_tag_assignments a ON a.tag_id = t.id
            WHERE a.media_id = ?
            ORDER BY t.name ASC
        ");
        $stmt->execute([$mediaId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static fn(array $row): MediaTag => new MediaTag($row), $rows);
    }

    public function syncTags(int $mediaId, array $tagNames): void
    {
        $tagRepository = new MediaTagRepository();

        $normalized = [];
        foreach ($tagNames as $tagName) {
            if (!is_string($tagName)) {
                continue;
            }

            $value = trim($tagName);
            if ($value === '') {
                continue;
            }

            $normalized[strtolower($value)] = $value;
        }

        $uniqueNames = array_values($normalized);
        $targetTagIds = [];
        foreach ($uniqueNames as $name) {
            $tag = $tagRepository->findOrCreateByName($name);
            if ($tag !== null && $tag->id !== null) {
                $targetTagIds[] = (int) $tag->id;
            }
        }

        $this->db->beginTransaction();
        try {
            $deleteStmt = $this->db->prepare('DELETE FROM media_tag_assignments WHERE media_id = ?');
            $deleteStmt->execute([$mediaId]);

            if ($targetTagIds !== []) {
                $insertStmt = $this->db->prepare('INSERT INTO media_tag_assignments (media_id, tag_id, created_at) VALUES (?, ?, ?)');
                $now = date('c');
                foreach ($targetTagIds as $tagId) {
                    $insertStmt->execute([$mediaId, $tagId, $now]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function attachTagsToMedia(Media $media): Media
    {
        if ($media->id === null) {
            $media->tags = [];
            return $media;
        }

        $media->tags = $this->tagsForMedia((int) $media->id);
        return $media;
    }

    public function attachTagsToMediaList(array $mediaItems): array
    {
        $ids = [];
        foreach ($mediaItems as $media) {
            if ($media instanceof Media && $media->id !== null) {
                $ids[] = (int) $media->id;
            }
        }

        if ($ids === []) {
            return $mediaItems;
        }

        $placeholder = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "
            SELECT a.media_id, t.*
            FROM media_tag_assignments a
            INNER JOIN media_tags t ON t.id = a.tag_id
            WHERE a.media_id IN ({$placeholder})
            ORDER BY t.name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $tagsByMediaId = [];
        foreach ($rows as $row) {
            $mediaId = (int) $row['media_id'];
            unset($row['media_id']);
            if (!isset($tagsByMediaId[$mediaId])) {
                $tagsByMediaId[$mediaId] = [];
            }
            $tagsByMediaId[$mediaId][] = new MediaTag($row);
        }

        foreach ($mediaItems as $media) {
            if (!$media instanceof Media || $media->id === null) {
                continue;
            }
            $media->tags = $tagsByMediaId[(int) $media->id] ?? [];
        }

        return $mediaItems;
    }
}
