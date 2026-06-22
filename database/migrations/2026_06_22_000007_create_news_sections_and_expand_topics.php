<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('refresh_interval_minutes')->default(10);
            $table->unsignedInteger('card_limit')->default(6);
            $table->timestamps();
        });

        Schema::table('news_topics', function (Blueprint $table) {
            $table->foreignId('news_section_id')->nullable()->after('id')->constrained('news_sections')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->after('country');
        });

        Schema::table('news_items', function (Blueprint $table) {
            $table->foreignId('news_section_id')->nullable()->after('news_topic_id')->constrained('news_sections')->nullOnDelete();
            $table->index('news_section_id');
        });

        $now = now();
        $sections = [
            ['name' => 'FIFA 2026', 'slug' => 'fifa-2026', 'description' => 'FIFA World Cup 2026 coverage, teams, players, qualifiers, and host nation stories.', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'World', 'slug' => 'world', 'description' => 'Global headlines, diplomacy, and international developments.', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Politics', 'slug' => 'politics', 'description' => 'Government, elections, policy, and political news.', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Business', 'slug' => 'business', 'description' => 'Markets, economy, company moves, and business analysis.', 'sort_order' => 4, 'is_default' => false],
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Tech products, startups, software, and digital infrastructure.', 'sort_order' => 5, 'is_default' => false],
            ['name' => 'AI', 'slug' => 'ai', 'description' => 'Artificial intelligence platforms, research, policy, and product launches.', 'sort_order' => 6, 'is_default' => false],
            ['name' => 'Crypto', 'slug' => 'crypto', 'description' => 'Bitcoin, Ethereum, markets, regulation, and blockchain adoption.', 'sort_order' => 7, 'is_default' => false],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'Mainstream sports coverage beyond the World Cup.', 'sort_order' => 8, 'is_default' => false],
            ['name' => 'Cricket', 'slug' => 'cricket', 'description' => 'Cricket fixtures, tournaments, teams, and player updates.', 'sort_order' => 9, 'is_default' => false],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'description' => 'Film, streaming, TV, celebrity, and culture headlines.', 'sort_order' => 10, 'is_default' => false],
            ['name' => 'Health', 'slug' => 'health', 'description' => 'Health research, medicine, wellness, and public health updates.', 'sort_order' => 11, 'is_default' => false],
            ['name' => 'Science', 'slug' => 'science', 'description' => 'Space, climate, innovation, and scientific discoveries.', 'sort_order' => 12, 'is_default' => false],
        ];

        foreach ($sections as $section) {
            DB::table('news_sections')->insert([
                'name' => $section['name'],
                'slug' => $section['slug'],
                'description' => $section['description'],
                'sort_order' => $section['sort_order'],
                'is_active' => true,
                'is_default' => $section['is_default'],
                'refresh_interval_minutes' => 10,
                'card_limit' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sectionIds = DB::table('news_sections')->pluck('id', 'slug');
        $fifaSectionId = (int) $sectionIds['fifa-2026'];

        DB::table('news_topics')->orderBy('id')->get()->each(function ($topic, $index) use ($fifaSectionId) {
            DB::table('news_topics')->where('id', $topic->id)->update([
                'news_section_id' => $fifaSectionId,
                'sort_order' => $index + 1,
            ]);
        });

        $starterTopics = [
            'world' => [
                ['Global Headlines', 'global news'],
                ['International Affairs', 'international affairs'],
                ['Breaking World News', 'breaking world news'],
            ],
            'politics' => [
                ['Politics Headlines', 'politics news'],
                ['Election Watch', 'election news'],
                ['Government Policy', 'government policy news'],
            ],
            'business' => [
                ['Business Headlines', 'business news'],
                ['Economy Watch', 'economy news'],
                ['Company Moves', 'company earnings news'],
            ],
            'technology' => [
                ['Tech Headlines', 'technology news'],
                ['Startup Radar', 'startup news'],
                ['Gadgets & Devices', 'gadgets news'],
            ],
            'ai' => [
                ['AI Headlines', 'artificial intelligence news'],
                ['LLM Platforms', 'LLM news'],
                ['AI Policy', 'AI policy news'],
            ],
            'crypto' => [
                ['Bitcoin Watch', 'bitcoin news'],
                ['Ethereum Watch', 'ethereum news'],
                ['Crypto Regulation', 'crypto regulation news'],
            ],
            'sports' => [
                ['Sports Headlines', 'sports news'],
                ['Football Roundup', 'football news'],
                ['Basketball Roundup', 'basketball news'],
            ],
            'cricket' => [
                ['Cricket Headlines', 'cricket news'],
                ['ICC Coverage', 'ICC cricket news'],
                ['IPL & Leagues', 'IPL news'],
            ],
            'entertainment' => [
                ['Entertainment Headlines', 'entertainment news'],
                ['Streaming & TV', 'TV streaming news'],
                ['Celebrity Watch', 'celebrity news'],
            ],
            'health' => [
                ['Health Headlines', 'health news'],
                ['Medical Research', 'medical research news'],
                ['Public Health', 'public health news'],
            ],
            'science' => [
                ['Science Headlines', 'science news'],
                ['Space Watch', 'space news'],
                ['Climate & Research', 'climate science news'],
            ],
        ];

        foreach ($starterTopics as $slug => $topics) {
            $sectionId = (int) $sectionIds[$slug];

            foreach ($topics as $index => [$name, $keyword]) {
                $exists = DB::table('news_topics')->where('keyword', $keyword)->exists();

                if ($exists) {
                    continue;
                }

                DB::table('news_topics')->insert([
                    'news_section_id' => $sectionId,
                    'name' => $name,
                    'keyword' => $keyword,
                    'language' => 'en',
                    'country' => 'US',
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::statement('
            UPDATE news_items
            SET news_section_id = (
                SELECT news_topics.news_section_id
                FROM news_topics
                WHERE news_topics.id = news_items.news_topic_id
            )
            WHERE news_topic_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('news_section_id');
        });

        Schema::table('news_topics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('news_section_id');
            $table->dropColumn('sort_order');
        });

        Schema::dropIfExists('news_sections');
    }
};
