<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected string $table;
    protected string $modelClass;
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function hydrate(array $row): object
    {
        return new $this->modelClass($row);
    }

    protected function hydrateAll(array $rows): array
    {
        return array_map($this->hydrate(...), $rows);
    }

    public function find(int $id): ?object
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $this->hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(object $model): ?int
    {
        $data = $model->toArray();
        unset($data['id']);

        $now = date('c');
        if (array_key_exists('created_at', $data) && empty($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (array_key_exists('updated_at', $data) && empty($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)$this->db->lastInsertId();
    }

    public function update(object $model): bool
    {
        if ($model->id === null) {
            throw new \RuntimeException('Cannot update model without id');
        }

        $data = $model->toArray();
        unset($data['id']);
        unset($data['created_at']);

        if (array_key_exists('updated_at', $data)) {
            $data['updated_at'] = date('c');
        }

        $fields = array_keys($data);
        $setParts = array_map(fn($f) => "$f = ?", $fields);

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $params = array_values($data);
        $params[] = $model->id;
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function save(object $model): int
    {
        if ($model->id === null) {
            $model->id = $this->create($model);
            return $model->id;
        }

        $this->update($model);
        return $model->id;
    }

    protected function findBy(string $column, mixed $value, bool $one = true): mixed
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE $column = ?");
        $stmt->execute([$value]);

        if ($one) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->hydrate($row) : null;
        }

        return $this->hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
