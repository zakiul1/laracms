<?php


namespace App\Support;


use Illuminate\Support\Str;


class ViteManifestLoader
{
    /**
     * Return public URL for an entry given a vite manifest path
     * @param string $manifestPath absolute path to manifest.json
     * @param string $entry e.g. 'resources/css/app.css' or 'resources/js/app.ts'
     * @param string $publicBase public base folder (e.g. 'modules/hello' or 'themes/laracms')
     */
    public function url(string $manifestPath, string $entry, string $publicBase): string
    {
        if (!is_file($manifestPath))
            return '';
        $json = json_decode(file_get_contents($manifestPath), true) ?: [];
        $key = $this->findKey($json, $entry);
        if (!$key || empty($json[$key]['file']))
            return '';
        return url(trim($publicBase, '/') . '/' . ltrim($json[$key]['file'], '/'));
    }


    protected function findKey(array $manifest, string $entry): ?string
    {
        if (isset($manifest[$entry]))
            return $entry;
        foreach (array_keys($manifest) as $k) {
            if (Str::endsWith($k, $entry))
                return $k;
        }
        return null;
    }
}