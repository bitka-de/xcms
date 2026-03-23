<?php

namespace App\Repositories;

use App\Models\MediaTag;

class MediaTagRepository extends BaseRepository
{
    protected string $table = 'media_tags';
    protected string $modelClass = MediaTag::class;

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function allInUse(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT t.* FROM {$this->table} t
             INNER JOIN media_tag_assignments a ON a.tag_id = t.id
             INNER JOIN media m ON m.id = a.media_id
             ORDER BY t.name ASC"
        );
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function deleteOrphans(): int
    {
        // Remove stale assignments whose media row no longer exists (e.g. after a delete without cascading FK)
        $this->db->exec(
            'DELETE FROM media_tag_assignments WHERE media_id NOT IN (SELECT id FROM media)'
        );

        // Remove stale assignments whose tag row no longer exists.
        $this->db->exec(
            'DELETE FROM media_tag_assignments WHERE tag_id NOT IN (SELECT id FROM media_tags)'
        );

        $stmt = $this->db->query(
            "DELETE FROM {$this->table}
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM media_tag_assignments a
                 WHERE a.tag_id = {$this->table}.id
             )"
        );
        return (int) $stmt->rowCount();
    }

    public function findByName(string $name): ?MediaTag
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->execute([trim($name)]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?MediaTag
    {
        return $this->findBy('slug', $slug);
    }

    public function findOrCreateByName(string $name): ?MediaTag
    {
        $cleanName = trim($name);
        if ($cleanName === '') {
            return null;
        }

        $existing = $this->findByName($cleanName);
        if ($existing !== null) {
            return $existing;
        }

        $tag = new MediaTag();
        $tag->name = $cleanName;
        $tag->slug = $this->generateUniqueSlug($cleanName);

        $id = $this->create($tag);
        if ($id === null) {
            return null;
        }

        return $this->find((int) $id);
    }

    public function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = $this->slugify($name);
        $candidate = $base;
        $counter = 2;

        while ($this->slugExists($candidate, $excludeId)) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'tag';
    }

    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
        $params = [$slug];

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
