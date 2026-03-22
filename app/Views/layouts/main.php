<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) ($pageTitle ?? 'xcms'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars((string) ($seoDescription ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <link rel="stylesheet" href="/assets/app.css">
    <?php if (!empty($globalCss)): ?>
    <style>
<?= $globalCss ?>
    </style>
    <?php endif; ?>
    <?php if (!empty($pageCss)): ?>
    <style>
<?= $pageCss ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <main class="site-content">
<?= $content ?>
    </main>

    <?php if (!empty($pageJs)): ?>
    <script>
<?= $pageJs ?>
    </script>
    <?php endif; ?>
</body>
</html>
