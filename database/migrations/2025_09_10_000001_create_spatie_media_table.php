<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spatie_media', function (Blueprint $table) {
            $table->id();

            // same columns Spatie expects, just on `spatie_media`
            $table->uuid('uuid')->nullable()->unique();

            // polymorphic to your models (e.g., App\Models\Post)
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_type', 'model_id']);

            $table->string('collection_name');   // e.g., 'images'
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();

            $table->string('disk');              // e.g., 'public'
            $table->string('conversions_disk')->nullable();

            $table->unsignedBigInteger('size');

            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');

            $table->unsignedInteger('order_column')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spatie_media');
    }
};