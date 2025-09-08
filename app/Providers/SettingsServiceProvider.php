<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\Settings\SettingsPageRegistry;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var SettingsPageRegistry $reg */
        $reg = $this->app->make(SettingsPageRegistry::class);

        $defaults = [
            'general' => ['label' => 'General', 'icon' => 'lucide-settings', 'order' => 10],
            'writing' => ['label' => 'Writing', 'icon' => 'lucide-pencil', 'order' => 20],
            'reading' => ['label' => 'Reading', 'icon' => 'lucide-book-open', 'order' => 30],
            'discussion' => ['label' => 'Discussion', 'icon' => 'lucide-message-square', 'order' => 40],
            'media' => ['label' => 'Media', 'icon' => 'lucide-image', 'order' => 50],
            'permalinks' => ['label' => 'Permalinks', 'icon' => 'lucide-link', 'order' => 60],
            'privacy' => ['label' => 'Privacy', 'icon' => 'lucide-shield', 'order' => 70],
        ];

        foreach ($defaults as $key => $meta) {
            if (!$reg->has($key)) {
                $reg->register($key, $meta);
            }
        }

        // Build the Admin menu group
        $children = [];
        foreach ($reg->all() as $key => $meta) {
            $children[] = [
                'label' => $meta['label'] ?? ucfirst($key),
                'route' => "admin.settings.$key",
                'icon' => $meta['icon'] ?? null,
                'order' => $meta['order'] ?? 0,
            ];
        }

        if ($children) {
            register_admin_menu([
                'key' => 'settings',
                'label' => 'Settings',
                'icon' => 'lucide-settings',
                'order' => 900,
                'children' => $children,
            ]);
        }
    }
}