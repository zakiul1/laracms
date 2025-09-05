<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appearance_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();  // e.g., active_theme, customizer
            $t->json('value')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('appearance_settings');
    }
};