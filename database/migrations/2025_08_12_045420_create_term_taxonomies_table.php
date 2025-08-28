<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('term_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('taxonomy'); // category, tag, product-category, etc.
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['term_id', 'taxonomy']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('term_taxonomies');
    }
};