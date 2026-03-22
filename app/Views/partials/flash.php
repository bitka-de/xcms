<?php
$flashMessage = null;
$flashType = 'info';

if (isset($flash) && is_array($flash)) {
    $flashMessage = $flash['message'] ?? null;
    $flashType = $flash['type'] ?? 'info';
} elseif (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
    $flashMessage = $_SESSION['flash']['message'] ?? null;
    $flashType = $_SESSION['flash']['type'] ?? 'info';
    unset($_SESSION['flash']);
}
?>

<?php if (!empty($flashMessage)): ?>
<div class="flash flash-<?= htmlspecialchars((string) $flashType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <?= htmlspecialchars((string) $flashMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
</div>
<?php endif; ?>
