# Admin Guide

The xcms admin panel is accessible at `/admin`. It provides full management of all content in the system. There is no authentication layer by default — access should be restricted at the web server level or by adding an authentication layer for production use.

---

## Navigation

The sidebar contains links to all admin sections:

| Section | URL |
|---|---|
| Dashboard | `/admin` |
| Pages | `/admin/pages` |
| Block Types | `/admin/block-types` |
| Collections | `/admin/collections` |
| Media | `/admin/media` |
| Media Folders | `/admin/media/folders` |
| Design Settings | `/admin/design` |

---

## Dashboard

The dashboard (`/admin`) shows a summary of the site's content:

- Total number of pages
- Number of publicly visible pages
- Total number of block types
- Total number of collections

Use the dashboard as a starting point when returning to the admin panel.

---

## Pages

**URL:** `/admin/pages`

Pages are the top-level content objects. Each page has a unique slug that determines its public URL. A page with the slug `home` is served at the root `/`. All other pages are served at their slug directly (e.g., a page with slug `about` is accessible at `/about`).

### Page fields

| Field | Required | Description |
|---|---|---|
| Title | Yes | Display name of the page, shown in `<title>` unless SEO title is set |
| Slug | Yes | URL-safe identifier, must be unique across all pages (e.g. `about`, `home`) |
| Description | No | Internal description or excerpt |
| Visibility | Yes | `public` (visible to visitors), `draft` (hidden from public), or `private` (hidden) |
| SEO Title | No | Overrides title in the `<title>` tag |
| SEO Description | No | Populates the `<meta name="description">` tag |

### Creating a page

1. Go to **Pages** in the sidebar
2. Click **Add Page**
3. Fill in the title and slug. Choose a visibility of `draft` while building the page
4. Click **Save Page**

### Editing a page

Click **Edit** next to any page in the list. The edit screen has two sections:

1. **Page details** — the same fields as above
2. **Page blocks** — the block management section (see below)

### Deleting a page

On the page list, click **Delete** next to the page you want to remove. All page blocks associated with the page are deleted automatically (cascading delete).

---

## Block Types

**URL:** `/admin/block-types`

Block types are reusable content templates. They define the HTML structure, CSS styles, and JavaScript behavior of a block. Each block type has a unique machine-readable key and a set of template fields.

### Block type fields

| Field | Required | Description |
|---|---|---|
| Name | Yes | Human-readable name (e.g. "Hero Section") |
| Key | Yes | Machine-readable identifier, unique, used internally (e.g. `hero`) |
| Description | No | Internal notes about this block type |
| HTML Template | Yes | Mustache-style HTML template using `{{ variable }}` placeholders |
| CSS Template | No | CSS rules scoped automatically to this block's container |
| JS Template | No | JavaScript executed in the context of this block |
| Schema (JSON) | No | JSON object describing expected props for this block type |
| Preview Image URL | No | URL to a preview image shown in the block picker |

### HTML template syntax

In the HTML template, use `{{ variable_name }}` to insert a prop value with HTML escaping applied. Use `{{{ variable_name }}}` (triple braces) to insert a raw, unescaped value.

**Example:**

```html
<section class="hero">
  <p class="eyebrow">{{ eyebrow }}</p>
  <h1>{{ title }}</h1>
  <div class="body">{{{ body }}}</div>
  <a href="{{ button_url }}" class="btn">{{ button_label }}</a>
</section>
```

When this block is placed on a page, you supply the actual values for `eyebrow`, `title`, `body`, `button_url`, and `button_label` in the page block's props.

### CSS template

Write standard CSS in the CSS template. You do not need to scope the rules yourself — xcms automatically prefixes every selector with `[data-block-id="N"]` where N is the block's ID. This means your CSS only affects the specific block instance it belongs to.

**Example:**

```css
.hero {
  padding: 80px 20px;
  background: var(--primary-color);
}

h1 {
  font-size: 3rem;
  color: white;
}
```

This will be output as:

