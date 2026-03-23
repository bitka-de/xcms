CREATE TABLE IF NOT EXISTS media_tag_assignments (
    media_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    PRIMARY KEY (media_id, tag_id),
    FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES media_tags (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_media_tag_assignments_tag_id ON media_tag_assignments (tag_id);
