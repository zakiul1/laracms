<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('term_meta', function (Blueprint $t) {
            $t->id();
            $t->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $t->string('key', 128)->index();
            $t->longText('value')->nullable();
            $t->timestamps();

            $t->unique(['term_id', 'key']); // like wp_termmeta
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_meta');
    }
};