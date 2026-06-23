<?php

namespace Tests\Feature;

use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use App\Services\TrendingNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrendingNewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure google-trends section exists
        NewsSection::firstOrCreate([
            'slug' => 'google-trends',
        ], [
            'name' => 'Google Trends',
            'description' => 'Top daily Google Trends keywords by country, refreshed for the dedicated 5-minute country crawler.',
            'sort_order' => 10,
            'is_active' => true,
            'is_default' => false,
            'refresh_interval_minutes' => 5,
            'card_limit' => 10,
        ]);
    }

    /**
     * Test parallel fetch trends and updating active topics.
     */
    public function test_trends_fetching_and_updating_spots()
    {
        $usRssXml = '<?xml version="1.0" encoding="utf-8"?>
        <rss version="2.0" xmlns:ht="https://trends.google.com/trends/trendingsearches/daily">
            <channel>
                <item>
                    <title>Apple Watch</title>
                    <ht:approx_traffic>100,000+</ht:approx_traffic>
                </item>
                <item>
                    <title>Nvidia Stock</title>
                    <ht:approx_traffic>50,000+</ht:approx_traffic>
                </item>
            </channel>
        </rss>';

        $inRssXml = '<?xml version="1.0" encoding="utf-8"?>
        <rss version="2.0" xmlns:ht="https://trends.google.com/trends/trendingsearches/daily">
            <channel>
                <item>
                    <title>Virat Kohli</title>
                    <ht:approx_traffic>200,000+</ht:approx_traffic>
                </item>
                <item>
                    <title>Monsoon Update</title>
                    <ht:approx_traffic>100,000+</ht:approx_traffic>
                </item>
            </channel>
        </rss>';

        Http::fake(function ($request) use ($usRssXml, $inRssXml) {
            if (str_contains($request->url(), 'trending/rss?geo=US')) {
                return Http::response($usRssXml, 200);
            }

            if (str_contains($request->url(), 'trending/rss?geo=IN')) {
                return Http::response($inRssXml, 200);
            }

            return Http::response('<?xml version="1.0" encoding="utf-8"?><rss version="2.0"><channel></channel></rss>', 200);
        });

        $service = app(TrendingNewsService::class);
        $trends = $service->fetchTrends();

        $this->assertEquals(['Apple Watch', 'Nvidia Stock'], $trends['US']);
        $this->assertEquals(['Virat Kohli', 'Monsoon Update'], $trends['IN']);
        $this->assertArrayHasKey('BR', $trends);
        $this->assertArrayHasKey('JP', $trends);

        $stats = $service->updateKeywordSpots($trends);

        $this->assertEquals(2, $stats['US']['active']);
        $this->assertEquals(2, $stats['IN']['active']);

        $this->assertDatabaseHas('news_topics', ['keyword' => 'Apple Watch', 'country' => 'US', 'is_active' => true]);
        $this->assertDatabaseHas('news_topics', ['keyword' => 'Virat Kohli', 'country' => 'IN', 'is_active' => true]);
    }

    /**
     * Test keyword spot limits. Active topics capped at 25 per country.
     */
    public function test_keyword_spots_limit_capping()
    {
        $section = NewsSection::where('slug', 'google-trends')->first();

        for ($i = 1; $i <= 40; $i++) {
            NewsTopic::create([
                'news_section_id' => $section->id,
                'name' => "Keyword US {$i}",
                'keyword' => "Keyword US {$i}",
                'country' => 'US',
                'language' => 'en',
                'is_active' => true,
                'updated_at' => now()->subMinutes(65 - $i), // oldest first
            ]);
        }

        $incomingKeywords = [];

        foreach (range(1, 10) as $index) {
            $incomingKeywords[] = "Fresh Keyword {$index}";
        }

        $service = app(TrendingNewsService::class);
        $service->updateKeywordSpots([
            'US' => $incomingKeywords,
        ]);

        $activeCount = NewsTopic::where('news_section_id', $section->id)
            ->where('country', 'US')
            ->where('is_active', true)
            ->count();

        $inactiveCount = NewsTopic::where('news_section_id', $section->id)
            ->where('country', 'US')
            ->where('is_active', false)
            ->count();

        $this->assertEquals(10, $activeCount);
        $this->assertEquals(40, $inactiveCount);

        $this->assertFalse(NewsTopic::where('keyword', 'Keyword US 1')->first()->is_active);
        $this->assertTrue(NewsTopic::where('keyword', 'Fresh Keyword 1')->first()->is_active);
        $this->assertTrue(NewsTopic::where('keyword', 'Fresh Keyword 10')->first()->is_active);
    }

    /**
     * Test news articles fetched matching trending keywords are marked as featured.
     */
    public function test_news_saving_as_featured()
    {
        $section = NewsSection::where('slug', 'google-trends')->first();
        $topic = NewsTopic::create([
            'news_section_id' => $section->id,
            'name' => 'Apple Watch',
            'keyword' => 'Apple Watch',
            'country' => 'US',
            'language' => 'en',
            'is_active' => true,
        ]);

        $newsRssXml = '<?xml version="1.0" encoding="utf-8"?>
        <rss version="2.0">
            <channel>
                <item>
                    <title>New Apple Watch Series 10 - ESPN</title>
                    <link>https://example.com/apple-watch-10</link>
                    <pubDate>Mon, 22 Jun 2026 10:00:00 GMT</pubDate>
                    <description>Some description</description>
                    <source url="https://espn.com">ESPN</source>
                </item>
            </channel>
        </rss>';

        Http::fake([
            'news.google.com/*' => Http::response($newsRssXml, 200),
        ]);

        $service = app(TrendingNewsService::class);
        $savedCount = $service->syncTrendingNews(10);

        $this->assertEquals(1, $savedCount);

        $item = NewsItem::first();
        $this->assertEquals('New Apple Watch Series 10', $item->title);
        $this->assertEquals($topic->id, $item->news_topic_id);
        $this->assertEquals($section->id, $item->news_section_id);
        $this->assertTrue($item->is_featured);
    }

    /**
     * Test Artisan command runs fetch process.
     */
    public function test_artisan_command_fetch_trends()
    {
        $usRssXml = '<?xml version="1.0" encoding="utf-8"?>
        <rss version="2.0" xmlns:ht="https://trends.google.com/trends/trendingsearches/daily">
            <channel>
                <item>
                    <title>Apple Watch</title>
                    <ht:approx_traffic>100,000+</ht:approx_traffic>
                </item>
            </channel>
        </rss>';

        $inRssXml = '<?xml version="1.0" encoding="utf-8"?>
        <rss version="2.0" xmlns:ht="https://trends.google.com/trends/trendingsearches/daily">
            <channel>
                <item>
                    <title>Virat Kohli</title>
                    <ht:approx_traffic>200,000+</ht:approx_traffic>
                </item>
            </channel>
        </rss>';

        $newsRssXml = '<?xml version="1.0" encoding="utf-8"?>
        <rss version="2.0">
            <channel>
                <item>
                    <title>News Story - ESPN</title>
                    <link>https://example.com/story</link>
                    <pubDate>Mon, 22 Jun 2026 10:00:00 GMT</pubDate>
                    <description>Some description</description>
                    <source url="https://espn.com">ESPN</source>
                </item>
            </channel>
        </rss>';

        Http::fake(function ($request) use ($usRssXml, $inRssXml, $newsRssXml) {
            if (str_contains($request->url(), 'trending/rss?geo=US')) {
                return Http::response($usRssXml, 200);
            }

            if (str_contains($request->url(), 'trending/rss?geo=IN')) {
                return Http::response($inRssXml, 200);
            }

            if (str_contains($request->url(), 'news.google.com/')) {
                return Http::response($newsRssXml, 200);
            }

            return Http::response('<?xml version="1.0" encoding="utf-8"?><rss version="2.0"><channel></channel></rss>', 200);
        });

        $this->artisan('news:fetch-trends')
            ->assertExitCode(0);

        $trendsSection = NewsSection::where('slug', 'google-trends')->first();

        $this->assertEquals(2, NewsTopic::where('news_section_id', $trendsSection->id)->count());
        $this->assertEquals(0, NewsItem::count());

        $this->artisan('news:fetch-trends --sync-news')
            ->assertExitCode(0);

        $this->assertTrue(NewsItem::where('is_featured', true)->exists());
    }

    public function test_admin_trends_page_renders_country_keyword_snapshot()
    {
        $section = NewsSection::where('slug', 'google-trends')->first();

        NewsTopic::create([
            'news_section_id' => $section->id,
            'name' => 'Apple Watch',
            'keyword' => 'Apple Watch',
            'country' => 'US',
            'language' => 'en',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Setting::set('trends_last_synced_at', now()->toIso8601String());
        Setting::set('trends_country_stats', json_encode([
            'US' => ['fetched' => 1, 'active' => 1, 'stored' => 1],
        ]));
        Setting::set('trend_sync_meta', json_encode([
            'progress' => 40,
            'stage' => 'Processing United States Trends',
            'processed_sections' => 1,
            'total_sections' => 9,
            'stats' => ['new_articles' => 3, 'skipped_duplicates' => 1, 'images_recovered' => 2],
        ]));

        $response = $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.trends'));

        $response->assertOk();
        $response->assertSee('Country keyword monitor');
        $response->assertSee('Trend crawler progress');
        $response->assertSee('United States');
        $response->assertSee('Apple Watch');
    }

    public function test_admin_trend_sync_status_endpoint_returns_monitor_payload()
    {
        Setting::set('trend_sync_status', 'queued');
        Setting::set('trend_sync_meta', json_encode([
            'progress' => 10,
            'stage' => 'Queueing trend sync job',
            'stats' => [
                'new_articles' => 0,
                'skipped_duplicates' => 0,
                'images_recovered' => 0,
            ],
        ]));

        $response = $this->withSession(['admin_authenticated' => true])
            ->getJson(route('admin.trends.sync-status'));

        $response->assertOk();
        $response->assertJsonPath('status', 'queued');
        $response->assertJsonPath('meta.stage', 'Queueing trend sync job');
        $response->assertJsonStructure([
            'status',
            'meta',
            'log',
            'fetch_stats' => [
                'interval_minutes',
                'country_count',
                'articles_per_country',
                'next_scheduled_at',
            ],
        ]);
    }
}
