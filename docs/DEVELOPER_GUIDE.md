# Developer Guide

## How Block Rendering Works

When a public page is requested, `PageRenderer` orchestrates the entire rendering pipeline:

1. Load the `Page` record from the database by slug
2. Load all `PageBlock` records for that page, ordered by `sort_order`
3. For each `PageBlock`, load its associated `BlockType`
4. Pass each `(PageBlock, BlockType)` pair to `BlockRenderer::render()`
5. Collect all returned HTML, CSS, and JS strings
6. Load design settings and build the `:root {}` CSS block
7. Return all parts to the view

`BlockRenderer::render()` returns an array with these keys:

```php
[
    'block_id'      => '42',                        // data-block-id value (string)
    'page_block_id' => 42,                          // integer DB id
    'block_type_id' => 3,
    'block_type_key'=> 'hero',
    'html'          => '<div class="cms-block" data-block-id="42">…</div>',
    'css'           => '[data-block-id="42"] .hero { … }',
    'js'            => '// optional JS',
]
```

The `html`, `css`, and `js` values across all blocks are concatenated and passed to `pages/show.php`.

---

## How to Create a New Block Type

You can create block types through the admin UI at `/admin/block-types/create`. For programmatic creation, use the seed pattern described below.

### Step 1: Design the props

Decide what content variables your block needs and give them clear names. These will be the keys in `props_json` on each page block instance.

**Example props:** `heading`, `body`, `image_url`, `cta_label`, `cta_url`

### Step 2: Write the HTML template

In the HTML template, use:
- `{{ variable }}` — output a value with HTML escaping (safe for text content)
- `{{{ variable }}}` — output raw HTML (use only for content you control)

```html
<section class="feature-card">
  <img src="{{ image_url }}" alt="">
  <h2>{{ heading }}</h2>
  <div class="body">{{{ body }}}</div>
  <a href="{{ cta_url }}" class="btn-primary">{{ cta_label }}</a>
</section>
```

Dot notation is supported for nested props: `{{ author.name }}` will resolve to `$props['author']['name']`.

### Step 3: Write the CSS template

Write plain CSS. Do not scope it yourself — `CssScoper` will prefix every selector with `[data-block-id="N"]` automatically.

```css
.feature-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: var(--base-spacing);
  border-radius: var(--border-radius);
}

.feature-card h2 {
  font-size: 1.5rem;
  color: var(--primary-color);
}

.btn-primary {
  background: var(--primary-color);
  color: white;
  padding: 0.5rem 1.25rem;
  border-radius: var(--border-radius);
  text-decoration: none;
}
```

### Step 4: Write the JS template (optional)

If the block needs JavaScript, write it in the JS template. It is output as-is at the bottom of the page `<body>`. Target the block using its `data-block-id` attribute.

```js
(function() {
  document.querySelectorAll('.feature-card').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
      card.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
    });
    card.addEventListener('mouseleave', function() {
      card.style.boxShadow = '';
    });
  });
})();
```

### Step 5: Define a schema JSON (optional but recommended)

The schema JSON documents the expected props for editor tooling and human reference. It is stored in the `description` column encoded alongside other metadata.

```json
{
  "fields": [
    { "key": "heading",   "type": "text",  "label": "Heading" },
    { "key": "body",      "type": "html",  "label": "Body HTML" },
    { "key": "image_url", "type": "url",   "label": "Image URL" },
    { "key": "cta_label", "type": "text",  "label": "Button label" },
    { "key": "cta_url",   "type": "url",   "label": "Button URL" }
  ]
}
```

### Step 6: Add the block to a page

After creating the block type, open any page in the admin and use the **Add New Block** form. Select the block type, enter a sort order, and supply props as a JSON object matching your schema.

---

## How schema_json is Used

The `schema_json` field on block types and the `schema_json` field on collections are both informational JSON objects. They are stored in the database but not validated or enforced by the runtime.

**Block type schema** is stored inside the `description` column as a JSON-encoded metadata object:

```json
{
  "schema_json": "{\"fields\": […]}",
  "preview_image": "https://example.com/preview.png"
}
```

The admin controller (`BlockTypeAdminController`) reads and writes this metadata by encoding and decoding the `description` field. This is a compatibility workaround because the `block_types` table does not have dedicated `schema_json` or `preview_image` columns.

**Collection schema** is stored directly in the `schema_json` column on the `collections` table. It documents the expected structure of each entry's `data_json` field.

Neither schema is used for form auto-generation or server-side validation in the current version. It exists as structured metadata for future tooling or custom code.

---

## Notes on CSS Scoping

`CssScoper` uses a regex-based approach to prefix CSS selectors. It handles standard rule sets and comma-separated selector groups. Limitations to be aware of:

- **Nested at-rules** like `@keyframes` and `@media` blocks are partially supported. Selectors inside `@media` are scoped, but `@keyframes` animation name identifiers are not prefixed.
- **`:root` selectors** inside a block CSS template are rewritten to the block's scoping prefix. If you need to define CSS variables scoped to a block, write `:root { --var: value; }` and it will be output as `[data-block-id="N"] { --var: value; }`.
- **Global styles** should be placed in `public/assets/app.css`, not in block templates.
- **The block container** is always `<div class="cms-block" data-block-id="N">`. You can safely style `.cms-block` from `app.css`.

