<?php

/**
 * xcms route definitions.
 *
 * The current bootstrap registers each route for both GET and POST.
 * Controllers use the request method to separate form display from form submission.
 *
 * Public slug fallback is handled in public/index.php via:
 * Router::setSlugFallback('PageController', 'show')
 */

return [
    // Public routes
    '/' => ['controller' => 'HomeController', 'action' => 'index'],
    '/page/:slug' => ['controller' => 'PageController', 'action' => 'show'],

    // Admin dashboard
    '/admin' => ['controller' => 'Admin\DashboardController', 'action' => 'index'],

    // Admin pages CRUD
    '/admin/pages' => ['controller' => 'Admin\PageAdminController', 'action' => 'index'],
    '/admin/pages/create' => ['controller' => 'Admin\PageAdminController', 'action' => 'create'],
    '/admin/pages/:id/edit' => ['controller' => 'Admin\PageAdminController', 'action' => 'edit'],

    // Admin block types CRUD
    '/admin/block-types' => ['controller' => 'Admin\BlockTypeAdminController', 'action' => 'index'],
    '/admin/block-types/create' => ['controller' => 'Admin\BlockTypeAdminController', 'action' => 'create'],
    '/admin/block-types/:id/edit' => ['controller' => 'Admin\BlockTypeAdminController', 'action' => 'edit'],

    // Admin collections CRUD
    '/admin/collections' => ['controller' => 'Admin\CollectionAdminController', 'action' => 'index'],
    '/admin/collections/create' => ['controller' => 'Admin\CollectionAdminController', 'action' => 'create'],
    '/admin/collections/:id/edit' => ['controller' => 'Admin\CollectionAdminController', 'action' => 'edit'],

    // Admin collection entries
    '/admin/collections/:collectionId/entries' => ['controller' => 'Admin\CollectionEntryAdminController', 'action' => 'index'],
    '/admin/collections/:collectionId/entries/create' => ['controller' => 'Admin\CollectionEntryAdminController', 'action' => 'create'],
    '/admin/collections/:collectionId/entries/:id/edit' => ['controller' => 'Admin\CollectionEntryAdminController', 'action' => 'edit'],

    // Admin design settings
    '/admin/design' => ['controller' => 'Admin\DesignAdminController', 'action' => 'edit'],

    // Admin media library
    '/admin/media' => ['controller' => 'Admin\MediaAdminController', 'action' => 'index'],
    '/admin/media/upload' => ['controller' => 'Admin\MediaAdminController', 'action' => 'upload'],
    '/admin/media/upload/chunk' => ['controller' => 'Admin\\MediaAdminController', 'action' => 'uploadChunk'],
    '/admin/media/edit' => ['controller' => 'Admin\MediaAdminController', 'action' => 'edit'],
    '/admin/media/delete' => ['controller' => 'Admin\MediaAdminController', 'action' => 'delete'],

    // Admin media folders
    '/admin/media/folders' => ['controller' => 'Admin\MediaAdminController', 'action' => 'folders'],
    '/admin/media/folders/create' => ['controller' => 'Admin\MediaAdminController', 'action' => 'createFolder'],
    '/admin/media/folders/edit' => ['controller' => 'Admin\MediaAdminController', 'action' => 'editFolder'],
    '/admin/media/folders/delete' => ['controller' => 'Admin\MediaAdminController', 'action' => 'deleteFolder'],

    // Backward-compatible media edit route
    '/admin/media/:id/edit' => ['controller' => 'Admin\MediaAdminController', 'action' => 'edit'],

    // Public fallback
    // Any unmatched single-segment public slug, such as /about or /contact,
    // is resolved by Router::setSlugFallback('PageController', 'show') in public/index.php.
];
