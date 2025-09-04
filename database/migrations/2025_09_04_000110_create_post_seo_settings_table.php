<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_seo_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->unique()->constrained('posts')->cascadeOnDelete();

            // Basic SEO
            $t->string('meta_title', 255)->nullable();
            $t->text('meta_description')->nullable();
            $t->text('meta_keywords')->nullable();

            // Robots
            $t->boolean('robots_index')->default(true);
            $t->boolean('robots_follow')->default(true);

            // OpenGraph
            $t->string('og_title', 255)->nullable();
            $t->text('og_description')->nullable();
            $t->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();

            // Twitter
            $t->string('twitter_title', 255)->nullable();
            $t->text('twitter_description')->nullable();
            $t->foreignId('twitter_image_id')->nullable()->constrained('media')->nullOnDelete();

            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('post_seo_settings');
    }
};