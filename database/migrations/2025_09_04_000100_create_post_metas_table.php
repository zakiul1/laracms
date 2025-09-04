<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_metas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $t->string('meta_key', 191);
            $t->longText('meta_value')->nullable();
            $t->timestamps();

            $t->unique(['post_id', 'meta_key']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('post_metas');
    }
};