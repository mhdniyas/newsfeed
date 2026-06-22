<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('is_featured');
            $table->unsignedBigInteger('clicks_count')->default(0)->after('views_count');
            $table->timestamp('last_viewed_at')->nullable()->after('clicks_count');
            $table->timestamp('last_clicked_at')->nullable()->after('last_viewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropColumn([
                'views_count',
                'clicks_count',
                'last_viewed_at',
                'last_clicked_at',
            ]);
        });
    }
};
