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

The following sections explain how to add new sections, repositories, migrations, seeds, routes, and extend the media library in xcms.

---

## Media Library Internals

xcms media management is implemented with:

| Class | Namespace | Purpose |
|---|---|---|
| `Media` | `App\Models\Media` | Model for a single media file record |
| `MediaTag` | `App\Models\MediaTag` | Model for a single tag |
| `MediaFolder` | `App\Models\MediaFolder` | Model for a folder node |
| `MediaRepository` | `App\Repositories\MediaRepository` | All media queries |
| `MediaTagRepository` | `App\Repositories\MediaTagRepository` | Tag CRUD and slug generation |
| `MediaFolderRepository` | `App\Repositories\MediaFolderRepository` | Folder tree queries |
| `MediaStorageService` | `App\Services\MediaStorageService` | File uploads, validation, renaming |
| `MediaAdminController` | `App\Controllers\Admin\MediaAdminController` | All `/admin/media/*` routes |

### Storage strategy

Files are stored flat under `public/uploads/media/`. Each upload receives a server-generated unique `stored_name` and a corresponding `path` (e.g. `/uploads/media/hero-8bde23d88d6ab12f.webp`). Folder organization is stored in SQLite metadata (`folder_id`) — moving a file between folders only updates metadata; the physical file never moves.

Physical path stays stable unless an explicit rename is requested by the editor (opt-in checkbox on the edit form), at which point a new unique stored name is generated and `path` is updated.

### Display filename vs stored name

`filename` is the editable display name shown in the admin. `stored_name` is the physical disk name, server-controlled and never trusted from user input.

### Upload validation rules

Implemented in `MediaStorageService`:

- Extension allowlist
- MIME type allowlist matched by extension
- Blocked executable/script extension list
- Maximum file size enforcement
- `is_uploaded_file()` check
- SVG inline script/event attribute safety scan

### Media tags: how they work

Tags are stored in `media_tags` and assigned via `media_tag_assignments`. A media item can have zero or more tags.

**Finding or creating tags — `MediaTagRepository`**

`findOrCreateByName(string $name)` is the primary entry point. It lowercases and trims the name, checks for an existing match, and creates a new record with a generated slug if no match is found. This is how tags are implicitly created when an editor saves the media edit form.

```php
$tagRepo = new \App\Repositories\MediaTagRepository();
$tag = $tagRepo->findOrCreateByName('hero images');
// Returns existing tag or creates a new one with slug "hero-images"
```

**Syncing tags on a media item — `MediaRepository::syncTags()`**

```php
$mediaRepo->syncTags($media->id, ['outdoor', 'product shots', 'CC BY 4.0']);
```

`syncTags()` accepts a flat array of tag name strings. It:

1. Finds or creates each tag via `MediaTagRepository::findOrCreateByName()`
2. Wraps the operation in a transaction
3. Deletes all existing assignments for the media item
4. Inserts new assignments for each resolved tag ID

Passing an empty array removes all tag assignments.

**Loading tags onto a media item**

Tags are not fetched automatically by default (to avoid N+1 queries on list pages).

For a single item:

```php
$media = $mediaRepo->findWithTags($id);
// $media->tags is an array of MediaTag objects
```

For a list of items:

```php
$items = $mediaRepo->searchWithFilters(['folder_id' => 3]);
$items = $mediaRepo->attachTagsToMediaList($items);
// Each $item->tags is now populated
```

`attachTagsToMediaList()` uses a single batch query for all IDs — it does not issue one query per item.

**The `MediaTag` model**

```php
$tag->id        // int
$tag->name      // string — original name as entered, e.g. "Hero Images"
$tag->slug      // string — URL-safe lowercased slug, e.g. "hero-images"
$tag->created_at
$tag->updated_at
```

### Searching media — `MediaRepository::searchWithFilters()`

`searchWithFilters(array $filters): array` is the central query method. It supports four independent filter keys, all optional:

| Key | Type | Behavior |
|---|---|---|
| `q` | `string` | Full-text LIKE search across filename, original name, title, alt text, MIME type, copyright text, copyright author, license name, source URL, and tag names/slugs |
| `folder_id` | `int` | Exact match on `m.folder_id` |
| `tag_id` | `int` | EXISTS subquery on `media_tag_assignments` — only items with this tag |
| `type` | `string` | Exact match on `m.type`; accepts `image`, `video`, or `document` only |

All active filters are combined with AND. `DISTINCT` prevents duplicates when a search term matches multiple tag names on the same item.

```php
$mediaRepo = new \App\Repositories\MediaRepository();

// Search for images tagged "outdoor" in folder 5
$results = $mediaRepo->searchWithFilters([
    'q'         => 'sunset',
    'folder_id' => 5,
    'tag_id'    => 12,
    'type'      => 'image',
]);

// Load tags onto results
$results = $mediaRepo->attachTagsToMediaList($results);
```

### Copyright and license fields on the `Media` model

The following properties are available on every `Media` instance:

