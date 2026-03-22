<?php

namespace App\Models;

class PageBlock
{
    public ?int $id = null;
    public int $page_id;
    public int $block_type_id;
    public int $sort_order = 0;
    public string $props_json = '{}'; // Block properties/configuration
    public string $bindings_json = '{}'; // Data bindings
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
            'page_id' => $this->page_id,
            'block_type_id' => $this->block_type_id,
            'sort_order' => $this->sort_order,
            'props_json' => $this->props_json,
            'bindings_json' => $this->bindings_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getProps(): array
    {
        return json_decode($this->props_json, true) ?? [];
    }

    public function setProps(array $props): self
    {
        $this->props_json = json_encode($props);
        return $this;
    }

    public function getBindings(): array
    {
        return json_decode($this->bindings_json, true) ?? [];
    }

    public function setBindings(array $bindings): self
    {
        $this->bindings_json = json_encode($bindings);
        return $this;
    }
}
