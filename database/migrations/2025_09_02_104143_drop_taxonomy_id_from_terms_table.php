<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // If the column isn't there, nothing to do.
        if (!Schema::hasColumn('terms', 'taxonomy_id')) {
            return;
        }

        // 1) Drop FK if it exists (discover real constraint name)
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'terms'
              AND COLUMN_NAME = 'taxonomy_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");
        if ($fk && isset($fk->name)) {
            DB::statement("ALTER TABLE `terms` DROP FOREIGN KEY `{$fk->name}`");
        }

        // 2) Drop any indexes on taxonomy_id (unique/normal)
        $indexes = DB::select("SHOW INDEX FROM `terms` WHERE Column_name = 'taxonomy_id'");
        $dropped = [];
        foreach ($indexes as $idx) {
            $name = $idx->Key_name;
            if (!isset($dropped[$name])) {
                DB::statement("ALTER TABLE `terms` DROP INDEX `{$name}`");
                $dropped[$name] = true;
            }
        }

        // 3) Finally drop the column
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('taxonomy_id');
        });
    }

    public function down(): void
    {
        // Recreate column as nullable so it won't block inserts if you roll back
        if (!Schema::hasColumn('terms', 'taxonomy_id')) {
            Schema::table('terms', function (Blueprint $table) {
                $table->unsignedBigInteger('taxonomy_id')->nullable()->after('id');
                $table->index('taxonomy_id'); // no FK on purpose
            });
        }
    }
};