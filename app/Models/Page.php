<?php

namespace App\Models;

class Page
{
    public ?int $id = null;
    public string $slug;
    public string $title;
    public ?string $description = null;
    public string $visibility = 'draft'; // public, draft, hidden
    public ?string $seo_title = null;
    public ?string $seo_description = null;
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
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isDraft(): bool
    {
        return $this->visibility === 'draft';
    }

    public function isVisible(): bool
    {
        return $this->visibility !== 'hidden';
    }
}