| Property | Type | Description |
|---|---|---|
| `$media->copyright_text` | `?string` | Full copyright statement, e.g. `© 2024 Jane Smith` |
| `$media->copyright_author` | `?string` | Author or rights holder name |
| `$media->license_name` | `?string` | License identifier, e.g. `CC BY 4.0`, `All rights reserved` |
| `$media->license_url` | `?string` | URL to the license text |
| `$media->source_url` | `?string` | URL to the origin of the file |
| `$media->attribution_required` | `int` | `1` if attribution is required by the license, `0` otherwise |
| `$media->usage_notes` | `?string` | Free-form notes about allowed uses or restrictions |

### Outputting copyright data in templates and frontend rendering

Media metadata is stored in the database and not automatically embedded in public pages. To render copyright or attribution information, you need to fetch the media record in your controller or template logic and pass the relevant fields to your view.

**Pattern 1: Fetch a single media item by path in a controller**

If you store a media path like `/uploads/media/photo-abc123.webp` in a block's `props_json`, you can query the database for the matching record:

```php
$mediaRepo = new \App\Repositories\MediaRepository();
$media = $mediaRepo->findByStoredName('photo-abc123.webp');
// or search by path using searchWithFilters(['q' => 'photo-abc123'])
```

**Pattern 2: Embed media metadata in `props_json` at authoring time**

The safer, zero-query approach is to store all needed copyright fields in the block JSON at the time of authoring. In the page block editor, construct `props_json` manually:

```json
{
  "hero_image":       "/uploads/media/beach-scene-abc123.webp",
  "image_alt":        "Sunrise over the beach",
  "copyright_author": "Jane Smith",
  "license_name":     "CC BY 4.0",
  "license_url":      "https://creativecommons.org/licenses/by/4.0/",
  "attribution_required": true
}
```

Then reference these in the block's HTML template:

```html
<figure class="media-figure">
  <img src="{{ hero_image }}" alt="{{ image_alt }}">
  <figcaption class="attribution">
    Photo by {{ copyright_author }}
    — <a href="{{ license_url }}">{{ license_name }}</a>
  </figcaption>
</figure>
```

**Pattern 3: Load and pass media to a custom controller action**

For a custom page or collection query that needs to render files with their attribution data:

```php
use App\Repositories\MediaRepository;

class GalleryController extends \App\Core\Controller
{
    private MediaRepository $mediaRepo;

    public function __construct()
    {
        parent::__construct();
        $this->mediaRepo = new MediaRepository();
    }

    public function index(): void
    {
        $images = $this->mediaRepo->searchWithFilters([
            'type'   => 'image',
            'tag_id' => 7, // e.g. "gallery" tag id
        ]);

        $images = $this->mediaRepo->attachTagsToMediaList($images);

        $this->render('pages/gallery', ['images' => $images]);
    }
}
```

In `app/Views/pages/gallery.php`:

```php
<?php foreach ($images as $image): ?>
  <figure>
    <img src="<?= htmlspecialchars($image->path) ?>"
         alt="<?= htmlspecialchars((string) $image->alt_text) ?>">
    <?php if ($image->attribution_required): ?>
      <figcaption>
        <?php if ($image->copyright_text): ?>
          <?= htmlspecialchars($image->copyright_text) ?>
        <?php elseif ($image->copyright_author): ?>
          &copy; <?= htmlspecialchars($image->copyright_author) ?>
        <?php endif ?>
        <?php if ($image->license_name): ?>
          &mdash;
          <?php if ($image->license_url): ?>
            <a href="<?= htmlspecialchars($image->license_url) ?>"
               rel="license noopener"><?= htmlspecialchars($image->license_name) ?></a>
          <?php else: ?>
            <?= htmlspecialchars($image->license_name) ?>
          <?php endif ?>
        <?php endif ?>
        <?php if ($image->source_url): ?>
          — <a href="<?= htmlspecialchars($image->source_url) ?>"
               rel="noopener">Source</a>
        <?php endif ?>
      </figcaption>
    <?php endif ?>
  </figure>
<?php endforeach ?>
```

**Pattern 4: Render all tags on a media item**

After calling `attachTagsToMediaList()` or `findWithTags()`, tags are available as an array of `MediaTag` objects on `$media->tags`:

```php
foreach ($image->tags as $tag) {
    echo htmlspecialchars($tag->name);
}
```

Each `MediaTag` has `id`, `name`, and `slug` properties.

### Folder hierarchy safety

Folder operations enforce:

- No self-parent assignment
- No cyclical hierarchy (cannot move a folder under its own descendant)
- Delete blocked when child folders exist
- Delete blocked when folder still contains media items

### Helper panels for JSON fields

Page block and collection entry edit screens load media helper context via `buildMediaHelperContext()` in the respective admin controllers. This method reads `media_q`, `media_folder_id`, and `media_tag_id` from GET parameters, runs `searchWithFilters()`, batch-loads tags, and returns:

```php
[
    'mediaFolders'          => [...],  // all folders (tree list)
    'mediaTags'             => [...],  // all tags
    'mediaItems'            => [...],  // filtered Media objects with ->tags
    'selectedMediaFolderId' => ?int,
    'selectedMediaTagId'    => ?int,
    'mediaSearchQuery'      => string,
]
```

The Insert Path and Insert Snippet buttons in the helper write values into the active `props_json` / `bindings_json` (page blocks) or `data_json` (collection entries) textarea at the cursor position using vanilla JavaScript.

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
