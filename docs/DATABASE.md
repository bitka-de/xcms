# Database Reference

xcms uses a single SQLite file stored at `storage/database.sqlite`. The schema is managed through sequential SQL migration files in `database/migrations/`. All timestamps are stored as ISO 8601 strings (e.g. `2025-01-15T14:30:00+00:00`).

---

## Tables Overview

| Table | Purpose |
|---|---|
| `migrations` | Tracks which migration files have been applied |
| `pages` | Top-level content pages |
| `block_types` | Reusable block templates (HTML + CSS + JS) |
| `page_blocks` | Instances of a block type placed on a page |
| `collections` | Structured content lists |
| `collection_entries` | Individual entries within a collection |
| `media_folders` | Nested folder hierarchy for media organization |
| `media` | Uploaded images, videos, and documents |
| `design_settings` | Global CSS custom property key/value pairs |
| `media_tags` | Tag vocabulary for labeling media items |
| `media_tag_assignments` | Join table: which tags are assigned to which media items |

---

## Table: `migrations`

Created automatically by `MigrationRunner` on first run.

| Column | Type | Description |
|---|---|---|
| `id` | INTEGER PK | Auto-increment primary key |
| `filename` | TEXT UNIQUE | Filename of the applied migration, e.g. `001_create_pages.sql` |
| `applied_at` | TEXT | ISO 8601 timestamp when the migration was applied |

**Purpose:** Prevents re-applying migrations on subsequent `php setup.php` runs. If a filename is present in this table, the corresponding file is skipped.

---

## Table: `pages`

Defined in `database/migrations/001_create_pages.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `slug` | TEXT UNIQUE | No | — | URL identifier, e.g. `home`, `about`, `contact` |
| `title` | TEXT | No | — | Display title |
| `description` | TEXT | Yes | NULL | Internal description or page excerpt |
| `visibility` | TEXT | No | `draft` | One of `public`, `private`, `draft` |
| `seo_title` | TEXT | Yes | NULL | Overrides `title` in `<title>` tag |
| `seo_description` | TEXT | Yes | NULL | Populates `<meta name="description">` |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- `UNIQUE` on `slug`
- Index on `visibility` (used for public page queries)

**Key relationships:**
- One page has many `page_blocks` (CASCADE DELETE)

**Important fields:**

`slug` must be unique and URL-safe. The page with slug `home` is served at the site root `/`. All other public slugs are served directly at `/<slug>` via the router's slug fallback mechanism.

`visibility` is enforced with a CHECK constraint. Only `public` pages are returned by `PageRepository::findPublicBySlug()` and shown as active links. Pages with `draft` or `private` visibility are accessible in the admin but not served publicly.

---

## Table: `block_types`

Defined in `database/migrations/002_create_block_types.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `name` | TEXT | No | — | Human-readable name, e.g. "Hero Section" |
| `key` | TEXT UNIQUE | No | — | Machine-readable identifier, e.g. `hero`, `text-block` |
| `description` | TEXT | Yes | NULL | Internal notes **and** encoded metadata (see below) |
| `html_template` | TEXT | No | — | Mustache-style HTML template string |
| `css_template` | TEXT | Yes | NULL | CSS to be scoped and injected for this block type |
| `js_template` | TEXT | Yes | NULL | JavaScript to be injected at end of page body |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- `UNIQUE` on `key`
- Index on `name`

**Key relationships:**
- One block type is referenced by many `page_blocks` (RESTRICT DELETE — cannot delete a block type that is in use)

**Important fields:**

`key` is the stable identifier for a block type. It is used internally and can be indexed or referenced by collections or future tooling. Changing a key after a block type is in use is safe from a data perspective but may break any code that relies on it by key.

`html_template` supports two interpolation syntaxes:
- `{{ variable }}` — HTML-escaped value (safe for text)
- `{{{ variable }}}` — Raw/unescaped value (use only for trusted HTML)
- `{{ nested.key }}` — Dot-notation for nested JSON objects

`css_template` rules are scoped at render time by `CssScoper`. Every CSS selector is automatically prefixed with `[data-block-id="N"]`.

`description` **dual use:** The column stores both the human-readable description and encoded metadata. When the `BlockTypeAdminController` saves a block type, it JSON-encodes a metadata object and stores it in `description`:

```json
{
  "schema_json": "{\"fields\": [{\"key\": \"title\", …}]}",
  "preview_image": "https://example.com/preview.png",
  "_description": "The original description text"
}
```

On read, the controller decodes this JSON to extract `schema_json` and `preview_image`. If `description` is not valid JSON, it is treated as a plain string. This is a schema compatibility workaround — a future migration could add dedicated columns.

