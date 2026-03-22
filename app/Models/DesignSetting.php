<?php

namespace App\Models;

class DesignSetting
{
    public ?int $id = null;
    public string $key; // e.g., 'primary_color', 'font_size_base'
    public string $value; // Can be JSON or plain value
    public string $type = 'text'; // text, color, number, font, spacing, etc.
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
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isJson(): bool
    {
        return json_decode($this->value) !== null && is_array(json_decode($this->value, true));
    }

    public function getAsJson(): array
    {
        if ($this->isJson()) {
            return json_decode($this->value, true);
        }
        return [];
    }

    public function setFromArray(array $value): self
    {
        $this->value = json_encode($value);
        return $this;
    }

    public function getAsVariable(): string
    {
        // For CSS variable output, format based on type
        return match ($this->type) {
            'color' => "var(--{$this->key}: {$this->value});",
            'spacing', 'size' => "var(--{$this->key}: {$this->value});",
            default => "var(--{$this->key}: {$this->value});",
        };
    }
}
