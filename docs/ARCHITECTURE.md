# Architecture

## Overview

xcms is a custom MVC application with no external framework dependencies. The entire framework layer lives in `app/Core/` and is deliberately small. All content modelling, persistence, and rendering logic lives in `app/Models/`, `app/Repositories/`, and `app/Services/` respectively.

---

## Directory Structure

```
app/
├── Controllers/
│   ├── Admin/              # Admin panel controllers
│   │   ├── BlockTypeAdminController.php
│   │   ├── CollectionAdminController.php
│   │   ├── CollectionEntryAdminController.php
│   │   ├── DashboardController.php
│   │   ├── DesignAdminController.php
│   │   └── PageAdminController.php
│   ├── HomeController.php  # Public homepage
│   └── PageController.php  # Public page-by-slug
├── Core/
│   ├── Autoloader.php      # PSR-4-style class loader
│   ├── Config.php          # Static key/value config store
│   ├── Controller.php      # Base controller class
│   ├── Database.php        # SQLite PDO singleton
│   ├── MigrationRunner.php # SQL migration executor
│   ├── Request.php         # HTTP request abstraction
│   ├── Response.php        # HTTP response builder
│   ├── Router.php          # Route registration and dispatch
│   ├── Seeder.php          # Seed file runner
│   └── View.php            # PHP template renderer
├── Models/                 # Plain PHP data objects
├── Repositories/           # Database access layer
└── Services/               # Business logic
    ├── BlockRenderer.php   # Renders one block (HTML + CSS + JS)
    ├── CssScoper.php       # Scopes CSS to a block's data-block-id
    ├── PageRenderer.php    # Assembles a full public page
    └── TemplateEngine.php  # Mustache-style variable interpolation
```

---

## Core Classes

### `App\Core\Autoloader`

Registers an SPL autoloader that maps the `App\` namespace prefix to the `app/` directory. No Composer is required. Class names map directly to file paths — `App\Services\BlockRenderer` → `app/Services/BlockRenderer.php`.

### `App\Core\Config`

A static key-value store loaded from `config/app.php`. Access values anywhere with `Config::get('key', $default)`. Supports environment variable overrides via `getenv()` in the config file itself.

### `App\Core\Database`

A singleton wrapper around PHP's PDO with a SQLite connection. Initialized once during bootstrap via `Database::initialize($path)`. After initialization, use `Database::connection()` to get the PDO instance anywhere in the application.

All repositories receive the PDO connection through `BaseRepository::__construct()`.

### `App\Core\Router`

Supports named URL parameters using `:param` syntax in route paths. Routes are registered for both GET and POST on the same path — controllers inspect `$request->getMethod()` to distinguish form display from form submission.

A _slug fallback_ can be configured with `Router::setSlugFallback('PageController', 'show')`. When no registered route matches, the router extracts the single path segment (e.g. `/about`) and passes it as a `slug` parameter to the specified controller action.

### `App\Core\Controller`

The base class for all controllers. Provides:

- `render(string $view, array $data, string $layout)` — renders a view into a layout and sends the HTML response
- `redirect(string $url)` — sends a 302 redirect
- `json(array $data, int $status)` — sends a JSON response
- `notFound()` — sets a 404 status and renders the 404 view

### `App\Core\View`

Renders PHP view files from `app/Views/`. Extracts the `$data` array as local variables inside the template scope. Layouts are views that receive content as the `$content` variable.

### `App\Core\MigrationRunner`

Reads `.sql` files from `database/migrations/` in filename order. Each file is split into individual SQL statements on `;` boundaries. Statements are executed in a try/catch block. Errors for `ALTER TABLE … ADD COLUMN` (duplicate column) and `CREATE INDEX … IF NOT EXISTS` are silently ignored to make migrations idempotent. Applied migrations are tracked in the `migrations` table.

### `App\Core\Request`

Wraps `$_SERVER`, `$_GET`, `$_POST`, and `$_FILES`. Provides `getMethod()`, `getPath()`, `getParam(string $key)`, `getPost(string $key)`, `getQuery(string $key)`.

### `App\Core\Response`

Builds and sends HTTP responses. Provides `html(string $body)`, `json(array $data, int $status)`, `redirect(string $url)`, `setStatus(int $code)`, `send()`.

---

## Request Flow

```
Browser
  │
  └─► public/index.php
          │  require bootstrap.php  (Autoloader, Config, Database init)
          │  load routes.php
          │  register GET + POST for every route
          │  setSlugFallback('PageController', 'show')
          │
          └─► Router::dispatch(Request, Response)
                  │
                  ├─ Match registered route  ──► Controller::action()
                  │                                   │
                  ├─ Match slug fallback     ──► PageController::show()
                  │
                  └─ No match               ──► 404
