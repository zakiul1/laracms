<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Core identity
            $table->string('type')->default('post')->index(); // post,page,product,custom
            $table->string('format', 24)->nullable();         // standard,gallery,image,video,etc.

            // Main content
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();

            // Template (theme-specific)
            $table->string('template', 128)->nullable();

            // Author
            $table->unsignedBigInteger('author_id')->nullable()->index();

            // Publish workflow
            $table->enum('status', ['draft', 'pending', 'published'])->default('draft')->index();
            $table->enum('visibility', ['public', 'private', 'password'])->default('public');
            $table->string('password', 128)->nullable();

            // Scheduling
            $table->timestamp('published_at')->nullable()->index();

            // Featured + gallery
            $table->unsignedBigInteger('featured_media_id')->nullable()->index();

            // Toggles
            $table->boolean('is_sticky')->default(false)->index();
            $table->boolean('allow_comments')->default(true);
            $table->boolean('allow_pingbacks')->default(true);

            // Meta (SEO etc handled via post_meta table)
            // $table->json('meta')->nullable(); // optional, we keep separate table

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};