<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $t->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $t->string('role', 32)->default('featured'); // for future roles
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();

            $t->index(['post_id', 'role', 'position']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};