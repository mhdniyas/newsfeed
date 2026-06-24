<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $sections = [
        [
            'name' => 'Bollywood',
            'slug' => 'bollywood',
            'description' => 'Bollywood films, stars, trailers, box office, and industry updates.',
            'topics' => [
                ['Bollywood Headlines', 'bollywood news'],
                ['Bollywood Box Office', 'bollywood box office news'],
                ['Bollywood Celebrities', 'bollywood celebrity news'],
            ],
        ],
        [
            'name' => 'Hollywood',
            'slug' => 'hollywood',
            'description' => 'Hollywood movies, studios, awards, celebrities, and release coverage.',
            'topics' => [
                ['Hollywood Headlines', 'hollywood news'],
                ['Hollywood Box Office', 'hollywood box office news'],
                ['Hollywood Celebrities', 'hollywood celebrity news'],
            ],
        ],
        [
            'name' => 'Mollywood',
            'slug' => 'mollywood',
            'description' => 'Malayalam cinema news, stars, reviews, trailers, and release updates.',
            'topics' => [
                ['Mollywood Headlines', 'mollywood news'],
                ['Malayalam Cinema', 'malayalam cinema news'],
                ['Mollywood Stars', 'mollywood celebrity news'],
            ],
        ],
        [
            'name' => 'South India Gossips',
            'slug' => 'south-india-gossips',
            'description' => 'South Indian cinema gossip, celebrity buzz, and viral entertainment chatter.',
            'topics' => [
                ['South India Gossips', 'south india cinema gossip'],
                ['Tamil Celebrity Buzz', 'tamil celebrity gossip news'],
                ['Telugu Film Gossip', 'telugu cinema gossip news'],
            ],
        ],
        [
            'name' => 'Cinema',
            'slug' => 'cinema',
            'description' => 'General cinema coverage across industries, releases, trailers, and reviews.',
            'topics' => [
                ['Cinema Headlines', 'cinema news'],
                ['Movie Releases', 'new movie release news'],
                ['Trailer Watch', 'movie trailer news'],
            ],
        ],
    ];

    public function up(): void
    {
        $now = now();
        $sortOrder = (int) DB::table('news_sections')->max('sort_order');

        foreach ($this->sections as $section) {
            $sectionId = DB::table('news_sections')->where('slug', $section['slug'])->value('id');

            if (!$sectionId) {
                $sortOrder++;
                $sectionId = DB::table('news_sections')->insertGetId([
                    'name' => $section['name'],
                    'slug' => $section['slug'],
                    'description' => $section['description'],
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'is_default' => false,
                    'refresh_interval_minutes' => 10,
                    'card_limit' => 20,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $topicOrder = (int) DB::table('news_topics')
                ->where('news_section_id', $sectionId)
                ->max('sort_order');

            foreach ($section['topics'] as [$name, $keyword]) {
                if (DB::table('news_topics')->where('keyword', $keyword)->exists()) {
                    continue;
                }

                $topicOrder++;

                DB::table('news_topics')->insert([
                    'news_section_id' => $sectionId,
                    'name' => $name,
                    'keyword' => $keyword,
                    'language' => 'en',
                    'country' => 'IN',
                    'sort_order' => $topicOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = array_column($this->sections, 'slug');
        $sectionIds = DB::table('news_sections')->whereIn('slug', $slugs)->pluck('id');

        DB::table('news_topics')->whereIn('news_section_id', $sectionIds)->delete();
        DB::table('news_sections')->whereIn('slug', $slugs)->delete();
    }
};
