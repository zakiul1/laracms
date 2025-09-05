<?php

namespace App\Support\Appearance;

use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class ThemeManager
{
    public function basePath(): string
    {
        return config('appearance.themes_path');
    }

    public function list(): array
    {
        $dir = $this->basePath();
        if (!is_dir($dir))
            @mkdir($dir, 0775, true);

        $out = [];
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $p) {
            $slug = basename($p);
            $meta = $this->readMeta($p);
            $out[$slug] = array_merge([
                'name' => $meta['name'] ?? Str::headline($slug),
                'slug' => $slug,
                'version' => $meta['version'] ?? null,
                'author' => $meta['author'] ?? null,
                'path' => $p,
                'metadata' => $meta,
            ]);
        }
        return array_values($out);
    }

    public function syncDb(): void
    {
        $existing = Theme::pluck('id', 'slug')->all();
        foreach ($this->list() as $t) {
            Theme::updateOrCreate(
                ['slug' => $t['slug']],
                Arr::only($t, ['name', 'version', 'author', 'path', 'metadata'])
                + ['status' => Theme::where('slug', $t['slug'])->value('status') ?? 'installed']
            );
            unset($existing[$t['slug']]);
        }
        if ($existing)
            Theme::whereIn('id', array_values($existing))->delete();
    }

    public function activate(string $slug): void
    {
        DB::transaction(function () use ($slug) {
            Theme::query()->update(['status' => 'installed']);
            Theme::where('slug', $slug)->update(['status' => 'active']);
        });
    }

    public function activeSlug(): string
    {
        return Theme::where('status', 'active')->value('slug')
            ?? (Theme::query()->value('slug') ?? '_fallback');
    }

    public function viewsPath(?string $slug = null): string
    {
        $slug ??= $this->activeSlug();
        return $this->basePath() . "/$slug/views";
    }

    public function readMeta(string $themeDir): array
    {
        $json = $themeDir . '/theme.json';
        $php = $themeDir . '/config.php';
        if (is_file($json))
            return json_decode(file_get_contents($json), true) ?: [];
        if (is_file($php))
            return (array) (include $php);
        return [];
    }

    public function installZip(string $uploadedZipFullPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($uploadedZipFullPath) !== true) {
            throw new \RuntimeException('Invalid theme zip');
        }
        $top = rtrim($zip->getNameIndex(0), '/');
        $slug = basename($top);
        $zip->extractTo($this->basePath());
        $zip->close();
        $this->syncDb();
        return $slug;
    }

    public function delete(string $slug): void
    {
        $path = $this->basePath() . "/$slug";
        if (is_dir($path))
            $this->rrmdir($path);
        Theme::where('slug', $slug)->delete();
    }

    protected function rrmdir($dir): void
    {
        foreach (array_diff(scandir($dir), ['.', '..']) as $f) {
            $p = "$dir/$f";
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}