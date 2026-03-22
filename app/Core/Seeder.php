<?php

namespace App\Core;

class Seeder
{
    private string $seedsPath;

    public function __construct()
    {
        $this->seedsPath = dirname(__DIR__, 2) . '/database/seeds';
    }

    public function run(): void
    {
        $seedFiles = $this->getSeedFiles();

        if (empty($seedFiles)) {
            echo "[INFO] No seed files found.\n";
            return;
        }

        $ranSeeds = 0;

        foreach ($seedFiles as $filename) {
            $filepath = $this->seedsPath . '/' . $filename;
            echo "[RUN]  $filename\n";
            require $filepath;
            $ranSeeds++;
        }

        echo "[OK]   $ranSeeds seed file(s) executed.\n";
    }

    private function getSeedFiles(): array
    {
        if (!is_dir($this->seedsPath)) {
            return [];
        }

        $files = array_filter(
            scandir($this->seedsPath),
            fn($f) => str_ends_with($f, '.php')
        );

        sort($files);
        return array_values($files);
    }
}