---

## Table: `page_blocks`

Defined in `database/migrations/003_create_page_blocks.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `page_id` | INTEGER FK | No | — | References `pages.id` — CASCADE DELETE |
| `block_type_id` | INTEGER FK | No | — | References `block_types.id` — RESTRICT DELETE |
| `sort_order` | INTEGER | No | 0 | Ascending sort order for rendering |
| `props_json` | TEXT | No | `{}` | JSON object of prop values for the template |
| `bindings_json` | TEXT | No | `{}` | JSON object of dynamic data bindings (future use) |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- Index on `page_id`
- Index on `block_type_id`
- Composite index on `(page_id, sort_order)` — used for ordered fetching

**Key relationships:**
- Belongs to one `page` (CASCADE DELETE — blocks are deleted when page is deleted)
- Belongs to one `block_type` (RESTRICT DELETE — block type cannot be deleted if used)

**Important fields:**

`props_json` is the primary content field for a block instance. It is a JSON object whose keys match the variable names referenced in the block type's `html_template`. The `TemplateEngine` resolves these keys when rendering.

**Example `props_json` for a hero block:**

```json
{
  "eyebrow": "Welcome to xcms",
  "title": "Build your site with blocks",
  "body": "<p>A clean, fast, block-based CMS built on PHP and SQLite.</p>",
  "button_label": "Get started",
  "button_url": "/about"
}
```

`sort_order` determines rendering sequence. Blocks with a lower `sort_order` value appear higher on the page. Use multiples of 10 to allow easy insertion between existing blocks.

`bindings_json` is reserved for future use — it is intended to support dynamic data binding where a block prop value is sourced from a collection query rather than a static value.

---

## Table: `collections`

Defined in `database/migrations/004_create_collections.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `name` | TEXT | No | — | Human-readable name, e.g. "Blog Posts" |
| `key` | TEXT UNIQUE | No | — | Machine-readable identifier, e.g. `blog`, `testimonials` |
| `description` | TEXT | Yes | NULL | Internal notes about the collection |
| `schema_json` | TEXT | No | `{}` | JSON object describing the structure of entries |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- `UNIQUE` on `key`
- Index on `name`

**Key relationships:**
- One collection has many `collection_entries` (CASCADE DELETE)

**Important fields:**

`key` is the stable machine-readable identifier used to reference a collection in templates or custom code (e.g. `blog`).

`schema_json` is informational metadata — a JSON object documenting what fields each entry in the collection is expected to have. It is not validated at runtime. Example:

```json
{
  "fields": [
    { "key": "title",     "type": "text",     "label": "Post title" },
    { "key": "slug",      "type": "text",     "label": "URL slug" },
    { "key": "excerpt",   "type": "text",     "label": "Short excerpt" },
    { "key": "body",      "type": "markdown", "label": "Post body" },
    { "key": "published", "type": "date",     "label": "Published date" }
  ]
}
```

---

## Table: `collection_entries`

Defined in `database/migrations/005_create_collection_entries.sql` and `007_add_status_to_collection_entries.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `collection_id` | INTEGER FK | No | — | References `collections.id` — CASCADE DELETE |
| `data_json` | TEXT | No | `{}` | JSON object with the entry's content |
| `status` | TEXT | No | `draft` | One of `draft`, `published`, `archived` |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- Index on `collection_id`
- Index on `created_at`
- Index on `status` (added by migration 007)

**Key relationships:**
- Belongs to one `collection` (CASCADE DELETE — entries are deleted when collection is deleted)

**Important fields:**

`data_json` stores the full content of an entry as a JSON object. The shape of this object should match the parent collection's `schema_json`. There is no server-side enforcement of the schema — any valid JSON object is accepted.

**Example `data_json` for a blog entry:**

```json
{
  "title": "Getting started with xcms",
  "slug": "getting-started",
  "excerpt": "A quick guide to setting up and running xcms.",
  "body": "<p>Install xcms by running <code>php setup.php</code>…</p>",
  "published": "2025-01-15"
}
```

`status` controls the visibility workflow:
- `draft` — entry is not publicly visible; work in progress
- `published` — entry is live and should be shown publicly
- `archived` — hidden from public output but retained in the database

The `status` column was added by migration `007`. `CollectionEntryRepository` includes an `ensureStatusColumn()` guard in its constructor that uses `PRAGMA table_info()` to add the column if it is missing on existing databases.

---

## Table: `design_settings`

Defined in `database/migrations/006_create_design_settings.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `key` | TEXT UNIQUE | No | — | CSS custom property name without `--` prefix, e.g. `primary_color` |
| `value` | TEXT | No | — | The setting value, e.g. `#3b82f6` or `'Inter', sans-serif` |
| `type` | TEXT | No | `text` | Display hint for the admin form: `color`, `text`, or `select` |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- `UNIQUE` on `key`

