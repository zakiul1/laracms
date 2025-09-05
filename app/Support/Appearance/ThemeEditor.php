<?php

namespace App\Support\Appearance;

class ThemeEditor
{
    public function save(string $absPath, string $contents): void
    {
        if (!str_starts_with(realpath(dirname($absPath)) . DIRECTORY_SEPARATOR, realpath(config('appearance.themes_path')) . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Outside theme directory');
        }
        @mkdir(config('appearance.backups_path'), 0775, true);
        $backup = config('appearance.backups_path') . '/' . basename($absPath) . '.' . date('YmdHis') . '.bak';
        if (is_file($absPath))
            @copy($absPath, $backup);
        file_put_contents($absPath, $contents);
    }
}