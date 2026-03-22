<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) ($pageTitle ?? 'Admin'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> - xcms</title>
    <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require BASE_PATH . '/app/Views/partials/admin_nav.php'; ?>
    </aside>

    <main class="admin-main">
        <?php require BASE_PATH . '/app/Views/partials/flash.php'; ?>
        <?= $content ?>
    </main>
</div>
</body>
</html>