**Important fields:**

`key` uses underscore-separated names. At render time, xcms converts each key to a CSS custom property by replacing underscores with hyphens and prefixing with `--`. For example, `primary_color` becomes `--primary-color`.

`type` controls which HTML input widget is rendered in the admin form:
- `color` — renders a `<input type="color">` color picker
- `text` — renders a `<input type="text">` field (default)
- `select` — reserved for future use

The settings are output as a CSS `:root {}` block at the top of every public page's `<head>`, making all values available as CSS custom properties throughout the page and all block CSS.

**Default settings seeded by `001_default_design_settings.php`:**

| Key | Default Value | CSS Variable |
|---|---|---|
| `primary_color` | `#3b82f6` | `--primary-color` |
| `secondary_color` | `#6366f1` | `--secondary-color` |
| `font_family` | `'Inter', sans-serif` | `--font-family` |
| `base_spacing` | `1rem` | `--base-spacing` |
| `container_width` | `1200px` | `--container-width` |
| `border_radius` | `0.5rem` | `--border-radius` |

---

## Table: `media_folders`

Defined in `database/migrations/009_media_folders_and_media_columns.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `name` | TEXT | No | — | Folder display name |
| `slug` | TEXT | No | — | URL-safe/identifier-friendly name, unique within same parent |
| `parent_id` | INTEGER FK | Yes | NULL | Self-reference to `media_folders.id` for nesting |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- Composite unique index on `(parent_id, slug)`
- Index on `parent_id`
- Index on `name`

**Key relationships:**
- Self-referential hierarchy via `parent_id`
- One folder can have many child folders
- One folder can have many `media` records (`media.folder_id`)

**Important behavior:**

Folder deletion is intentionally guarded in application logic:
- blocked if folder has child folders
- blocked if folder still contains media items

This prevents broken hierarchies and accidental orphaning.

---

## Table: `media`

Initially created in `database/migrations/008_create_media.sql` and extended in `database/migrations/009_media_folders_and_media_columns.sql`.
Extended further with copyright and license fields in `database/migrations/010_extend_media_copyright_metadata.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `folder_id` | INTEGER FK | Yes | NULL | Optional folder assignment (`media_folders.id`) |
| `filename` | TEXT | No | — | Editable display filename |
| `original_name` | TEXT | No | — | Original uploaded client filename |
| `stored_name` | TEXT | Yes* | NULL | Physical server-side file name (unique by generation strategy) |
| `mime_type` | TEXT | No | — | Detected MIME type |
| `extension` | TEXT | No | — | Lowercase extension |
| `file_size` | INTEGER | Yes* | NULL | File size in bytes |
| `path` | TEXT | Yes* | NULL | Public relative path (for example `/uploads/media/file.webp`) |
| `type` | TEXT | Yes* | NULL | `image`, `video`, or `document` |
| `title` | TEXT | Yes | NULL | Optional media title |
| `alt_text` | TEXT | Yes | NULL | Optional image alt text |
| `width` | INTEGER | Yes | NULL | Image width when applicable |
| `height` | INTEGER | Yes | NULL | Image height when applicable |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

`*` Columns added in migration `009` and backfilled for existing rows.

**Indexes:**
- Unique index from migration 008 on `filename` (legacy)
- Index on `created_at`
- Index on `mime_type`
- Index on `folder_id`
- Index on `type`
- Index on `path`

**Key relationships:**
- `folder_id` references `media_folders.id` (nullable)
- Tags assigned via `media_tag_assignments.media_id` (CASCADE DELETE)

**Important fields:**

`filename` is an editor-facing name used in admin UI and helper snippets.

`stored_name` is the real filename on disk. It is generated server-side with randomness to avoid collisions and should never be trusted from user input.

`path` is the reusable public path used by pages/blocks/collections in JSON fields.

`type` drives preview behavior in admin:
- image → `<img>` preview
- video → `<video>` preview
- document → PDF label

