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
}