```

Every route maps to a `[controller => '...', action => '...']` pair. The router instantiates the controller class from the `App\Controllers\` namespace, injecting `Request` and `Response`, then calls the action method.

---

## Rendering Flow (Public Pages)

When a visitor hits a public URL (e.g. `/`), the following happens:

```
HomeController::index()  (or PageController::show() for slug-based pages)
  │
  └─► PageRenderer::renderPublicBySlug(string $slug)
          │
          ├─ PageRepository::findPublicBySlug($slug)       → Page model
          ├─ PageBlockRepository::findByPageIdOrdered($id) → PageBlock[]
          ├─ BlockTypeRepository::find($id)                → BlockType (per block)
          │
          └─ foreach $pageBlocks as $block
                  └─► BlockRenderer::render($pageBlock, $blockType)
                          │
                          ├─ TemplateEngine::render($html_template, $props) → inner HTML
                          ├─ Wrap in <div class="cms-block" data-block-id="N">
                          └─ CssScoper::scope($css_template, $blockId)      → scoped CSS
          │
          ├─ DesignSettingRepository::getAllAsKeyValue() → :root { CSS vars }
          │
          └─ return [
               'content_html'         => combined block HTML,
               'css'                  => combined scoped block CSS,
               'js'                   → combined block JavaScript,
               'design_css_variables' => :root { --primary-color: … } block,
               'meta'                 => page title and description,
             ]
          │
          └─► View renders pages/show.php inside layouts/main.php
                  <head> outputs design_css_variables + block css in <style> tags
                  <body> outputs content_html
                  end of <body> outputs block js in <script> tags
```

---

## Repositories and Services

### Repositories

All repositories extend `App\Repositories\BaseRepository`, which provides:

| Method | Description |
|---|---|
| `find(int $id)` | Fetch one record by primary key |
| `all()` | Fetch all records from the table |
| `create(object $model)` | Insert a model, returns the new ID |
| `update(object $model)` | Update a model by its `id` |
| `delete(int $id)` | Delete by primary key |

`create()` and `update()` automatically set `created_at` and `updated_at` timestamps in ISO 8601 format.

Each repository subclass defines `$table` and `$modelClass`, and may add domain-specific query methods (e.g. `PageRepository::findPublicBySlug()`, `CollectionEntryRepository::findByCollectionId()`).

### Services

| Service | Responsibility |
|---|---|
| `TemplateEngine` | Interpolates `{{ variable }}` (HTML-escaped) and `{{{ variable }}}` (raw) placeholders in block HTML templates |
| `CssScoper` | Prefixes every CSS selector in a block's CSS template with `[data-block-id="N"]` so styles are scoped to that block |
| `BlockRenderer` | Combines TemplateEngine + CssScoper to produce the final `html`, `css`, and `js` output for a single block |
| `PageRenderer` | Orchestrates all repositories and `BlockRenderer` to assemble the complete data needed to render a public page |

---

## Admin POST Pattern

All admin mutations use HTML forms with a hidden `_action` field rather than relying on HTTP method semantics. This is because HTML forms only support `GET` and `POST`.

A single route handles both display (GET) and submission (POST) in the same controller action. The action method reads `$_POST['_action']` to dispatch to a private handler method:

```php
// PageAdminController::edit()
if ($request->getMethod() === 'POST') {
    return match ($request->getPost('_action')) {
        'save_page'      => $this->handleSavePage($id),
        'add_block'      => $this->handleAddBlock($id),
        'update_block'   => $this->handleUpdateBlock($id),
        'delete_block'   => $this->handleDeleteBlock($id),
        default          => $this->renderEditPage($id),
    };
}
```

All successful mutations end with a redirect (`redirect-after-post`) to prevent duplicate submissions on browser refresh.

---

## Design CSS Variables

Design settings are stored as key/value rows in the `design_settings` table. When a public page is rendered, `DesignSettingRepository::getAllAsKeyValue()` fetches all settings and `PageRenderer` builds a CSS `:root {}` block:

```css
:root {
    --primary-color: #3b82f6;
    --secondary-color: #6366f1;
    --font-family: 'Inter', sans-serif;
    /* … */
}
```

This block is output in the page `<head>` separately from the block-scoped CSS, so block templates can safely reference `var(--primary-color)` and similar tokens.
