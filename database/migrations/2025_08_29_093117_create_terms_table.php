<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
            $t->string('name');                 // display name
            $t->string('slug')->index();        // unique per taxonomy
            $t->foreignId('parent_id')->nullable()->constrained('terms')->nullOnDelete();
            $t->timestamps();

            $t->unique(['taxonomy_id', 'slug']); // avoid duplicate slugs in same taxonomy
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};