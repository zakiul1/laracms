<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('site_settings')) {
            // If table doesn’t exist yet, create the canonical shape
            DB::statement("
                CREATE TABLE `site_settings` (
                  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `key` TEXT NULL,
                  `value` LONGTEXT NULL,
                  `created_at` TIMESTAMP NULL,
                  `updated_at` TIMESTAMP NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            // Ensure columns exist
            if (!Schema::hasColumn('site_settings', 'key')) {
                DB::statement("ALTER TABLE `site_settings` ADD `key` TEXT NULL AFTER `id`");
            }
            if (!Schema::hasColumn('site_settings', 'value')) {
                DB::statement("ALTER TABLE `site_settings` ADD `value` LONGTEXT NULL");
            }
            if (!Schema::hasColumn('site_settings', 'updated_at')) {
                DB::statement("ALTER TABLE `site_settings` ADD `created_at` TIMESTAMP NULL, ADD `updated_at` TIMESTAMP NULL");
            }

            // If a previous migration tried to make it VARCHAR(255) NOT NULL, make it TEXT NULL again (no data loss).
            try {
                DB::statement("ALTER TABLE `site_settings` MODIFY `key` TEXT NULL");
            } catch (\Throwable $e) {
                // ignore if already TEXT
            }
        }

        // If you had legacy columns, copy to `key` when empty.
        try {
            if (Schema::hasColumn('site_settings', 'name')) {
                DB::statement("UPDATE `site_settings` SET `key` = COALESCE(`key`, `name`) WHERE `key` IS NULL OR `key` = ''");
            } elseif (Schema::hasColumn('site_settings', 'option_name')) {
                DB::statement("UPDATE `site_settings` SET `key` = COALESCE(`key`, `option_name`) WHERE `key` IS NULL OR `key` = ''");
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Drop any full unique index on `key` (not allowed for TEXT on some MySQL versions)
        foreach (DB::select("SHOW INDEX FROM `site_settings`") as $idx) {
            if ($idx->Column_name === 'key' && (int) ($idx->Non_unique) === 0) {
                try {
                    DB::statement("DROP INDEX `{$idx->Key_name}` ON `site_settings`");
                } catch (\Throwable $e) {
                }
            }
        }

        // Create a prefix unique index on the first 191 characters of `key`
        try {
            DB::statement("CREATE UNIQUE INDEX `site_settings_key_unique` ON `site_settings` (`key`(191))");
        } catch (\Throwable $e) {
            // If it already exists (or unsupported), silently ignore.
        }
    }

    public function down(): void
    {
        // Best-effort down: remove the prefix index; keep the safer TEXT type.
        try {
            DB::statement("DROP INDEX `site_settings_key_unique` ON `site_settings`");
        } catch (\Throwable $e) {
        }
    }
};