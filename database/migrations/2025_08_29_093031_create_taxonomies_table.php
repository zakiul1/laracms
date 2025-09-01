<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('taxonomies', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 64)->unique();       // e.g. category, post_tag, brand
            $t->string('label')->nullable();        // human label
            $t->boolean('hierarchical')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomies');
    }
};