<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // If you already have a 'media' table, rename this file or skip.
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            // Who uploaded (optional)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Storage info
            $table->string('disk')->default('public');
            $table->string('path');                  // e.g. media/2025/09/filename.jpg
            $table->string('filename');              // original file name (without path)
            $table->string('mime')->nullable();      // image/jpeg, image/webp, video/mp4, application/pdf
            $table->unsignedBigInteger('size')->default(0); // bytes

            // Optional image meta (null for non-images)
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Editable meta
            $table->string('title')->nullable();
            $table->string('alt')->nullable();
            $table->string('caption', 500)->nullable();

            // Soft delete so you can have Trash
            $table->softDeletes();
            $table->timestamps();

            // Helpful indexes
            $table->index(['mime']);
            $table->index(['filename']);
            $table->index(['path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};