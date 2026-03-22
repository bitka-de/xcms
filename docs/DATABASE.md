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
| `design_settings` | Global CSS custom property key/value pairs |

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

## Relationships Summary

```
pages
  └── page_blocks (page_id → pages.id, CASCADE)
        └── block_types (block_type_id → block_types.id, RESTRICT)

collections
  └── collection_entries (collection_id → collections.id, CASCADE)

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
