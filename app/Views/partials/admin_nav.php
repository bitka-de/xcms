<?php
$currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$isMediaSection = strpos($currentPath, '/admin/media') === 0;
?>
<nav class="admin-nav">
    <h1 class="admin-logo">
        <a class="admin-logo-link" href="/admin" aria-label="Go to admin dashboard">
            <svg data-logo="logo" viewBox="0 0 163.74 42" role="img" aria-hidden="true" focusable="false">
                <path fill="#fff" fill-rule="evenodd" d="M21.2.7a20.4 20.4 0 1 1 .01 40.78 20.4 20.4 0 0 1 0-40.78m19.62 20.4a19.6 19.6 0 0 0-22.2-19.46q.82-.07 1.66-.07A18.74 18.74 0 1 1 1.62 21.95a19.6 19.6 0 0 0 39.2-.86m-2.58-.78a17.95 17.95 0 0 0-20.33-17.8q.72-.06 1.45-.06a17.08 17.08 0 1 1-17.01 18.6 17.95 17.95 0 0 0 35.89-.74M19.36 3.24A16.3 16.3 0 1 1 3.07 20.16 15.43 15.43 0 1 0 17.13 3.4q1.1-.15 2.23-.15m13.72 15.52a14.64 14.64 0 0 0-16.66-14.5q.54-.05 1.1-.05A13.77 13.77 0 1 1 3.8 19.31a14.65 14.65 0 0 0 29.28-.55M17.51 5A13 13 0 1 1 4.53 18.4 12.12 12.12 0 1 0 15.68 5.13Q16.58 5 17.5 5m10.42 12.2A11.34 11.34 0 0 0 14.9 6q.37-.03.76-.03a10.46 10.46 0 1 1-10.4 11.54 11.34 11.34 0 0 0 22.67-.3M15.67 6.76a9.68 9.68 0 1 1-9.68 9.89 8.81 8.81 0 1 0 8.2-9.78q.72-.1 1.48-.1m7.1 8.9a8.03 8.03 0 0 0-9.33-7.91l.38-.01a7.16 7.16 0 1 1-7.1 8.03 8.03 8.03 0 0 0 16.05-.1m-8.95-7.14a6.37 6.37 0 1 1-6.37 6.35 5.5 5.5 0 1 0 5.24-6.25q.55-.1 1.13-.1m3.8 5.6a4.72 4.72 0 1 0-9.44 0 4.72 4.72 0 0 0 9.43 0" clip-rule="evenodd"/>
                <path fill="#fafafa" d="M62.7 35h-6.57V7.3h6.57zm19.31 0h-6.58V7.3h6.58zm-5.93-11.48H62.09v-5.24h13.99zM91.13 35h-6.58l9.81-27.7h8.93l9.8 27.7h-6.69l-6.72-19.61-.76-3.3h-.35l-.72 3.3zm13.9-5.59H92.46l1.59-4.82h9.43zM138.51 35h-23.79v-4.9l14.6-17.6h-13.91V7.3h22.53v4.82l-14.55 17.67h15.12zm24.36 0h-21.51V7.3h21.43v5.17h-14.85v17.32h14.93zm-4.56-11.67h-10.98v-4.86h10.98z"/>
            </svg>
            <span class="admin-logo-text">xcms admin</span>
        </a>
    </h1>
    <ul>
        <li>
            <a href="/admin">
                <span class="admin-nav-link-label">
                    <span class="admin-nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 256 256" focusable="false">
                            <path d="M72 60a12 12 0 1 1-12-12 12 12 0 0 1 12 12m56-12a12 12 0 1 0 12 12 12 12 0 0 0-12-12m68 24a12 12 0 1 0-12-12 12 12 0 0 0 12 12M60 116a12 12 0 1 0 12 12 12 12 0 0 0-12-12m68 0a12 12 0 1 0 12 12 12 12 0 0 0-12-12m68 0a12 12 0 1 0 12 12 12 12 0 0 0-12-12M60 184a12 12 0 1 0 12 12 12 12 0 0 0-12-12m68 0a12 12 0 1 0 12 12 12 12 0 0 0-12-12m68 0a12 12 0 1 0 12 12 12 12 0 0 0-12-12"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </span>
            </a>
        </li>
        <li>
            <a href="/admin/pages">
                <span class="admin-nav-link-label">
                    <span class="admin-nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 256 256" focusable="false">
                            <path d="m213.66 82.34-56-56A8 8 0 0 0 152 24H56a16 16 0 0 0-16 16v176a16 16 0 0 0 16 16h144a16 16 0 0 0 16-16V88a8 8 0 0 0-2.34-5.66M160 51.31 188.69 80H160ZM200 216H56V40h88v48a8 8 0 0 0 8 8h48z"/>
                        </svg>
                    </span>
                    <span>Pages</span>
                </span>
            </a>
        </li>
        <li>
            <a href="/admin/block-types">
                <span class="admin-nav-link-label">
                    <span class="admin-nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 256 256" focusable="false">
                            <path d="m223.68 66.15-88-48.15a15.9 15.9 0 0 0-15.36 0l-88 48.17a16 16 0 0 0-8.32 14v95.64a16 16 0 0 0 8.32 14l88 48.17a15.9 15.9 0 0 0 15.36 0l88-48.17a16 16 0 0 0 8.32-14V80.18a16 16 0 0 0-8.32-14.03M128 32l80.34 44L128 120 47.66 76ZM40 90l80 43.78v85.79l-80-43.75Zm96 129.57v-85.75L216 90v85.78Z"/>
                        </svg>
                    </span>
                    <span>Block Types</span>
                </span>
            </a>
        </li>
        <li>
            <a href="/admin/collections">
                <span class="admin-nav-link-label">
                    <span class="admin-nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 256 256" focusable="false">
                            <path d="M230.91 172a8 8 0 0 1-2.91 10.91l-96 56a8 8 0 0 1-8.06 0l-96-56A8 8 0 0 1 36 169.09l92 53.65 92-53.65a8 8 0 0 1 10.91 2.91M220 121.09l-92 53.65-92-53.65a8 8 0 0 0-8 13.82l96 56a8 8 0 0 0 8.06 0l96-56a8 8 0 1 0-8.06-13.82M24 80a8 8 0 0 1 4-6.91l96-56a8 8 0 0 1 8.06 0l96 56a8 8 0 0 1 0 13.82l-96 56a8 8 0 0 1-8.06 0l-96-56A8 8 0 0 1 24 80m23.88 0L128 126.74 208.12 80 128 33.26Z"/>
                        </svg>
                    </span>
                    <span>Collections</span>
                </span>
            </a>
        </li>
        <li>
            <a href="/admin/media">
                <span class="admin-nav-link-label">
                    <span class="admin-nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 256 256" focusable="false">
                            <path d="M241.75 51.32a15.9 15.9 0 0 0-13.86-2.77l-3.48.94C205.61 54.56 170.61 64 128 64s-77.61-9.44-96.41-14.51l-3.48-.94A16 16 0 0 0 8 64v128a16 16 0 0 0 16 16 16 16 0 0 0 4.18-.55l3.18-.86C50.13 201.49 85.17 192 128 192s77.87 9.49 96.69 14.59l3.18.86A16 16 0 0 0 248 192V64a15.9 15.9 0 0 0-6.25-12.68M27.42 64.93C46.94 70.2 83.27 80 128 80s81.06-9.8 100.58-15.07L232 64v118.76l-58.07-58.07a16 16 0 0 0-22.63 0l-20 20-44-44a16 16 0 0 0-22.62 0L24 141.37V64Zm186.42 122.28a391 391 0 0 0-49-9L142.63 156l20-20Zm-186.71 3.93L24 192v-28l52-52 64.25 64.25q-6-.24-12.25-.25c-45 0-82.72 10.23-100.87 15.14M192 108a12 12 0 1 1 12 12 12 12 0 0 1-12-12"/>
                        </svg>
                    </span>
                    <span>Media</span>
                </span>
            </a>
            <?php if ($isMediaSection): ?>
                <ul class="admin-subnav">
                    <li>
                        <a href="/admin/media/folders">
                            <span class="admin-nav-link-label">
                                <span class="admin-nav-icon" aria-hidden="true">
                                    <svg viewBox="0 0 256 256" focusable="false">
                                        <path d="M245 110.64a16 16 0 0 0-13-6.64h-16V88a16 16 0 0 0-16-16h-69.33l-27.73-20.8a16 16 0 0 0-9.6-3.2H40a16 16 0 0 0-16 16v144a8 8 0 0 0 8 8h179.1a8 8 0 0 0 7.59-5.47l28.49-85.47a16 16 0 0 0-2.18-14.42M93.34 64l29.86 22.4A8 8 0 0 0 128 88h72v16H69.77a16 16 0 0 0-15.18 10.94L40 158.7V64Zm112 136H43.1l26.67-80H232Z"/>
                                    </svg>
                                </span>
                                <span>Media Folders</span>
                            </span>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </li>
        <li>
            <a href="/admin/design">
                <span class="admin-nav-link-label">
                    <span class="admin-nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 256 256" focusable="false">
                            <path d="M230.64 49.36a32 32 0 0 0-45.26 0 32 32 0 0 0-5.16 6.76L152 48.42a32 32 0 0 0-54.63-23.06 32.1 32.1 0 0 0-5.76 37.41L57.67 93.32a32.05 32.05 0 0 0-40.31 4.05 32 32 0 0 0 42.89 47.41l70 51.36a32 32 0 1 0 47.57-14.69l27.39-77.59q1.38.12 2.76.12a32 32 0 0 0 22.63-54.62Zm-122-12.69a16 16 0 1 1 0 22.64 16 16 0 0 1 .04-22.64Zm-80 94.65a16 16 0 0 1 0-22.64 16 16 0 1 1 0 22.64m142.65 88a16 16 0 0 1-22.63-22.63 16 16 0 1 1 22.63 22.63m-8.55-43.18a32 32 0 0 0-23 7.08l-70-51.36a32.2 32.2 0 0 0-1.34-26.65l33.95-30.55a32 32 0 0 0 45.47-10.81L176 71.56a32 32 0 0 0 14.12 27ZM219.3 83.3a16 16 0 1 1-22.6-22.62 16 16 0 0 1 22.63 22.63Z"/>
                        </svg>
                    </span>
                    <span>Design Settings</span>
                </span>
            </a>
        </li>
    </ul>
</nav>
