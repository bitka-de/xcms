CREATE TABLE IF NOT EXISTS page_blocks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    block_type_id INTEGER NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    props_json TEXT NOT NULL DEFAULT '{}',
    bindings_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE,
    FOREIGN KEY (block_type_id) REFERENCES block_types (id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_page_blocks_page_id ON page_blocks (page_id);
CREATE INDEX IF NOT EXISTS idx_page_blocks_block_type_id ON page_blocks (block_type_id);
CREATE INDEX IF NOT EXISTS idx_page_blocks_sort_order ON page_blocks (page_id, sort_order);
