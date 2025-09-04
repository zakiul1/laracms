<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $t) {
            if (!Schema::hasColumn('posts', 'template'))
                $t->string('template')->nullable()->after('excerpt');
            if (!Schema::hasColumn('posts', 'featured_media_id'))
                $t->foreignId('featured_media_id')->nullable()->after('template');
            if (!Schema::hasColumn('posts', 'is_sticky'))
                $t->boolean('is_sticky')->default(false)->after('featured_media_id');
            if (!Schema::hasColumn('posts', 'allow_comments'))
                $t->boolean('allow_comments')->default(true)->after('is_sticky');
        });
    }
    public function down(): void
    {
        // usually leave columns in place; omit drops for safety
    }
};