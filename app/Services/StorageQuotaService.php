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

    public function getStorageByType(): array
    {
        $bytesByType = $this->mediaRepository->getStorageBytesByType();
        $usedBytes = array_sum($bytesByType);
        
        if ($usedBytes <= 0) {
            return [];
        }

        $typeLabels = [
            'image' => 'Images',
            'video' => 'Videos',
            'audio' => 'Audio',
            'document' => 'Documents',
        ];

        $result = [];
        foreach ($bytesByType as $type => $bytes) {
            if ($bytes > 0) {
                $percent = ($bytes / $usedBytes) * 100;
                $result[] = [
                    'type' => $type,
                    'label' => $typeLabels[$type] ?? ucfirst($type),
                    'bytes' => $bytes,
                    'formatted' => $this->formatBytes($bytes),
                    'percent' => min(100, max(0, $percent)),
                    'color' => $this->getTypeColor($type),
                ];
            }
        }

        return $result;
    }

    private function getTypeColor(string $type): string
    {
        return match ($type) {
            'image' => '#4CAF50',
            'video' => '#2196F3',
            'audio' => '#FF9800',
            'document' => '#9C27B0',
            default => '#757575',
        };
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
