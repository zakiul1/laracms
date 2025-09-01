<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Create table if missing
        if (!Schema::hasTable('term_taxonomies')) {
            Schema::create('term_taxonomies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('term_id');       // -> terms.id
                $table->string('taxonomy');                  // e.g. 'category','tag','media_category'
                $table->text('description')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable(); // tree support within same taxonomy
                $table->unsignedBigInteger('count')->default(0);
                $table->timestamps();

                $table->index(['taxonomy']);
                $table->index(['term_id']);
                $table->index(['parent_id']);
            });
        }

        // Normalize columns if table already exists
        Schema::table('term_taxonomies', function (Blueprint $table) {
            // Drop a stray 'name' column (belongs to terms, not term_taxonomies)
            if (Schema::hasColumn('term_taxonomies', 'name')) {
                $table->dropColumn('name');
            }

            // Add missing columns if needed
            if (!Schema::hasColumn('term_taxonomies', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('term_taxonomies', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->index();
            }
            if (!Schema::hasColumn('term_taxonomies', 'count')) {
                $table->unsignedBigInteger('count')->default(0);
            }
            if (!Schema::hasColumn('term_taxonomies', 'taxonomy')) {
                $table->string('taxonomy')->index();
            }
            if (!Schema::hasColumn('term_taxonomies', 'term_id')) {
                $table->unsignedBigInteger('term_id')->index();
            }
        });
    }

    public function down(): void
    {
        // No destructive changes on down; keep structure as normalized
    }
};