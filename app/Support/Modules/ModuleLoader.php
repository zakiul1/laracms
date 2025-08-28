<?php

namespace App\Support\Modules;

use App\Models\Module;
use Illuminate\Support\Facades\File;
use App\Support\AssetPublisher;

class ModuleLoader
{
    public function bootActive(): void
    {
        $this->syncWithFilesystem();

        $base = rtrim(config('laracms.modules_path'), DIRECTORY_SEPARATOR);
        if (!File::isDirectory($base))
            return;

        // Order: sort_order asc, then id
        $active = Module::where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->pluck('slug')->all();

        foreach ($active as $slug) {
            $dir = $base . DIRECTORY_SEPARATOR . $slug;
            if (!File::isDirectory($dir))
                continue;

            // 1) Load Providers/*.php (optional)
            $providers = $dir . '/Providers';
            if (File::isDirectory($providers)) {
                foreach (glob($providers . '/*.php') as $f)
                    require_once $f;
            }

            // 2) Register service provider (if set or guessed)
            $meta = $this->readManifest($dir);
            $provider = $meta['provider'] ?? $this->guessProvider($slug);
            if ($provider && class_exists($provider)) {
                app()->register($provider);
            }

            // 3) Include taxonomy declarations first
            foreach (['/taxonomies.php', '/taxonomy.php', '/tax.php'] as $tf) {
                if (File::exists($dir . $tf)) {
                    require $dir . $tf;
                    break;
                }
            }

            // 4) Then CPT declarations
            foreach (['/cpt.php', '/posttypes.php'] as $cf) {
                if (File::exists($dir . $cf)) {
                    require $dir . $cf;
                    break;
                }
            }

            // 5) Optional initial terms
            $terms = $dir . '/terms.php';
            if (File::exists($terms))
                require $terms;

            // 6) Boot hooks (enqueue assets, etc.)
            $boot = $dir . '/boot.php';
            if (File::exists($boot))
                require $boot;

            // 7) Auto publish dist → public/modules/{slug}
            if (config('laracms.auto_publish')) {
                app(AssetPublisher::class)->mirror(
                    $dir . '/dist',
                    config('laracms.public_modules_path') . '/' . strtolower($slug)
                );
            }
        }
    }

    protected function syncWithFilesystem(): void
    {
        $base = rtrim(config('laracms.modules_path'), DIRECTORY_SEPARATOR);
        if (!File::isDirectory($base))
            return;

        foreach (scandir($base) as $d) {
            if ($d === '.' || $d === '..')
                continue;
            $dir = $base . DIRECTORY_SEPARATOR . $d;
            if (!File::isDirectory($dir))
                continue;

            $slug = strtolower($d);
            $row = Module::where('slug', $slug)->first();
            $meta = $this->readManifest($dir);

            $priority = (int) ($meta['priority'] ?? 50);
            $autoload = (bool) ($meta['autoload'] ?? false);
            $activeByDefault = (bool) ($meta['active_by_default'] ?? false);

            if (!$row) {
                // First discovery → create row (respect autoload/active_by_default)
                $row = new Module();
                $row->slug = $slug;
                $row->name = $meta['name'] ?? $d;
                $row->version = $meta['version'] ?? '1.0.0';
                $row->is_active = $activeByDefault || $autoload;
                $row->autoload = $autoload;
                $row->sort_order = $priority;
                $row->save();
            } else {
                $dirty = false;
                if ($row->sort_order !== $priority) {
                    $row->sort_order = $priority;
                    $dirty = true;
                }
                if ((bool) $row->autoload !== $autoload) {
                    $row->autoload = $autoload;
                    $dirty = true;
                }
                if ($dirty)
                    $row->save();

                // autoload:true → ensure active
                if ($autoload && !$row->is_active) {
                    $row->is_active = true;
                    $row->save();
                }
            }
        }
    }

    protected function guessProvider(string $slug): string
    {
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
        return "\\Modules\\{$studly}\\{$studly}ServiceProvider";
    }

    protected function readManifest(string $dir): array
    {
        $json = $dir . '/module.json';
        if (is_file($json))
            return json_decode(file_get_contents($json), true) ?: [];
        return [];
    }
}