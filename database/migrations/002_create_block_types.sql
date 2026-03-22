CREATE TABLE IF NOT EXISTS block_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    key TEXT NOT NULL,
    description TEXT,
    html_template TEXT NOT NULL,
    css_template TEXT,
    js_template TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_block_types_key_unique ON block_types (key);
CREATE INDEX IF NOT EXISTS idx_block_types_name ON block_types (name);
