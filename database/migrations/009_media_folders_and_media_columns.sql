CREATE TABLE IF NOT EXISTS media_folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    parent_id INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (parent_id) REFERENCES media_folders (id) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_media_folders_parent_slug_unique ON media_folders (parent_id, slug);
CREATE INDEX IF NOT EXISTS idx_media_folders_parent_id ON media_folders (parent_id);
CREATE INDEX IF NOT EXISTS idx_media_folders_name ON media_folders (name);

ALTER TABLE media ADD COLUMN folder_id INTEGER NULL;
ALTER TABLE media ADD COLUMN stored_name TEXT;
ALTER TABLE media ADD COLUMN file_size INTEGER;
ALTER TABLE media ADD COLUMN path TEXT;
ALTER TABLE media ADD COLUMN type TEXT;

UPDATE media
SET stored_name = COALESCE(NULLIF(stored_name, ''), NULLIF(filename, ''), 'file-' || id || '.' || LOWER(COALESCE(NULLIF(extension, ''), 'bin')))
WHERE stored_name IS NULL OR stored_name = '';

UPDATE media
SET file_size = COALESCE(file_size, size_bytes, 0)
WHERE file_size IS NULL OR file_size = 0;

UPDATE media
SET path = COALESCE(NULLIF(path, ''), NULLIF(public_url, ''), '/' || NULLIF(storage_path, ''), '/uploads/media/' || stored_name)
WHERE path IS NULL OR path = '';

UPDATE media
SET type = CASE
    WHEN mime_type LIKE 'image/%' THEN 'image'
    WHEN mime_type LIKE 'video/%' THEN 'video'
    ELSE 'document'
END
WHERE type IS NULL OR type = '';

CREATE INDEX IF NOT EXISTS idx_media_folder_id ON media (folder_id);
CREATE INDEX IF NOT EXISTS idx_media_type ON media (type);
CREATE INDEX IF NOT EXISTS idx_media_path ON media (path);