<?php

namespace App\Repositories;

use App\Models\MediaFolder;

class MediaFolderRepository extends BaseRepository
{
    protected string $table = 'media_folders';
    protected string $modelClass = MediaFolder::class;

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY sort_order ASC, name ASC");
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function allWithParents(): array
    {
        $stmt = $this->db->query("\n            SELECT f.*, p.name AS parent_name\n            FROM {$this->table} f\n            LEFT JOIN {$this->table} p ON p.id = f.parent_id\n            ORDER BY f.sort_order ASC, f.name ASC\n        ");

        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function allByParent(?int $parentId): array
    {
        if ($parentId === null) {
            $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE parent_id IS NULL ORDER BY sort_order ASC, name ASC");
            return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id = ? ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$parentId]);

        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function getNextSortOrder(): int
    {
        $stmt = $this->db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function getNextSortOrderForParent(?int $parentId): int
    {
        if ($parentId === null) {
            $stmt = $this->db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$this->table} WHERE parent_id IS NULL");
            return (int) $stmt->fetchColumn();
        }

        $stmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$this->table} WHERE parent_id = ?");
        $stmt->execute([$parentId]);

        return (int) $stmt->fetchColumn();
    }

    public function reorderByIds(array $orderedFolderIds): bool
    {
        if ($orderedFolderIds === []) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET sort_order = ? WHERE id = ?");
            $position = 1;

            foreach ($orderedFolderIds as $folderId) {
                $stmt->execute([$position, (int) $folderId]);
                $position++;
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function reorderWithinParent(?int $parentId, array $orderedFolderIds): bool
    {
        if ($orderedFolderIds === []) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            if ($parentId === null) {
                $stmt = $this->db->prepare("UPDATE {$this->table} SET sort_order = ? WHERE id = ? AND parent_id IS NULL");
            } else {
                $stmt = $this->db->prepare("UPDATE {$this->table} SET sort_order = ? WHERE id = ? AND parent_id = ?");
            }

            $position = 1;
            foreach ($orderedFolderIds as $folderId) {
                $params = $parentId === null
                    ? [$position, (int) $folderId]
                    : [$position, (int) $folderId, $parentId];

                $stmt->execute($params);
                $position++;
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function hasChildren(int $folderId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE parent_id = ? LIMIT 1");
        $stmt->execute([$folderId]);
        return (bool) $stmt->fetchColumn();
    }

    public function isDescendantOf(int $folderId, int $ancestorId): bool
    {
        $current = $this->find($folderId);

        while ($current !== null && $current->parent_id !== null) {
            if ((int) $current->parent_id === $ancestorId) {
                return true;
            }

            $current = $this->find((int) $current->parent_id);
        }

        return false;
    }

    public function generateUniqueSlug(string $name, ?int $parentId, ?int $excludeId = null): string
    {
        $base = $this->slugify($name);
        $candidate = $base;
        $counter = 2;

        while ($this->slugExists($candidate, $parentId, $excludeId)) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    public function getTreeList(): array
    {
        $all = $this->all();
        $byParent = [];

        foreach ($all as $folder) {
            $key = $folder->parent_id !== null ? (int) $folder->parent_id : 0;
            if (!isset($byParent[$key])) {
                $byParent[$key] = [];
            }
            $byParent[$key][] = $folder;
        }

        $result = [];
        $walk = function (int $parentId, int $depth) use (&$walk, &$result, $byParent): void {
            $children = $byParent[$parentId] ?? [];
            foreach ($children as $folder) {
                $result[] = [
                    'id' => (int) $folder->id,
                    'name' => $folder->name,
                    'slug' => $folder->slug,
                    'parent_id' => $folder->parent_id,
                    'depth' => $depth,
                ];
                $walk((int) $folder->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $result;
    }

    public function getIdNameMap(): array
    {
        $stmt = $this->db->query("SELECT id, name FROM {$this->table} ORDER BY sort_order ASC, name ASC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = (string) $row['name'];
        }

        return $map;
    }

    public function mediaCountByFolder(): array
    {
        $stmt = $this->db->query('SELECT folder_id, COUNT(*) AS c FROM media WHERE folder_id IS NOT NULL GROUP BY folder_id');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['folder_id']] = (int) $row['c'];
        }

        return $result;
    }

    public function childCountByFolder(): array
    {
        $stmt = $this->db->query("SELECT parent_id, COUNT(*) AS c FROM {$this->table} WHERE parent_id IS NOT NULL GROUP BY parent_id");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['parent_id']] = (int) $row['c'];
        }

        return $result;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'folder';
    }

    private function slugExists(string $slug, ?int $parentId, ?int $excludeId): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE slug = ? AND " . ($parentId === null ? 'parent_id IS NULL' : 'parent_id = ?');
        $params = [$slug];
        if ($parentId !== null) {
            $params[] = $parentId;
        }

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }
}