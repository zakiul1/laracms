<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('laracms');
            $table->json('options')->nullable(); // footer_text, logo, colors, etc.
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};