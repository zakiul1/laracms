<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('widget_areas', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('slug', 140)->unique();           // global unique (simple)
            $t->string('description', 255)->nullable();
            $t->string('theme', 120)->nullable();        // null = global
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('widget_areas');
    }
};