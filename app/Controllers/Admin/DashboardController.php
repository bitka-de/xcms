<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\BlockTypeRepository;
use App\Repositories\CollectionRepository;
use App\Repositories\DesignSettingRepository;
use App\Repositories\MediaRepository;
use App\Repositories\PageRepository;

class DashboardController extends Controller
{
    public function index(): void
    {
        $pageRepository = new PageRepository();
        $blockTypeRepository = new BlockTypeRepository();
        $collectionRepository = new CollectionRepository();
        $designSettingRepository = new DesignSettingRepository();
        $mediaRepository = new MediaRepository();

        $stats = [
            'pages' => count($pageRepository->all()),
            'public_pages' => $pageRepository->countPublic(),
            'block_types' => count($blockTypeRepository->all()),
            'collections' => count($collectionRepository->all()),
            'media' => count($mediaRepository->all()),
            'design_settings' => count($designSettingRepository->all()),
        ];

        $this->render('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'stats' => $stats,
            'flash' => null,
        ], 'admin');
    }
}
