# xcms

A lightweight, flat-file-inspired CMS built on PHP and SQLite. No external framework dependencies — just a clean custom MVC stack with a block-based page builder, content collections, and design settings.

---

## Features

- **Block-based page builder** — compose pages from reusable block types, each with its own HTML template, CSS, and JavaScript
- **Block types** — define reusable content blocks with Mustache-style templates (`{{ variable }}`, `{{{ raw_html }}}`)
- **CSS scoping** — each block's CSS is automatically scoped to its own `data-block-id` attribute, preventing style leakage
- **Collections** — structured content lists (blog posts, testimonials, FAQs) with a JSON-based data schema
- **Collection entries** — per-entry status workflow: `draft`, `published`, `archived`
- **Design settings** — global CSS custom properties (primary color, font family, spacing, etc.) managed via the admin UI
- **Admin panel** — complete CRUD for all content types with a clean sidebar interface
- **SQLite database** — zero-config, file-based storage in `storage/database.sqlite`
- **Idempotent migrations** — safe to re-run `setup.php` on any existing installation
- **Demo seeds** — pre-built hero block, text block, homepage, and blog collection

---

## Quick Start

**Requirements:** PHP 8.1+, SQLite extension enabled

```bash
# Clone or unzip the project
cd xcms

# Run setup (creates database, runs migrations, seeds demo content)
php setup.php

# Start the local development server
php -S localhost:8000 -t public public/router.php
```

Open [http://localhost:8000](http://localhost:8000) to see the homepage.
Open [http://localhost:8000/admin](http://localhost:8000/admin) to access the admin panel.

---

## Folder Structure

```
xcms/
├── app/
│   ├── Controllers/
│   │   ├── Admin/          # Admin CRUD controllers
│   │   ├── HomeController.php
│   │   └── PageController.php
│   ├── Core/               # Framework core (Router, Controller, Database, …)
│   ├── Models/             # Data model classes
│   ├── Repositories/       # Database access layer
│   ├── Services/           # Business logic (BlockRenderer, PageRenderer, …)
│   └── Views/
│       ├── admin/          # Admin panel views
│       ├── layouts/        # Layout wrappers (main, admin)
│       ├── pages/          # Public page views
│       └── partials/       # Reusable view partials
├── config/
│   └── app.php             # App configuration
├── database/
│   ├── migrations/         # SQL migration files
│   └── seeds/              # PHP seed files
├── docs/                   # Extended documentation
├── public/
│   ├── assets/             # CSS (admin.css, app.css)
│   ├── index.php           # Front controller
│   └── router.php          # Built-in server router
├── storage/
│   └── database.sqlite     # SQLite database (created by setup.php)
├── bootstrap.php           # App bootstrap (autoload, config, DB init)
├── routes.php              # Route definitions
└── setup.php               # Installation / migration runner
```

---

## Documentation

| File | Topic |
|---|---|
| [docs/INSTALLATION.md](docs/INSTALLATION.md) | Requirements, setup, and troubleshooting |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Application structure and request flow |
| [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) | Using the admin panel |
| [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md) | Extending xcms, creating block types |
| [docs/DATABASE.md](docs/DATABASE.md) | Schema reference for all tables |
