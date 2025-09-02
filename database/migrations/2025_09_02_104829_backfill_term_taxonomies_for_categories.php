<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Create a term_taxonomies row for 'category' for any term that doesn't have one yet
        DB::statement("
            INSERT INTO term_taxonomies (term_id, taxonomy, parent_id, created_at, updated_at)
            SELECT t.id, 'category', NULL, NOW(), NOW()
            FROM terms t
            WHERE NOT EXISTS (
                SELECT 1 FROM term_taxonomies tt
                WHERE tt.term_id = t.id AND tt.taxonomy = 'category'
            )
        ");
    }

    public function down(): void
    {
        // Only remove rows we inserted (safe)
        DB::statement("
            DELETE tt FROM term_taxonomies tt
            LEFT JOIN terms t ON t.id = tt.term_id
            WHERE tt.taxonomy = 'category' AND t.id IS NULL
        ");
    }
};