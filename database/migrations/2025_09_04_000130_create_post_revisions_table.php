<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title')->nullable();
            $t->longText('content')->nullable();
            $t->text('excerpt')->nullable();
            $t->json('snapshot')->nullable(); // any extra fields
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('post_revisions');
    }
};