**Copyright and licence columns** (added in migration `010`):

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `copyright_text` | TEXT | Yes | NULL | Full copyright statement, e.g. `© 2024 Jane Smith` |
| `copyright_author` | TEXT | Yes | NULL | Name of the rights holder or author |
| `license_name` | TEXT | Yes | NULL | License identifier, e.g. `CC BY 4.0`, `All rights reserved` |
| `license_url` | TEXT | Yes | NULL | URL to the full license text |
| `source_url` | TEXT | Yes | NULL | URL to the original source of the file |
| `attribution_required` | INTEGER | No | `0` | `1` if the license requires crediting the author; `0` otherwise |
| `usage_notes` | TEXT | Yes | NULL | Free-form internal notes about permitted uses or restrictions |

**Indexes added by migration 010:**
- Index on `copyright_author`
- Index on `license_name`
- Index on `attribution_required`

---

## Table: `media_tags`

Defined in `database/migrations/011_create_media_tags.sql`.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | INTEGER PK | — | auto | Primary key |
| `name` | TEXT | No | — | Display name as entered, e.g. `Hero Images` |
| `slug` | TEXT | No | — | URL-safe lowercase slug, e.g. `hero-images`, unique |
| `created_at` | TEXT | No | — | ISO 8601 creation timestamp |
| `updated_at` | TEXT | No | — | ISO 8601 last-updated timestamp |

**Indexes:**
- `UNIQUE` on `name`
- `UNIQUE` on `slug`
- Index on `name`

**Key relationships:**
- Tags are assigned to media items via `media_tag_assignments`

**Important behavior:**

Tags are created implicitly by the application. When an editor saves a media item with tag names, `MediaTagRepository::findOrCreateByName()` looks up each name case-insensitively and creates a new record if no match is found. The `slug` is generated by lowercasing the name and replacing non-alphanumeric characters with hyphens.

Tag records are never deleted automatically. Removing a tag from a media item only removes the assignment row in `media_tag_assignments`. If you need to prune unused tags, do so directly in the database.

---

## Table: `media_tag_assignments`

Defined in `database/migrations/012_create_media_tag_assignments.sql`.

This is a composite-primary-key join table linking media items to tags.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `media_id` | INTEGER PK (composite) | No | — | References `media.id` — CASCADE DELETE |
| `tag_id` | INTEGER PK (composite) | No | — | References `media_tags.id` — CASCADE DELETE |
| `created_at` | TEXT | No | — | ISO 8601 timestamp when the assignment was created |

**Primary key:** Composite `(media_id, tag_id)` — a given tag can only be assigned to a given media item once.

**Indexes:**
- Composite PK index on `(media_id, tag_id)` (implicit)
- Index on `tag_id` (for reverse lookups: which media items have this tag)

**Key relationships:**
- `media_id` references `media.id` — when a media item is deleted, all its tag assignments are deleted automatically (CASCADE DELETE)
- `tag_id` references `media_tags.id` — when a tag is deleted, all its assignments are deleted automatically (CASCADE DELETE)

**Sync pattern:**

Tag assignments are always managed as a full replacement. `MediaRepository::syncTags()` wraps the operation in a transaction: delete all existing assignments for a media item, then insert new assignments for the resolved tag IDs. There is no partial-update path.

---

## Relationships Summary

```
pages
  └── page_blocks (page_id → pages.id, CASCADE)
        └── block_types (block_type_id → block_types.id, RESTRICT)

collections
  └── collection_entries (collection_id → collections.id, CASCADE)

media_folders
  ├── media_folders (parent_id → media_folders.id, self-reference)
  └── media (folder_id → media_folders.id, nullable)
        └── media_tag_assignments (media_id → media.id, CASCADE)
              └── media_tags (tag_id → media_tags.id, CASCADE)

design_settings (standalone, no foreign keys)
migrations (standalone, managed by MigrationRunner)
```


---

## JSON Field Summary

| Table | Column | Purpose | Structure |
|---|---|---|---|
| `block_types` | `html_template` | Mustache HTML with `{{ prop }}` variables | HTML string |
| `block_types` | `css_template` | CSS rules, auto-scoped to `data-block-id` | CSS string |
| `block_types` | `js_template` | JavaScript, injected verbatim | JS string |
| `block_types` | `description` | Dual-purpose: plain text OR JSON with `schema_json` + `preview_image` keys | JSON object or plain string |
| `page_blocks` | `props_json` | Prop values for block template variables | JSON object |
| `page_blocks` | `bindings_json` | Future dynamic binding configuration | JSON object (currently `{}`) |
| `collections` | `schema_json` | Documents expected structure of entry `data_json` | JSON object |
| `collection_entries` | `data_json` | Full entry content | JSON object (schema matches parent collection's `schema_json`) |
| `media` | `path` | Public path used by page/collection JSON fields | String |
