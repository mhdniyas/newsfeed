<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false)->after('is_featured');
            $table->index(['is_favorite', 'published_at'], 'news_items_favorite_published_index');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex('news_items_favorite_published_index');
            $table->dropColumn('is_favorite');
        });
    }
};
