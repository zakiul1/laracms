<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_area_id')->constrained('widget_areas')->cascadeOnDelete();
            $t->string('type');                   // text, html, menu, recent_posts, categories, Your\Custom\Widget
            $t->string('title')->nullable();
            $t->json('settings')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};