// database/migrations/2025_09_02_000000_add_soft_deletes_to_media_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('media', 'deleted_at')) {
            Schema::table('media', function (Blueprint $table) {
                $table->softDeletes(); // adds nullable deleted_at
                // optional: $table->index('deleted_at'); // helps queries
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('media', 'deleted_at')) {
            Schema::table('media', function (Blueprint $table) {
                // $table->dropSoftDeletes(); // if your Laravel supports it
                $table->dropColumn('deleted_at');
            });
        }
    }
};