```css
[data-block-id="1"] .hero { … }
[data-block-id="1"] h1 { … }
```

You can safely use `var(--primary-color)` and other CSS custom properties defined in Design Settings.

### JS template

The JavaScript in the JS template is output as-is in a `<script>` block at the bottom of the page. You can use `document.querySelector('[data-block-id="N"]')` to target the specific block instance. Be aware that block IDs are assigned at database insertion time — use `data-block-id` selectors rather than hardcoded IDs.

### Schema JSON

The schema JSON field is optional documentation metadata. It is a JSON object describing the expected shape of the block's props. It is not validated at runtime but can be used by future tooling to auto-generate block editing forms.

**Example:**

```json
{
  "fields": [
    { "key": "title",        "type": "text",  "label": "Headline" },
    { "key": "body",         "type": "html",  "label": "Body text" },
    { "key": "button_label", "type": "text",  "label": "Button label" },
    { "key": "button_url",   "type": "url",   "label": "Button URL" }
  ]
}
```

---

## Page Blocks

Page blocks are instances of a block type placed on a specific page. They are managed directly from the page edit screen (`/admin/pages/:id/edit`), scrolled below the page detail fields.

### Adding a block

In the **Add New Block** section at the bottom of the page edit screen:

1. Choose a **Block Type** from the dropdown
2. Enter a **Sort Order** number (blocks render in ascending sort order)
3. Enter the **Props (JSON)** — a JSON object with the values for each template variable

Click **Add Block** to save.

**Example props JSON for a Hero block:**

```json
{
  "eyebrow": "Welcome",
  "title": "Build something great",
  "body": "<p>Start your project with xcms today.</p>",
  "button_label": "Get started",
  "button_url": "/about"
}
```

### Editing an existing block

Each block in the block list has its own update form. Change the sort order, type, or props and click **Update** on that block row to save.

### Deleting a block

Click **Delete** on a block row within the page edit screen. The block is removed immediately.

### Sort order

Blocks are rendered in ascending `sort_order` value. Use multiples of 10 (10, 20, 30) to leave room for inserting blocks between existing ones later.

### Using media in block JSON

The page edit screen includes a **Media Helper** panel. You can filter media by folder and copy either a direct media path or a ready-to-paste JSON snippet into `props_json`.

Use paths like:

```json
{
  "image_url": "/uploads/media/hero-image-2fd1a9a8d2f4c7a1.webp"
}
```

Or richer snippets:

```json
{
  "media": {
    "path": "/uploads/media/product-demo-a9f15f5d3bd910c1.mp4",
    "filename": "product-demo.mp4",
    "type": "video"
  }
}
```

---

## Media Library

**URL:** `/admin/media`

The media library stores uploaded files in `public/uploads/media/` and keeps metadata in SQLite. Supported file groups:

- **Images:** `jpg`, `jpeg`, `png`, `webp`, `gif`, `svg`
- **Videos:** `mp4`, `webm`, `mov`
- **Documents:** `pdf`

### What the media list shows

- Preview (image thumbnail, video preview, or PDF badge)
- Display filename
- MIME type and extension
- File size
- Folder name
- Public path for reuse in JSON data

### Uploading media

Go to `/admin/media/upload` and provide:

1. File
2. Optional folder
3. Optional display filename
4. Optional title and alt text

Upload security includes extension + MIME validation, max-size validation, server-side safe storage naming, and path traversal protection.

### Editing media metadata

Go to `/admin/media/edit?id=<id>` to edit:

- Folder assignment
- Display filename
- Title
- Alt text
- Optional physical file rename (safe server-side rename)

By default, editing display filename does **not** change the physical path. Physical rename is an explicit opt-in checkbox.

### Deleting media

Delete actions remove both:

- Database record
- Physical file from disk

---

## Media Folders

**URL:** `/admin/media/folders`

Media folders support nested hierarchies with optional parent folder references.

### Folder actions

