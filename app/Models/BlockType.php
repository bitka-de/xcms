<?php

namespace App\Models;

class BlockType
{
    public ?int $id = null;
    public string $name;
    public string $key; // slug-like identifier
    public ?string $description = null;
    public string $html_template; // HTML markup
    public ?string $css_template = null; // Scoped CSS
    public ?string $js_template = null; // JavaScript code
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
            'html_template' => $this->html_template,
            'css_template' => $this->css_template,
            'js_template' => $this->js_template,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function hasCss(): bool
    {
        return !empty($this->css_template);
    }

    public function hasJs(): bool
    {
        return !empty($this->js_template);
    }
}
