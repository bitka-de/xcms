<?php

namespace App\Repositories;

use App\Models\DesignSetting;

class DesignSettingRepository extends BaseRepository
{
    protected string $table = 'design_settings';
    protected string $modelClass = DesignSetting::class;

    public function findByKey(string $key): ?DesignSetting
    {
        return $this->findBy('key', $key);
    }

    public function getAllAsKeyValue(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }

    public function getAllAsModels(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY key ASC");
        return $this->hydrateAll($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function getAllByCssVariables(): string
    {
        $settings = $this->getAllAsModels();
        $css = ':root {' . PHP_EOL;
        foreach ($settings as $setting) {
            $css .= '  --' . htmlspecialchars($setting->key) . ': ' . htmlspecialchars($setting->value) . ';' . PHP_EOL;
        }
        $css .= '}' . PHP_EOL;
        return $css;
    }
}
