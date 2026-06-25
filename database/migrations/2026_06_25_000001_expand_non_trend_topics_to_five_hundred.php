<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    protected const TARGET_TOPICS = 500;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('news_sections') || !Schema::hasTable('news_topics')) {
            return;
        }

        $sections = DB::table('news_sections')
            ->where('slug', '!=', 'google-trends')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($sections->isEmpty()) {
            return;
        }

        $topicCount = (int) DB::table('news_topics')
            ->whereIn('news_section_id', $sections->pluck('id'))
            ->where('is_active', true)
            ->count();

        if ($topicCount >= self::TARGET_TOPICS) {
            return;
        }

        $topicTemplates = [
            ['Latest Headlines', 'latest news'],
            ['Breaking News', 'breaking news'],
            ['Live Updates', 'live updates'],
            ['Analysis', 'analysis news'],
            ['Market Watch', 'market news'],
            ['Expert Opinion', 'expert opinion'],
            ['Global Roundup', 'global news'],
            ['Top Stories', 'top stories'],
        ];

        $now = now();

        while ($topicCount < self::TARGET_TOPICS) {
            $insertedInPass = 0;

            foreach ($sections as $section) {
                $baseName = trim((string) $section->name);
                $sortOrder = (int) DB::table('news_topics')
                    ->where('news_section_id', $section->id)
                    ->max('sort_order');

                foreach ($topicTemplates as [$labelSuffix, $keywordSuffix]) {
                    if ($topicCount >= self::TARGET_TOPICS) {
                        break 2;
                    }

                    $topicName = Str::limit("{$baseName} {$labelSuffix}", 255, '');
                    $keyword = Str::limit("{$baseName} {$keywordSuffix}", 255, '');

                    $exists = DB::table('news_topics')
                        ->where('news_section_id', $section->id)
                        ->whereRaw('LOWER(keyword) = ?', [Str::lower($keyword)])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $sortOrder++;

                    DB::table('news_topics')->insert([
                        'news_section_id' => $section->id,
                        'name' => $topicName,
                        'keyword' => $keyword,
                        'language' => 'en',
                        'country' => 'US',
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $topicCount++;
                    $insertedInPass++;
                }
            }

            if ($insertedInPass === 0) {
                break;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank. The migration only appends missing topics and
        // should not remove any user-managed topic records during rollback.
    }
};