- Create root or nested folder
- Rename folder
- Re-parent folder (move under another folder)
- Delete folder

### Safety rules

- A folder cannot be its own parent
- A folder cannot be moved under its own descendant
- Folder deletion is blocked if child folders exist
- Folder deletion is blocked if media items are still assigned

Use folder filtering in media list and helper panels to quickly find reusable media paths.

---

## Collections

**URL:** `/admin/collections`

Collections are structured content lists — used for things like blog posts, team members, testimonials, or FAQs. Each collection has a unique machine-readable key and an optional JSON schema that describes the shape of its entries.

### Collection fields

| Field | Required | Description |
|---|---|---|
| Name | Yes | Human-readable name (e.g. "Blog Posts") |
| Key | Yes | Machine-readable identifier, unique (e.g. `blog`) |
| Description | No | Internal notes |
| Schema (JSON) | No | JSON object describing the expected data structure of entries |

### Schema JSON

The schema JSON documents what fields each entry in the collection should contain. It is informational metadata and is not enforced at runtime.

**Example:**

```json
{
  "fields": [
    { "key": "title",     "type": "text",     "label": "Title" },
    { "key": "slug",      "type": "text",     "label": "URL slug" },
    { "key": "published", "type": "date",     "label": "Published date" },
    { "key": "body",      "type": "markdown", "label": "Content" }
  ]
}
```

### Viewing entries

The collection edit screen shows a table of all entries in the collection, with their status and last updated date. Click **Edit** to open an entry, or **Delete** to remove it.

---

## Collection Entries

**URL:** `/admin/collections/:collectionId/entries/:id/edit`

Each collection entry stores its content as a JSON object in the `data_json` field. The structure of this JSON is flexible and should match the schema defined on the collection.

### Entry fields

| Field | Required | Description |
|---|---|---|
| Status | Yes | `draft`, `published`, or `archived` |
| Data (JSON) | Yes | Valid JSON object containing the entry's content |

### Status workflow

| Status | Meaning |
|---|---|
| `draft` | Work in progress, not publicly visible |
| `published` | Live and publicly visible |
| `archived` | Hidden from public, preserved for reference |

### Adding an entry

From the collection edit screen, click **Add entry** to open the create form. Enter the status and a JSON object for the data. Click **Save Entry**.

### Editing an entry

Click **Edit** on any entry in the collection's entry table. Update the status or data JSON and click **Save Entry**.

### Deleting an entry

On the collection edit page, click **Delete** in the entry table row. The entry is deleted immediately with no undo.

### Using media in collection entry JSON

Collection entry create/edit screens include the same folder-filterable **Media Helper** panel used on page edit. Copy the media path or snippet directly into `data_json`.

---

## Design Settings

**URL:** `/admin/design`

Design settings store global CSS custom properties that are injected into every public page as a `:root {}` block. Block CSS templates can reference these values using `var(--property-name)`.

### Built-in settings

| Key | CSS Variable | Default | Field Type |
|---|---|---|---|
| `primary_color` | `--primary-color` | `#3b82f6` | Color picker |
| `secondary_color` | `--secondary-color` | `#6366f1` | Color picker |
| `font_family` | `--font-family` | `'Inter', sans-serif` | Text |
| `base_spacing` | `--base-spacing` | `1rem` | Text |
| `container_width` | `--container-width` | `1200px` | Text |
| `border_radius` | `--border-radius` | `0.5rem` | Text |

### Extra settings

Below the built-in settings, there is a table for additional custom key/value pairs. Enter any number of extra keys and values. These are also output as CSS custom properties on `:root`.

### Applying changes

Click **Save Design Settings**. The changes take effect immediately on the next page load — there is no build step or cache to clear.

### Using variables in blocks

In a block type's CSS template:

```css
.hero {
  background-color: var(--primary-color);
  padding: var(--base-spacing);
  max-width: var(--container-width);
  border-radius: var(--border-radius);
}
```

In a block type's HTML template, use the variable as a prop value or inline style if needed.
