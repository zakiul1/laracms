<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();
                $t->text('description')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
                $t->unsignedBigInteger('parent_id')->nullable()->index();
                $t->string('title');
                $t->string('url')->nullable();
                $t->string('type')->default('custom'); // custom|page|category|post
                $t->unsignedBigInteger('type_id')->nullable(); // page/post/category id
                $t->string('target')->default('_self'); // _self|_blank
                $t->string('icon')->nullable();
                $t->integer('sort_order')->default(0)->index();
                $t->timestamps();
                $t->index(['menu_id', 'parent_id', 'sort_order']);
            });
        }

        if (!Schema::hasTable('menu_locations')) {
            Schema::create('menu_locations', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();
                $t->foreignId('menu_id')->nullable()->constrained('menus')->nullOnDelete();
                $t->timestamps();
            });
        }

        // Seed default locations (header, footer, sidebar) if none
        if (DB::table('menu_locations')->count() === 0) {
            DB::table('menu_locations')->insert([
                ['name' => 'Header', 'slug' => 'header', 'menu_id' => null, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Footer', 'slug' => 'footer', 'menu_id' => null, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Sidebar', 'slug' => 'sidebar', 'menu_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_locations');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};