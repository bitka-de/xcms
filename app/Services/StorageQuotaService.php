<?php

namespace App\Services;

use App\Repositories\MediaRepository;

class StorageQuotaService
{
    private const MAX_TOTAL_STORAGE_BYTES = 5368709120; // 5 GB
    private const SIZE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

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

    public function getUsageSummary(): array
    {
        $used = $this->getUsedStorageBytes();
        $total = self::MAX_TOTAL_STORAGE_BYTES;
        $remaining = max(0, $total - $used);
        $usedPercent = $total > 0 ? min(100, max(0, ($used / $total) * 100)) : 0;

        return [
            'used_bytes' => $used,
            'remaining_bytes' => $remaining,
            'total_bytes' => $total,
            'used_percent' => $usedPercent,
            'used_formatted' => $this->formatBytes($used),
            'remaining_formatted' => $this->formatBytes($remaining),
            'total_formatted' => $this->formatBytes($total),
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $size = (float) $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count(self::SIZE_UNITS) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        $precision = match (self::SIZE_UNITS[$unitIndex]) {
            'B' => 0,
            'KB' => 1,
            'MB' => 1,
            default => 2,
        };

        return number_format($size, $precision, '.', ',') . ' ' . self::SIZE_UNITS[$unitIndex];
    }

    public function wouldExceedQuota(int $incomingBytes): bool
    {
        if ($incomingBytes <= 0) {
            return false;
        }

        return $this->getUsedStorageBytes() + $incomingBytes > self::MAX_TOTAL_STORAGE_BYTES;
    }
}
