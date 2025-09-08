<?php

namespace App\Support\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    private const CACHE_KEY = 'settings.all';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $out = [];
            foreach (Setting::all(['key', 'value']) as $row) {
                $out[$row->key] = $this->unpack($row->value);
            }
            return $out;
        });
    }

    public function get(string $key, $default = null)
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function set(string $key, $value, ?string $group = null): void
    {
        Setting::setValue($key, $value, $group);
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, mixed> $kv */
    public function setMany(array $kv, ?string $group = null): void
    {
        foreach ($kv as $k => $v)
            Setting::setValue($k, $v, $group);
        Cache::forget(self::CACHE_KEY);
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
        Cache::forget(self::CACHE_KEY);
    }

    private function unpack($val)
    {
        if (is_array($val) && array_key_exists('__scalar', $val))
            return $val['__scalar'];
        return $val;
    }
}