CREATE TABLE IF NOT EXISTS collection_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    collection_id INTEGER NOT NULL,
    data_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (collection_id) REFERENCES collections (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_collection_entries_collection_id ON collection_entries (collection_id);
CREATE INDEX IF NOT EXISTS idx_collection_entries_created_at ON collection_entries (created_at);
