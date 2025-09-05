<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->enum('status', ['installed', 'active'])->default('installed');
            $t->json('metadata')->nullable();     // from theme.json/config.php
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};