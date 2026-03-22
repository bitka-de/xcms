<?php

namespace App\Models;

class CmsCollection
{
    public ?int $id = null;
    public string $name;
    public string $key; // slug-like identifier
    public ?string $description = null;
    public string $schema_json = '{}'; // Defines fields and their types
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array $data = [])
    {
        $this->fromArray($data);
    }

    public function fromArray(array $data): self
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'schema_json' => $this->schema_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getSchema(): array
    {
        return json_decode($this->schema_json, true) ?? [];
    }

    public function setSchema(array $schema): self
    {
        $this->schema_json = json_encode($schema);
        return $this;
    }
}
