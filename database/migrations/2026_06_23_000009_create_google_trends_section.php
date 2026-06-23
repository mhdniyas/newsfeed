<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!DB::table('news_sections')->where('slug', 'google-trends')->exists()) {
            $sortOrder = (int) DB::table('news_sections')->max('sort_order') + 1;
            DB::table('news_sections')->insert([
                'name' => 'Google Trends',
                'slug' => 'google-trends',
                'description' => 'Latest trending search topics and associated news from Google Trends (US & IN).',
                'sort_order' => $sortOrder,
                'is_active' => true,
                'is_default' => false,
                'refresh_interval_minutes' => 10,
                'card_limit' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $sectionId = DB::table('news_sections')->where('slug', 'google-trends')->value('id');
        if ($sectionId) {
            DB::table('news_topics')->where('news_section_id', $sectionId)->delete();
            DB::table('news_sections')->where('id', $sectionId)->delete();
        }
    }
};
