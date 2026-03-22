ALTER TABLE collection_entries ADD COLUMN status TEXT NOT NULL DEFAULT 'draft';

CREATE INDEX IF NOT EXISTS idx_collection_entries_status ON collection_entries (status);
