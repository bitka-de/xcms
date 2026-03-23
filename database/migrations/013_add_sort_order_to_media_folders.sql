ALTER TABLE media_folders ADD COLUMN sort_order INTEGER NOT NULL DEFAULT 0;

UPDATE media_folders
SET sort_order = id
WHERE sort_order IS NULL OR sort_order = 0;

CREATE INDEX IF NOT EXISTS idx_media_folders_sort_order ON media_folders (sort_order);
