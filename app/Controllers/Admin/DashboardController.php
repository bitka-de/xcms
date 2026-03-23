<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\BlockTypeRepository;
use App\Repositories\CollectionRepository;
use App\Repositories\DesignSettingRepository;
use App\Repositories\MediaFolderRepository;
use App\Repositories\MediaRepository;
use App\Repositories\PageRepository;
use App\Services\StorageQuotaService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $pageRepository = new PageRepository();
        $blockTypeRepository = new BlockTypeRepository();
        $collectionRepository = new CollectionRepository();
        $designSettingRepository = new DesignSettingRepository();
        $mediaRepository = new MediaRepository();
        $mediaFolderRepository = new MediaFolderRepository();

        $quotaService = new StorageQuotaService($mediaRepository);
        $quotaUsage = $quotaService->getUsageSummary();

        $stats = [
            'pages' => count($pageRepository->all()),
            'public_pages' => $pageRepository->countPublic(),
            'block_types' => count($blockTypeRepository->all()),
            'collections' => count($collectionRepository->all()),
            'media' => count($mediaRepository->all()),
            'media_folders' => count($mediaFolderRepository->all()),
            'design_settings' => count($designSettingRepository->all()),
            'storage_used' => $quotaUsage['used_formatted'],
            'storage_remaining' => $quotaUsage['remaining_formatted'],
            'storage_percent' => (int) round($quotaUsage['used_percent']),
        ];

        $this->render('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'stats' => $stats,
            'flash' => null,
        ], 'admin');
    }
}
