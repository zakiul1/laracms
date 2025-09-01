<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('term_relationships')) {
            Schema::create('term_relationships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('object_id');        // id of post/page/media/etc.
                $table->unsignedBigInteger('term_taxonomy_id'); // FK -> term_taxonomies.id
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['object_id', 'term_taxonomy_id'], 'object_tax_unique');
                $table->index(['term_taxonomy_id']);
                $table->index(['object_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('term_relationships');
    }
};