<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LegacyMediaHelper
{
    /** Try to find a disk/path/url for a legacy Media record */
    public static function resolveDiskAndPath($legacy): ?array
    {
        if (!$legacy)
            return null;

        $candidates = array_filter([
            $legacy->path ?? null,
            $legacy->file_path ?? null,
            $legacy->filepath ?? null,
            (isset($legacy->dir, $legacy->filename) ? rtrim($legacy->dir, '/') . '/' . $legacy->filename : null),
            $legacy->url ?? null, // may already be a full URL
        ]);

        $disk = $legacy->disk ?? null;

        foreach ($candidates as $cand) {
            $cand = ltrim((string) $cand, '/');

            // Full URL?
            if (Str::startsWith($cand, ['http://', 'https://'])) {
                return ['url' => $cand];
            }

            // If an explicit disk is set and file exists there
            if ($disk && Storage::disk($disk)->exists($cand)) {
                return ['disk' => $disk, 'path' => $cand];
            }

            // Try common disks
            foreach (['public', 'local', 's3'] as $tryDisk) {
                if (Storage::disk($tryDisk)->exists($cand)) {
                    return ['disk' => $tryDisk, 'path' => $cand];
                }
            }

            // Try under public/
            $publicPath = public_path($cand);
            if (is_file($publicPath)) {
                return ['public' => true, 'path' => $cand];
            }
        }

        return null;
    }

    /** Get a URL for a legacy Media record */
    public static function url($legacy): ?string
    {
        $r = self::resolveDiskAndPath($legacy);
        if (!$r)
            return null;

        if (isset($r['url']))
            return $r['url'];
        if (!empty($r['public']))
            return asset($r['path']);

        return Storage::disk($r['disk'])->url($r['path']);
    }
}