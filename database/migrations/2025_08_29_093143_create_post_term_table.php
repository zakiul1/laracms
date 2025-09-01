<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_term', function (Blueprint $t) {
            $t->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $t->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $t->primary(['post_id', 'term_id']);

            // Optional helpers:
            $t->unsignedInteger('sort_order')->default(0); // for ordered tax relationships if needed
            $t->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_term');
    }
};