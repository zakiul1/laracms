<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class PluginLoader
{
    public function basePath(): string
    {
        return config('plugins.base_path', base_path('plugins'));
    }

    /** Scan /plugins and sync to DB */
    public function scanAndSync(): void
    {
        $base = $this->basePath();
        if (!File::exists($base))
            File::makeDirectory($base, 0755, true);

        $dirs = collect(File::directories($base));

        foreach ($dirs as $dir) {
            $slug = basename($dir);
            $meta = $this->readMetadata($dir);

            // minimal metadata
            $name = $meta['name'] ?? Str::headline($slug);
            $version = $meta['version'] ?? null;
            $description = $meta['description'] ?? null;

            Plugin::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'version' => $version,
                    'author' => $meta['author'] ?? null,
                    'homepage' => $meta['homepage'] ?? null,
                    'path' => $dir,
                    'description' => $description,
                    'update_url' => $meta['update_url'] ?? null,
                    'requires' => $meta['dependencies'] ?? $meta['requires'] ?? null,
                    'extra' => $meta,
                ]
            );
        }
    }

    /** Read plugin.json if present */
    public function readMetadata(string $dir): array
    {
        $json = $dir . '/plugin.json';
        if (File::exists($json)) {
            try {
                return json_decode(File::get($json), true) ?? [];
            } catch (\Throwable $e) {
            }
        }
        return [];
    }

    /** Register active plugins' service providers (via provider_file/provider_class) */
    public function bootActive(): void
    {
        Plugin::query()->where('enabled', true)->get()->each(function (Plugin $p) {
            $meta = $this->readMetadata($p->getBasePath());

            $providerFile = $meta['provider_file'] ?? null; // e.g. "src/PluginServiceProvider.php"
            $providerClass = $meta['provider_class'] ?? null; // e.g. "MyPlugin\\PluginServiceProvider"

            if ($providerFile) {
                $full = $p->getBasePath() . '/' . $providerFile;
                if (is_file($full)) {
                    require_once $full;
                }
            }

            if ($providerClass && class_exists($providerClass)) {
                app()->register($providerClass);
            }

            // Optional bootstrap.php (returns callable)
            $bootstrap = $p->getBasePath() . '/bootstrap.php';
            if (is_file($bootstrap)) {
                $callable = require $bootstrap;
                if (is_callable($callable)) {
                    $callable(app());
                }
            }
        });
    }

    /** Handle a ZIP upload: validate, extract, register, return Plugin */
    public function installFromZip(string $zipPath): Plugin
    {
        $tmp = storage_path('app/tmp/plugins/' . uniqid('pkg_', true));
        File::ensureDirectoryExists($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Invalid ZIP file.");
        }
        $zip->extractTo($tmp);
        $zip->close();

        // detect root folder
        $entries = collect(File::directories($tmp));
        $root = $entries->count() === 1 ? $entries->first() : $tmp;

        // read metadata
        $meta = $this->readMetadata($root);
        if (!$meta)
            throw new \RuntimeException("plugin.json missing or invalid.");
        foreach (['name', 'slug', 'version'] as $required) {
            if (empty($meta[$required]))
                throw new \RuntimeException("plugin.json missing required: {$required}");
        }

        $slug = Str::slug($meta['slug']);
        $dest = $this->basePath() . '/' . $slug;

        // if exists, replace
        if (File::exists($dest)) {
            File::deleteDirectory($dest);
        }

        File::moveDirectory($root, $dest);

        // sync one plugin to DB
        $plugin = Plugin::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $meta['name'],
                'version' => $meta['version'] ?? null,
                'author' => $meta['author'] ?? null,
                'homepage' => $meta['homepage'] ?? null,
                'path' => $dest,
                'description' => $meta['description'] ?? null,
                'update_url' => $meta['update_url'] ?? null,
                'requires' => $meta['dependencies'] ?? $meta['requires'] ?? null,
                'extra' => $meta,
            ]
        );

        // Cleanup temp
        File::deleteDirectory(dirname($tmp));

        return $plugin;
    }

    /** Update plugin by ZIP (same as install but keep enabled flag) */
    public function updateFromZip(Plugin $plugin, string $zipPath): Plugin
    {
        $enabled = $plugin->enabled;
        $updated = $this->installFromZip($zipPath);
        $updated->enabled = $enabled;
        $updated->save();
        return $updated;
    }

    /** Delete plugin files (and optional uninstall script) */
    public function uninstall(Plugin $plugin): void
    {
        $meta = $this->readMetadata($plugin->getBasePath());
        // optional uninstall handler
        if (!empty($meta['uninstall'])) {
            $un = $plugin->getBasePath() . '/' . $meta['uninstall'];
            if (is_file($un)) {
                require $un; // your uninstall script can run migrations/cleanup etc.
            }
        }
        // delete dir
        if (is_dir($plugin->getBasePath())) {
            File::deleteDirectory($plugin->getBasePath());
        }
        // DB rows will be removed by controller
    }
}