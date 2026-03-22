<?php

namespace App\Models;

class CollectionEntry
{
    public ?int $id = null;
    public int $collection_id;
    public string $data_json = '{}'; // The actual entry data
    public string $status = 'draft';
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
            'collection_id' => $this->collection_id,
            'data_json' => $this->data_json,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getData(): array
    {
        return json_decode($this->data_json, true) ?? [];
    }

    public function setData(array $data): self
    {
        $this->data_json = json_encode($data);
        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->getData();
        return $data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $data = $this->getData();
        $data[$key] = $value;
        return $this->setData($data);
    }
}
