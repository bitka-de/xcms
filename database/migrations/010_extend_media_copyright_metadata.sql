ALTER TABLE media ADD COLUMN copyright_text TEXT;
ALTER TABLE media ADD COLUMN copyright_author TEXT;
ALTER TABLE media ADD COLUMN license_name TEXT;
ALTER TABLE media ADD COLUMN license_url TEXT;
ALTER TABLE media ADD COLUMN source_url TEXT;
ALTER TABLE media ADD COLUMN attribution_required INTEGER NOT NULL DEFAULT 0;
ALTER TABLE media ADD COLUMN usage_notes TEXT;

UPDATE media
SET attribution_required = COALESCE(attribution_required, 0);

CREATE INDEX IF NOT EXISTS idx_media_copyright_author ON media (copyright_author);
CREATE INDEX IF NOT EXISTS idx_media_license_name ON media (license_name);
CREATE INDEX IF NOT EXISTS idx_media_attribution_required ON media (attribution_required);
