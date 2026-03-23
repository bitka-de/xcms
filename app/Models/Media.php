<?php

namespace App\Models;

class Media
{
    public ?int $id = null;
    public ?int $folder_id = null;
    public string $filename = '';
    public string $original_name = '';
    public string $stored_name = '';
    public string $mime_type = '';
    public string $extension = '';
    public int $file_size = 0;
    public string $path = '';
    public string $type = 'document';
    public int $size_bytes = 0;
    public string $storage_path = '';
    public string $public_url = '';
    public ?string $title = null;
    public ?string $alt_text = null;
    public ?string $folder_name = null;
    public ?int $width = null;
    public ?int $height = null;
    public ?string $copyright_text = null;
    public ?string $copyright_author = null;
    public ?string $license_name = null;
    public ?string $license_url = null;
    public ?string $source_url = null;
    public int $attribution_required = 0;
    public ?string $usage_notes = null;
    public array $tags = [];
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
            'folder_id' => $this->folder_id,
            'filename' => $this->filename,
            'original_name' => $this->original_name,
            'stored_name' => $this->stored_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'file_size' => $this->file_size,
            'path' => $this->path,
            'type' => $this->type,
            'size_bytes' => $this->size_bytes,
            'storage_path' => $this->storage_path,
            'public_url' => $this->public_url,
            'title' => $this->title,
            'alt_text' => $this->alt_text,
            'width' => $this->width,
            'height' => $this->height,
            'copyright_text' => $this->copyright_text,
            'copyright_author' => $this->copyright_author,
            'license_name' => $this->license_name,
            'license_url' => $this->license_url,
            'source_url' => $this->source_url,
            'attribution_required' => $this->attribution_required,
            'usage_notes' => $this->usage_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function effectiveTitle(): string
    {
        if ($this->title !== null && trim($this->title) !== '') {
            return $this->title;
        }

        return $this->filename;
    }

    public function hasAttribution(): bool
    {
        $data = $this->getAttributionData();

        return $data['attribution_required']
            || $data['copyright_text'] !== null
            || $data['copyright_author'] !== null
            || $data['license_name'] !== null
            || $data['license_url'] !== null
            || $data['source_url'] !== null
            || $data['usage_notes'] !== null;
    }

    public function getAttributionData(): array
    {
        return [
            'copyright_text' => $this->normalizeNullableString($this->copyright_text),
            'copyright_author' => $this->normalizeNullableString($this->copyright_author),
            'license_name' => $this->normalizeNullableString($this->license_name),
            'license_url' => $this->normalizeNullableString($this->license_url),
            'source_url' => $this->normalizeNullableString($this->source_url),
            'attribution_required' => (int) $this->attribution_required === 1,
            'usage_notes' => $this->normalizeNullableString($this->usage_notes),
        ];
    }

    public function getTagNames(): array
    {
        $names = [];

        foreach ($this->tags as $tag) {
            if (!is_object($tag) || !property_exists($tag, 'name')) {
                continue;
            }

            $name = trim((string) $tag->name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    public function getTagSlugs(): array
    {
        $slugs = [];

        foreach ($this->tags as $tag) {
            if (!is_object($tag) || !property_exists($tag, 'slug')) {
                continue;
            }

            $slug = trim((string) $tag->slug);
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    public function getTemplateData(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'title' => $this->effectiveTitle(),
            'alt_text' => $this->normalizeNullableString($this->alt_text),
            'path' => $this->path,
            'type' => $this->type,
            'folder_name' => $this->normalizeNullableString($this->folder_name),
            'width' => $this->width,
            'height' => $this->height,
            'tags' => $this->getTagNames(),
            'tag_slugs' => $this->getTagSlugs(),
            'has_attribution' => $this->hasAttribution(),
            'attribution' => $this->getAttributionData(),
        ];
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}