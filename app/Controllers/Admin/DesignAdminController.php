<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\DesignSetting;
use App\Repositories\DesignSettingRepository;

class DesignAdminController extends Controller
{
    private DesignSettingRepository $designSettingRepository;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->designSettingRepository = new DesignSettingRepository();
    }

    public function edit(): void
    {
        $definitions = $this->getSettingDefinitions();

        if ($this->request->isPost()) {
            $form = $this->buildFormData($definitions);
            $errors = $this->validate($form);

            if ($errors !== []) {
                $this->render('admin/design/edit', [
                    'pageTitle' => 'Design Settings',
                    'settings' => $form['settings'],
                    'extraSettings' => $form['extra_settings'],
                    'definitions' => $definitions,
                    'errors' => $errors,
                    'flash' => [
                        'type' => 'error',
                        'message' => 'Please fix the validation errors.',
                    ],
                ], 'admin');
                return;
            }

            $this->saveSettings($form['settings'], $definitions);
            $this->saveExtraSettings($form['extra_settings']);

            $this->redirect('/admin/design?success=Design+settings+updated');
            return;
        }

        $loaded = $this->loadFormData($definitions);

        $this->render('admin/design/edit', [
            'pageTitle' => 'Design Settings',
            'settings' => $loaded['settings'],
            'extraSettings' => $loaded['extra_settings'],
            'definitions' => $definitions,
            'errors' => [],
            'flash' => $this->readFlashFromQuery(),
        ], 'admin');
    }

    private function getSettingDefinitions(): array
    {
        return [
            'primary_color' => ['label' => 'Primary Color', 'type' => 'color', 'default' => '#2563eb'],
            'secondary_color' => ['label' => 'Secondary Color', 'type' => 'color', 'default' => '#111827'],
            'font_family' => ['label' => 'Font Family', 'type' => 'text', 'default' => 'Georgia, serif'],
            'base_spacing' => ['label' => 'Base Spacing', 'type' => 'text', 'default' => '16px'],
            'container_width' => ['label' => 'Container Width', 'type' => 'text', 'default' => '1200px'],
            'border_radius' => ['label' => 'Border Radius', 'type' => 'text', 'default' => '10px'],
        ];
    }

    private function loadFormData(array $definitions): array
    {
        $models = $this->designSettingRepository->getAllAsModels();
        $settings = [];
        $extraSettings = [];

        foreach ($definitions as $key => $definition) {
            $settings[$key] = $definition['default'];
        }

        foreach ($models as $setting) {
            if (array_key_exists($setting->key, $definitions)) {
                $settings[$setting->key] = $setting->value;
                continue;
            }

            $extraSettings[] = [
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
            ];
        }

        if ($extraSettings === []) {
            $extraSettings[] = ['key' => '', 'value' => '', 'type' => 'text'];
        }

        return [
            'settings' => $settings,
            'extra_settings' => $extraSettings,
        ];
    }

    private function buildFormData(array $definitions): array
    {
        $settings = [];
        foreach ($definitions as $key => $definition) {
            $settings[$key] = trim((string) $this->request->getPost($key, $definition['default']));
        }

        $extraKeys = $this->request->getPost('extra_key', []);
        $extraValues = $this->request->getPost('extra_value', []);
        $extraTypes = $this->request->getPost('extra_type', []);

        $extraSettings = [];
        $count = max(count((array) $extraKeys), count((array) $extraValues), count((array) $extraTypes));
        for ($index = 0; $index < $count; $index++) {
            $extraSettings[] = [
                'key' => trim((string) ($extraKeys[$index] ?? '')),
                'value' => trim((string) ($extraValues[$index] ?? '')),
                'type' => trim((string) ($extraTypes[$index] ?? 'text')),
            ];
        }

        if ($extraSettings === []) {
            $extraSettings[] = ['key' => '', 'value' => '', 'type' => 'text'];
        }

        return [
            'settings' => $settings,
            'extra_settings' => $extraSettings,
        ];
    }

    private function validate(array $form): array
    {
        $errors = [];

        foreach ($form['settings'] as $key => $value) {
            if ($value === '') {
                $errors[$key] = 'This setting is required.';
            }
        }

        foreach ($form['extra_settings'] as $index => $setting) {
            $hasAnyValue = $setting['key'] !== '' || $setting['value'] !== '';
            if (!$hasAnyValue) {
                continue;
            }

            if ($setting['key'] === '') {
                $errors['extra_key_' . $index] = 'Extra setting key is required.';
            } elseif (!preg_match('/^[a-z0-9_-]+$/', $setting['key'])) {
                $errors['extra_key_' . $index] = 'Use lowercase letters, numbers, underscores, or dashes.';
            }

            if ($setting['value'] === '') {
                $errors['extra_value_' . $index] = 'Extra setting value is required.';
            }
        }

        return $errors;
    }

    private function saveSettings(array $settings, array $definitions): void
    {
        foreach ($settings as $key => $value) {
            $model = $this->designSettingRepository->findByKey($key) ?? new DesignSetting(['key' => $key]);
            $model->key = $key;
            $model->value = $value;
            $model->type = $definitions[$key]['type'] ?? 'text';
            $this->designSettingRepository->save($model);
        }
    }

    private function saveExtraSettings(array $extraSettings): void
    {
        foreach ($extraSettings as $setting) {
            $hasAnyValue = $setting['key'] !== '' || $setting['value'] !== '';
            if (!$hasAnyValue) {
                continue;
            }

            $model = $this->designSettingRepository->findByKey($setting['key']) ?? new DesignSetting(['key' => $setting['key']]);
            $model->key = $setting['key'];
            $model->value = $setting['value'];
            $model->type = $setting['type'] !== '' ? $setting['type'] : 'text';
            $this->designSettingRepository->save($model);
        }
    }

    private function readFlashFromQuery(): ?array
    {
        $success = trim((string) $this->request->getQuery('success', ''));
        if ($success !== '') {
            return ['type' => 'success', 'message' => $success];
        }

        $error = trim((string) $this->request->getQuery('error', ''));
        if ($error !== '') {
            return ['type' => 'error', 'message' => $error];
        }

        return null;
    }
}