When writing block CSS, always target class names relative to the block root:

```css
/* This works — .hero is scoped to the block container */
.hero { … }

/* This also works — direct descendant of the block wrapper */
> .inner { … }

/* Avoid targeting element types broadly — they will be scoped but may conflict with resets */
h1 { … }  /* becomes [data-block-id="N"] h1 { … } — fine, but be intentional */
```

---

## Notes on Trusted HTML/CSS/JS Editing

xcms is designed for **trusted editors** — people who have admin access are assumed to be developers or technically literate content managers. Because of this:

- **HTML templates** can contain arbitrary HTML. There is no sanitization. An editor can embed `<script>` tags, event attributes, or any other markup. This is intentional — it gives block type authors full control over the rendered output.
- **CSS templates** are scoped automatically but otherwise unmodified. Editors can write any valid CSS.
- **JS templates** are output verbatim. Editors have full JavaScript access to the page.
- **`{{{ triple-brace }}}` props** are output without HTML escaping. Use only when the prop value is trusted HTML authored by the same trusted editor.
- **`{{ double-brace }}` props** are HTML-escaped and safe for untrusted text values.
- **Collection entry `data_json`** is stored as raw JSON. Values accessed by block templates are HTML-escaped when rendered via `{{ variable }}`.

Do not give admin panel access to untrusted users without adding an authentication layer.

---

## How to Extend xcms

## Media Library Internals

xcms media management is implemented with:

- `App\Models\Media`
- `App\Models\MediaFolder`
- `App\Repositories\MediaRepository`
- `App\Repositories\MediaFolderRepository`
- `App\Services\MediaStorageService`
- `App\Controllers\Admin\MediaAdminController`

### Storage strategy

Files are stored flat under:

`public/uploads/media/`

Each upload receives a server-generated unique `stored_name` and corresponding `path` (for example `/uploads/media/hero-8bde23d88d6ab12f.webp`). Folder organization is stored in SQLite metadata (`folder_id`) instead of relying on physical nested directories.

This approach is robust for renames and folder moves because:

- moving between folders only updates metadata
- path collisions are avoided by random stored names
- physical paths stay stable unless explicit physical rename is requested

### Display filename vs physical stored name

`filename` is the editable display filename.

`stored_name` is the physical disk filename generated and controlled by the server.

In media edit:

- changing `filename` updates metadata only
- checking **rename physical file** triggers safe physical rename with a newly generated unique stored name
- when physical rename is applied, `path` is updated accordingly

### Upload validation rules

Validation is implemented in `MediaStorageService`:

- extension allowlist
- MIME type allowlist by extension
- blocked executable/script extension list
- max file size
- `is_uploaded_file` enforcement
- SVG safety check against inline script/event payloads

### Folder hierarchy safety

Folder operations enforce:

- no self-parent assignment
- no cyclical hierarchy (cannot move under descendant)
- delete blocked when child folders exist
- delete blocked when folder still contains media

### Helper panels for JSON fields

Page block and collection entry forms load media helper context from repositories and expose:

- folder filter
- file path
- filename
- media type
- copyable JSON snippets

This keeps media reusable in `props_json` and `data_json` without changing the block or collection schema model.

### Adding a new admin section

1. Create a controller in `app/Controllers/Admin/` extending `App\Core\Controller`
2. Create view files in `app/Views/admin/<section>/`
3. Add routes to `routes.php` following the existing pattern
4. Add a link to `app/Views/partials/admin_nav.php`

### Adding a new repository

1. Create a model class in `app/Models/` with a `toArray()` method and public properties
2. Create a repository in `app/Repositories/` extending `BaseRepository`
3. Set `$table` and `$modelClass` in the repository
4. Add custom query methods as needed

### Adding a new migration

1. Create a `.sql` file in `database/migrations/` with a numeric prefix higher than the existing files (e.g. `008_my_change.sql`)
2. Write standard SQLite DDL statements
3. Run `php setup.php` — the new migration will be applied

Use `CREATE TABLE IF NOT EXISTS`, `CREATE INDEX IF NOT EXISTS`, and guard `ALTER TABLE` changes with the knowledge that `MigrationRunner` will silently ignore duplicate-column errors.

### Adding a new seed

1. Create a `.php` file in `database/seeds/` with a numeric prefix
2. Use `App\Core\Database::connection()` to get the PDO instance
3. Write idempotent SQL — use `INSERT OR REPLACE`, `INSERT OR IGNORE`, or SELECT-then-insert patterns to avoid duplicate data
4. Run `php setup.php`

### Adding a new public route

Register the route in `routes.php`:

```php
'/contact' => ['controller' => 'ContactController', 'action' => 'index'],
```

Create `app/Controllers/ContactController.php` with the `index` method, and create any required views.

### Overriding the page layout

The public page layout is `app/Views/layouts/main.php`. Modify it to add a site header, footer, global navigation, or analytics tags. The layout receives:

- `$content` — the rendered page HTML
- `$page` — the `Page` model object
- `$css` — combined scoped CSS for all blocks on the page
- `$js` — combined JavaScript for all blocks on the page
- `$design_css_variables` — the `:root {}` CSS custom properties block
- `$meta` — array with `title` and `description` keys
