<?php

namespace App\Services;

use App\Repositories\MediaRepository;

class StorageQuotaService
{
    private const MAX_TOTAL_STORAGE_BYTES = 5368709120; // 5 GB

    private MediaRepository $mediaRepository;

    public function __construct(?MediaRepository $mediaRepository = null)
    {
        $this->mediaRepository = $mediaRepository ?? new MediaRepository();
    }

    public function getMaxTotalStorageBytes(): int
    {
        return self::MAX_TOTAL_STORAGE_BYTES;
    }

    public function getUsedStorageBytes(): int
    {
        return $this->mediaRepository->getUsedStorageBytes();
    }

    public function getRemainingStorageBytes(): int
    {
        return max(0, self::MAX_TOTAL_STORAGE_BYTES - $this->getUsedStorageBytes());
    }

    public function wouldExceedQuota(int $incomingBytes): bool
    {
        if ($incomingBytes <= 0) {
            return false;
        }

        return $this->getUsedStorageBytes() + $incomingBytes > self::MAX_TOTAL_STORAGE_BYTES;
    }
}
