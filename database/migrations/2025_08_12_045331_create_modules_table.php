<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();
            $table->string('name');
            $table->string('version')->nullable();

            $table->boolean('is_active')->default(false);
            $table->boolean('autoload')->default(false)->index();

            $table->integer('sort_order')->default(50)->index();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};