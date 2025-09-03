<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('version')->nullable();
            $t->string('author')->nullable();
            $t->string('homepage')->nullable();
            $t->string('path'); // absolute path or relative to /plugins
            $t->text('description')->nullable();
            $t->boolean('enabled')->default(false);
            $t->string('update_url')->nullable();   // remote manifest or zip
            $t->string('checksum')->nullable();
            $t->json('requires')->nullable();       // ["other-plugin@^1.2"]
            $t->json('extra')->nullable();          // any metadata
            $t->timestamps();
        });

        Schema::create('plugin_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plugin_id')->constrained('plugins')->cascadeOnDelete();
            $t->string('key');
            $t->json('value')->nullable();
            $t->timestamps();
            $t->unique(['plugin_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_settings');
        Schema::dropIfExists('plugins');
    }
};