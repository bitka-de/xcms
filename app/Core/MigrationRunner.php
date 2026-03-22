<?php

namespace App\Core;

use PDO;

class MigrationRunner
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function run(): void
    {
        $this->createTables();
        echo "Database migrations completed.\n";
    }

    private function createTables(): void
    {
        // Pages table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                visibility TEXT DEFAULT 'draft' CHECK (visibility IN ('public', 'draft', 'hidden')),
                seo_title TEXT,
                seo_description TEXT,
                created_at TEXT,
                updated_at TEXT
            )
        ");

        // Block types table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS block_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                key TEXT UNIQUE NOT NULL,
                description TEXT,
                html_template TEXT NOT NULL,
                css_template TEXT,
                js_template TEXT,
                created_at TEXT,
                updated_at TEXT
            )
        ");

        // Page blocks table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS page_blocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                page_id INTEGER NOT NULL,
                block_type_id INTEGER NOT NULL,
                sort_order INTEGER DEFAULT 0,
                props_json TEXT DEFAULT '{}',
                bindings_json TEXT DEFAULT '{}',
                created_at TEXT,
                updated_at TEXT,
                FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE,
                FOREIGN KEY (block_type_id) REFERENCES block_types (id)
            )
        ");

        // Collections table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS collections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                key TEXT UNIQUE NOT NULL,
                description TEXT,
                schema_json TEXT DEFAULT '{}',
                created_at TEXT,
                updated_at TEXT
            )
        ");

        // Collection entries table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS collection_entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                collection_id INTEGER NOT NULL,
                data_json TEXT DEFAULT '{}',
                created_at TEXT,
                updated_at TEXT,
                FOREIGN KEY (collection_id) REFERENCES collections (id) ON DELETE CASCADE
            )
        ");

        // Design settings table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS design_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT UNIQUE NOT NULL,
                value TEXT NOT NULL,
                type TEXT DEFAULT 'text',
                created_at TEXT,
                updated_at TEXT
            )
        ");

        // Create indexes
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_pages_visibility ON pages(visibility)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_page_blocks_page_id ON page_blocks(page_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_collection_entries_collection ON collection_entries(collection_id)");
    }
}
