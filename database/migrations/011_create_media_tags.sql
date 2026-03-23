CREATE TABLE IF NOT EXISTS media_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_media_tags_name_unique ON media_tags (name);
CREATE UNIQUE INDEX IF NOT EXISTS idx_media_tags_slug_unique ON media_tags (slug);
CREATE INDEX IF NOT EXISTS idx_media_tags_name ON media_tags (name);
