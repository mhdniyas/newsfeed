<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('news_sections')
            ->where('slug', 'google-trends')
            ->update([
                'name' => 'Google Trends',
                'description' => 'Top daily Google Trends keywords by country, refreshed for the dedicated 5-minute country crawler.',
                'refresh_interval_minutes' => 5,
                'card_limit' => 10,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('news_sections')
            ->where('slug', 'google-trends')
            ->update([
                'description' => 'Latest trending search topics and associated news from Google Trends (US & IN).',
                'refresh_interval_minutes' => 10,
                'card_limit' => 12,
                'updated_at' => now(),
            ]);
    }
};
