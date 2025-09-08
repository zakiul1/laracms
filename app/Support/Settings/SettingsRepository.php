<?php

namespace App\Support\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsRepository
{
    private string $table = 'site_settings';
    private string $colKey;
    private string $colValue;
    private bool $hasTimestamps;

    private const KEY_PREFIX = 'settings:'; // e.g. settings:general

    public function __construct()
    {
        // Auto-detect column names so we work with your existing table too.
        $this->colKey = Schema::hasColumn($this->table, 'key') ? 'key'
            : (Schema::hasColumn($this->table, 'name') ? 'name'
                : (Schema::hasColumn($this->table, 'option_name') ? 'option_name'
                    : 'key')); // fallback (migration below will add it)

        $this->colValue = Schema::hasColumn($this->table, 'value') ? 'value'
            : (Schema::hasColumn($this->table, 'option_value') ? 'option_value'
                : (Schema::hasColumn($this->table, 'val') ? 'val'
                    : 'value')); // fallback

        $this->hasTimestamps = Schema::hasColumn($this->table, 'updated_at');
    }

    /** Get an entire page (array) */
    public function getPage(string $page): array
    {
        $row = DB::table($this->table)
            ->where($this->colKey, self::KEY_PREFIX . $page)
            ->first();

        if (!$row)
            return [];
        $val = json_decode($row->{$this->colValue} ?? '[]', true);
        return is_array($val) ? $val : [];
    }

    /** Save (replace) an entire page */
    public function setPage(string $page, array $values): void
    {
        $data = [
            $this->colKey => self::KEY_PREFIX . $page,
            $this->colValue => json_encode($values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
        if ($this->hasTimestamps) {
            $data['updated_at'] = now();
        }

        DB::table($this->table)->updateOrInsert(
            [$this->colKey => self::KEY_PREFIX . $page],
            $data
        );
    }

    /** Export all settings */
    public function exportAll(): array
    {
        $rows = DB::table($this->table)
            ->when(Schema::hasColumn($this->table, $this->colKey), fn($q) => $q)
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $compound = (string) ($r->{$this->colKey} ?? '');
            if (!str_starts_with($compound, self::KEY_PREFIX))
                continue;
            $page = substr($compound, strlen(self::KEY_PREFIX));
            $out[$page] = json_decode($r->{$this->colValue} ?? '[]', true) ?: [];
        }
        return $out;
    }

    /** Import all (overwrites existing) */
    public function importAll(array $payload): void
    {
        foreach ($payload as $page => $values) {
            if (!is_array($values))
                continue;
            $this->setPage($page, $values);
        }
    }

    /** Convenience for blade directive: get "page.key1.key2" */
    public function getDot(string $dot, $default = null)
    {
        $parts = explode('.', $dot);
        $page = array_shift($parts) ?: 'general';
        $arr = $this->getPage($page);
        foreach ($parts as $k) {
            if (!is_array($arr) || !array_key_exists($k, $arr))
                return $default;
            $arr = $arr[$k];
        }
        return $arr ?? $default;
    }
}