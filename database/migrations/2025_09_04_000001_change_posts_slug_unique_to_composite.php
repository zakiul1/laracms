<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Drop the global unique(slug) if it exists
        if ($this->indexExists('posts', 'posts_slug_unique')) {
            Schema::table('posts', fn(Blueprint $t) => $t->dropUnique('posts_slug_unique'));
        } else {
            // Some projects created it without a custom name — try the column form, but ignore errors.
            try {
                Schema::table('posts', fn(Blueprint $t) => $t->dropUnique(['slug']));
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 2) Add composite unique (type, slug) if missing
        if (!$this->indexExists('posts', 'posts_type_slug_unique')) {
            Schema::table('posts', fn(Blueprint $t) => $t->unique(['type', 'slug'], 'posts_type_slug_unique'));
        }
    }

    public function down(): void
    {
        // Revert back to global unique(slug)
        if ($this->indexExists('posts', 'posts_type_slug_unique')) {
            Schema::table('posts', fn(Blueprint $t) => $t->dropUnique('posts_type_slug_unique'));
        }

        if (!$this->indexExists('posts', 'posts_slug_unique')) {
            Schema::table('posts', fn(Blueprint $t) => $t->unique('slug', 'posts_slug_unique'));
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        // Works on MySQL/MariaDB without doctrine/dbal
        $conn = Schema::getConnection()->getName();
        $db = config("database.connections.$conn.database");

        $rows = DB::select(
            'SELECT COUNT(1) AS c
               FROM information_schema.statistics
              WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $index]
        );

        return isset($rows[0]) && (int) $rows[0]->c > 0;
    }
};