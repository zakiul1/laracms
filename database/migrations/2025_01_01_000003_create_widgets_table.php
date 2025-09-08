<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_area_id')->constrained('widget_areas')->cascadeOnDelete();
            $t->string('type', 80);                      // registry key
            $t->string('title', 160)->nullable();
            $t->json('settings')->nullable();
            $t->json('visibility')->nullable();          // {rules:[],mode:"show|hide"}
            $t->string('status', 16)->default('active'); // active|inactive
            $t->integer('sort_order')->default(10);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};