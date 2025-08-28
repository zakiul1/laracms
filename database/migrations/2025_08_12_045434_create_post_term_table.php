<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_taxonomy_id')->constrained()->cascadeOnDelete();
            $table->unique(['post_id', 'term_taxonomy_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('post_term');
    }
};