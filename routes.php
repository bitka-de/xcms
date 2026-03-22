<?php

/**
 * Route definitions for xcms
 */

return [
    // Public routes
    '/' => ['controller' => 'HomeController', 'action' => 'index'],
    '/page/:slug' => ['controller' => 'PageController', 'action' => 'show'],

    // Admin routes
    '/admin' => ['controller' => 'Admin\\DashboardController', 'action' => 'index'],
    '/admin/pages' => ['controller' => 'Admin\\PageAdminController', 'action' => 'index'],
    '/admin/pages/create' => ['controller' => 'Admin\\PageAdminController', 'action' => 'create'],
    '/admin/pages/:id/edit' => ['controller' => 'Admin\\PageAdminController', 'action' => 'edit'],
    '/admin/block-types' => ['controller' => 'Admin\\BlockTypeAdminController', 'action' => 'index'],
    '/admin/block-types/create' => ['controller' => 'Admin\\BlockTypeAdminController', 'action' => 'create'],
    '/admin/block-types/:id/edit' => ['controller' => 'Admin\\BlockTypeAdminController', 'action' => 'edit'],
    '/admin/collections' => ['controller' => 'Admin\\CollectionAdminController', 'action' => 'index'],
    '/admin/collections/create' => ['controller' => 'Admin\\CollectionAdminController', 'action' => 'create'],
    '/admin/collections/:id/edit' => ['controller' => 'Admin\\CollectionAdminController', 'action' => 'edit'],
    '/admin/design' => ['controller' => 'Admin\\DesignAdminController', 'action' => 'edit'],
];
