# Installation

## Requirements

| Requirement | Minimum Version | Notes |
|---|---|---|
| PHP | 8.1 | Uses named arguments, `str_starts_with`, `match`, fibers not required |
| SQLite | 3.x | Bundled with PHP as the `pdo_sqlite` extension |
| PDO | any | Must be enabled with the `pdo_sqlite` driver |

Verify your environment before proceeding:

```bash
php --version          # must show 8.1 or higher
php -m | grep pdo      # must list pdo and pdo_sqlite
php -m | grep sqlite   # must list sqlite3
```

No Composer, Node.js, or any build tool is required.

---

## Installation Steps

### 1. Obtain the source

Clone the repository or unzip the project archive into a directory of your choice:

```bash
git clone <repository-url> xcms
cd xcms
```

Or, if you received a zip file:

```bash
unzip xcms.zip -d xcms
cd xcms
```

### 2. Review configuration

Open `config/app.php` and verify the settings:

```php
return [
    'app_name'      => 'xcms',
    'app_url'       => getenv('APP_URL') ?: 'http://localhost:8000',
    'debug'         => getenv('APP_DEBUG') === 'true',
    'database_path' => dirname(__DIR__) . '/storage/database.sqlite',
];
```

- **`app_url`** — set via the `APP_URL` environment variable, or change the fallback value directly
- **`debug`** — set `APP_DEBUG=true` in your environment to show stack traces on errors
- **`database_path`** — SQLite file location; `storage/` is created automatically by `setup.php`

### 3. Run setup

```bash
php setup.php
```

This single command:

1. Creates the `storage/` directory if it does not exist
2. Creates the SQLite database file if it does not exist
3. Runs all pending SQL migrations in `database/migrations/`
4. Runs all PHP seed files in `database/seeds/`

The command is idempotent — it is safe to run multiple times on an existing installation. Already-applied migrations and seeds are skipped.

Expected output on a fresh install:

```
[OK] Created storage directory
[OK] Created database file
[OK] Migration 001_create_pages.sql applied
[OK] Migration 002_create_block_types.sql applied
[OK] Migration 003_create_page_blocks.sql applied
[OK] Migration 004_create_collections.sql applied
[OK] Migration 005_create_collection_entries.sql applied
[OK] Migration 006_create_design_settings.sql applied
[OK] Migration 007_add_status_to_collection_entries.sql applied
[OK] Seed 001_default_design_settings.php ran
[OK] Seed 002_block_types.php ran
[OK] Seed 003_homepage.php ran
[OK] Seed 004_homepage_blocks.php ran
[OK] Seed 005_blog_collection.php ran

Setup completed successfully.
```

---

## Starting a Local Development Server

PHP's built-in web server is the recommended way to run xcms locally:

```bash
php -S localhost:8000 -t public public/router.php
```

- `-t public` sets the document root to the `public/` directory
- `public/router.php` is passed as the router script so that static assets (`admin.css`, `app.css`) are served directly while all other requests are forwarded to `public/index.php`

Then open:

- **Homepage:** [http://localhost:8000](http://localhost:8000)
- **Admin panel:** [http://localhost:8000/admin](http://localhost:8000/admin)

To use a different port:

```bash
php -S localhost:9090 -t public public/router.php
```

---

## Production / Web Server

For Apache or Nginx, point the document root at the `public/` directory and ensure all requests are rewritten to `public/index.php`.

**Apache** — add a `.htaccess` in `public/`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

**Nginx** — add to your `server` block:

```nginx
root /path/to/xcms/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php$is_args$args;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

Make sure the `storage/` directory is writable by the web server process:

```bash
chmod 755 storage/
```

---

## Troubleshooting

### "Database not initialized" error on first load

You have not run `php setup.php` yet. Run it from the project root before starting the server.

### "Class not found" errors

The autoloader maps `App\` to `app/`. Check that the file exists at the expected path and uses the correct namespace declaration. The class name must match the filename exactly (case-sensitive).

### Changes to `config/app.php` have no effect

The config file is loaded once at bootstrap. If you run a persistent FPM process, restart it after changing config.

### Migration fails with "duplicate column name"

This is handled automatically — `MigrationRunner` ignores `ALTER TABLE … ADD COLUMN` errors when the column already exists. If you see this error from outside setup, you may be running a hand-crafted SQL statement directly.

### SQLite file is not created

Check that PHP has write permission to the project directory. The `storage/` directory is created with mode `0755`. If running as a different user, adjust permissions accordingly.

### Port 8000 is already in use

Use any free port:

```bash
php -S localhost:8080 -t public public/router.php
```
