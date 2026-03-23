<?php

namespace App\Models;

class Media
{
    public ?int $id = null;
    public string $title = '';
    public ?string $alt_text = null;
    public string $original_name = '';
    public string $filename = '';
    public string $mime_type = '';
    public string $extension = '';
    public int $size_bytes = 0;
    public string $storage_path = '';
    public string $public_url = '';
    public ?int $width = null;
    public ?int $height = null;
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
            'title' => $this->title,
            'alt_text' => $this->alt_text,
            'original_name' => $this->original_name,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size_bytes' => $this->size_bytes,
            'storage_path' => $this->storage_path,
            'public_url' => $this->public_url,
            'width' => $this->width,
            'height' => $this->height,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}