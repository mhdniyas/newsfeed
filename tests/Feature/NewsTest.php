<?php

namespace Tests\Feature;

use App\Models\NewsItem;
use App\Models\NewsTopic;
use App\Models\VisitorAnalytic;
use App\Services\FifaMatchService;
use App\Services\FifaOfficialNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $matchServiceMock = Mockery::mock(FifaMatchService::class);
        $matchServiceMock->shouldReceive('getScoreboard')->andReturn([
            'recent' => [],
            'upcoming' => [],
            'source_url' => 'https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/scores-fixtures',
            'synced_at' => null,
        ]);

        $this->app->instance(FifaMatchService::class, $matchServiceMock);
    }

    /**
     * Test homepage redirects to public news hub.
     */
    public function test_homepage_redirects_to_world_cup_news()
    {
        $response = $this->get('/');
        $response->assertRedirect('/world-cup-news');
    }

    /**
     * Test news list loads articles and handles UI.
     */
    public function test_news_page_loads_with_articles()
    {
        $topic = NewsTopic::create([
            'name' => 'USA World Cup',
            'keyword' => 'usa world cup 2026',
            'language' => 'en',
            'country' => 'US',
            'is_active' => true,
        ]);

        NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Test News Article',
            'source_name' => 'ESPN',
            'description' => 'Test Description',
            'url' => 'https://espn.com/test',
            'hash' => 'hash123',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        $response = $this->get('/world-cup-news');
        $response->assertStatus(200);
        $response->assertSee('Test News Article');
        $response->assertSee('ESPN');
        $response->assertSee('Audience Snapshot');
        $response->assertSee('/media/news-image/', false);
    }

    /**
     * Test filtering news items by topic.
     */
    public function test_news_page_filters_by_topic()
    {
        $topic1 = NewsTopic::create(['name' => 'Topic 1', 'keyword' => 'topic1']);
        $topic2 = NewsTopic::create(['name' => 'Topic 2', 'keyword' => 'topic2']);

        NewsItem::create([
            'news_topic_id' => $topic1->id,
            'title' => 'Topic One Article',
            'source_name' => 'ESPN',
            'url' => 'https://espn.com/1',
            'hash' => 'hash1',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        NewsItem::create([
            'news_topic_id' => $topic2->id,
            'title' => 'Topic Two Article',
            'source_name' => 'Sky Sports',
            'url' => 'https://sky.com/2',
            'hash' => 'hash2',
            'published_at' => now()->subMinute(),
            'is_visible' => true,
        ]);

        // Filter by topic 1
        $response = $this->get('/world-cup-news?topic=' . $topic1->id);
        $response->assertSee('Topic One Article');
        $response->assertSee('Topic 1');
    }

    /**
     * Test admin auth login logic and redirects.
     */
    public function test_admin_login_validation_and_access()
    {
        // Admin login page loads
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // Access dashboard without passcode -> redirects
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');

        // Submit incorrect passcode -> validation error
        $response = $this->post('/admin/login', ['passcode' => 'wrong_pass']);
        $response->assertSessionHasErrors('passcode');

        // Submit correct passcode -> redirects to admin
        $response = $this->post('/admin/login', ['passcode' => 'admin123']);
        $response->assertRedirect('/admin');
        $response->assertSessionHas('admin_authenticated', true);
    }

    /**
     * Test news:fetch Artisan command.
     */
    public function test_news_fetch_command_fetches_and_saves_articles()
    {
        $officialNewsMock = Mockery::mock(FifaOfficialNewsService::class);
        $officialNewsMock->shouldReceive('latestArticles')->once()->andReturn([]);
        $this->app->instance(FifaOfficialNewsService::class, $officialNewsMock);

        $topic = NewsTopic::create([
            'name' => 'FIFA World Cup 2026',
            'keyword' => 'fifa world cup 2026',
            'language' => 'en',
            'country' => 'US',
            'is_active' => true,
        ]);

        $rssXml = '<?xml version="1.0" encoding="utf-8"?>
        <rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
            <channel>
                <title>Google News</title>
                <item>
                    <title>Major World Cup Update - ESPN</title>
                    <link>https://espn.com/wc-update</link>
                    <pubDate>Mon, 22 Jun 2026 10:00:00 GMT</pubDate>
                    <description>Some description content</description>
                    <media:content url="https://cdn.espn.com/world-cup-update.jpg" medium="image" />
                    <source url="https://espn.com">ESPN</source>
                </item>
            </channel>
        </rss>';

        Http::fake([
            'news.google.com/*' => Http::response($rssXml, 200)
        ]);

        Artisan::call('news:fetch');

        $this->assertEquals(1, NewsItem::count());

        $item = NewsItem::first();
        $this->assertEquals('Major World Cup Update', $item->title);
        $this->assertEquals('ESPN', $item->source_name);
        $this->assertEquals('https://espn.com/wc-update', $item->url);
        $this->assertEquals('https://cdn.espn.com/world-cup-update.jpg', $item->image_url);
        $this->assertTrue($item->is_visible);
    }

    /**
     * Test news:fetch never saves more than 60 new articles in one run.
     */
    public function test_news_fetch_command_caps_saved_articles_per_run()
    {
        $officialNewsMock = Mockery::mock(FifaOfficialNewsService::class);
        $officialNewsMock->shouldNotReceive('latestArticles');
        $this->app->instance(FifaOfficialNewsService::class, $officialNewsMock);

        NewsTopic::create([
            'name' => 'FIFA World Cup 2026',
            'keyword' => 'fifa world cup 2026',
            'language' => 'en',
            'country' => 'US',
            'is_active' => true,
        ]);

        NewsTopic::create([
            'name' => 'Canada World Cup',
            'keyword' => 'canada world cup 2026',
            'language' => 'en',
            'country' => 'US',
            'is_active' => true,
        ]);

        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $params);
            $seed = preg_replace('/\W+/', '-', $params['q'] ?? 'topic');
            $items = '';

            foreach (range(1, 35) as $index) {
                $items .= "
                    <item>
                        <title>World Cup Update {$seed} {$index} - ESPN</title>
                        <link>https://espn.com/{$seed}/wc-update-{$index}</link>
                        <pubDate>Mon, 22 Jun 2026 10:00:00 GMT</pubDate>
                        <description>Some description content</description>
                        <source url=\"https://espn.com\">ESPN</source>
                    </item>";
            }

            return Http::response("<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <rss version=\"2.0\">
                <channel>
                    <title>Google News</title>
                    {$items}
                </channel>
            </rss>", 200);
        });

        Artisan::call('news:fetch');

        $this->assertEquals(60, NewsItem::count());
        $this->assertStringContainsString('Saved 60/60 allowed articles', \App\Models\Setting::get('news_sync_last_output'));
    }

    public function test_news_prune_old_command_deletes_only_low_click_old_articles()
    {
        $topic = NewsTopic::create([
            'name' => 'Retention Topic',
            'keyword' => 'retention-topic',
            'language' => 'en',
            'country' => 'US',
            'is_active' => true,
        ]);

        $deleteCandidate = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Old Low Click Story',
            'source_name' => 'Example',
            'url' => 'https://example.com/old-low-click',
            'hash' => 'old-low-click',
            'published_at' => now()->subDays(9),
            'clicks_count' => 500,
            'is_visible' => true,
        ]);

        $protectedPopular = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Old Popular Story',
            'source_name' => 'Example',
            'url' => 'https://example.com/old-popular',
            'hash' => 'old-popular',
            'published_at' => now()->subDays(9),
            'clicks_count' => 501,
            'is_visible' => true,
        ]);

        $protectedRecent = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Recent Story',
            'source_name' => 'Example',
            'url' => 'https://example.com/recent-story',
            'hash' => 'recent-story',
            'published_at' => now()->subDays(2),
            'clicks_count' => 0,
            'is_visible' => true,
        ]);

        Artisan::call('news:prune-old');

        $this->assertDatabaseMissing('news_items', ['id' => $deleteCandidate->id]);
        $this->assertDatabaseHas('news_items', ['id' => $protectedPopular->id]);
        $this->assertDatabaseHas('news_items', ['id' => $protectedRecent->id]);
    }

    /**
     * Test fallback placeholder image route renders.
     */
    public function test_placeholder_image_route_returns_svg()
    {
        $response = $this->get('/media/fifa-placeholder/test-seed.svg');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertSee('FIFA 2026', false);
    }

    /**
     * Test card image route serves the fetched image when available.
     */
    public function test_article_image_route_serves_fetched_image()
    {
        $topic = NewsTopic::create(['name' => 'Topic', 'keyword' => 'topic']);
        $article = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Image Article',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/image-article',
            'image_url' => 'https://cdn.example.com/card.png',
            'hash' => 'image-article-hash',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        Http::fake([
            'cdn.example.com/card.png' => Http::response('png-binary', 200, ['Content-Type' => 'image/png']),
        ]);

        $response = $this->get(route('media.news-image', $article));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame('png-binary', $response->getContent());
    }

    /**
     * Test card image route falls back when the article has no usable image.
     */
    public function test_article_image_route_falls_back_to_placeholder()
    {
        $topic = NewsTopic::create(['name' => 'Topic', 'keyword' => 'topic']);
        $article = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'No Image Article',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/no-image-article',
            'hash' => 'no-image-article-hash',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        $response = $this->get(route('media.news-image', $article));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertSee('FIFA 2026', false);
    }

    /**
     * Test public visit counters increment on full page loads.
     */
    public function test_public_visit_counter_increments_on_page_load()
    {
        $topic = NewsTopic::create(['name' => 'Topic', 'keyword' => 'topic']);

        NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Counter Article',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/counter',
            'hash' => 'counter-hash',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        $this->get('/world-cup-news')->assertOk();
        $this->get('/world-cup-news')->assertOk();

        $this->assertEquals('2', \App\Models\Setting::get('visits_public_total'));
        $this->assertEquals('2', \App\Models\Setting::get('visits_public_' . now()->toDateString()));
    }

    /**
     * Test visitor analytics store device, browser, IP, and live context.
     */
    public function test_visitor_context_updates_device_details_and_live_presence()
    {
        $topic = NewsTopic::create(['name' => 'Topic', 'keyword' => 'topic']);

        NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Tracking Article',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/tracking',
            'hash' => 'tracking-hash',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

        $this->withHeader('User-Agent', $userAgent)
            ->get('/world-cup-news')
            ->assertOk();

        $this->withHeader('User-Agent', $userAgent)
            ->postJson(route('analytics.visitor-context'), [
                'timezone' => 'Asia/Kolkata',
                'country_code' => 'IN',
                'page_path' => '/world-cup-news',
            ])
            ->assertOk();

        $visitor = VisitorAnalytic::first();

        $this->assertNotNull($visitor);
        $this->assertSame('Mobile', $visitor->device_type);
        $this->assertSame('Safari', $visitor->browser_name);
        $this->assertSame('iOS', $visitor->os_name);
        $this->assertSame('IN', $visitor->country_code);
        $this->assertSame('/world-cup-news', $visitor->page_path);
        $this->assertNotNull($visitor->ip_address);
    }

    /**
     * Test tracked article click increments analytics and redirects.
     */
    public function test_article_click_route_increments_clicks()
    {
        $topic = NewsTopic::create(['name' => 'Topic', 'keyword' => 'topic']);

        $article = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Tracked Click',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/tracked-click',
            'hash' => 'tracked-click-hash',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        $response = $this->get(route('news.visit', $article));

        $response->assertRedirect('https://example.com/tracked-click');
        $this->assertEquals(1, $article->fresh()->clicks_count);
    }

    /**
     * Test admin sync now starts a non-blocking background process.
     */
    public function test_admin_fetch_news_starts_background_sync()
    {
        $response = $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.fetch-news'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertContains(\App\Models\Setting::get('news_sync_status'), ['queued', 'running', 'completed']);
        $this->assertStringContainsString('Detached queue worker started', \App\Models\Setting::get('news_sync_log'));
        $this->assertSame('testing-worker', \App\Models\Setting::get('news_sync_process_id'));
    }

    /**
     * Test admin can stop a stuck sync and start a fresh one.
     */
    public function test_admin_can_stop_and_resync()
    {
        \App\Models\Setting::set('news_sync_status', 'running');
        \App\Models\Setting::set('news_sync_process_id', '12345');
        \App\Models\Setting::set('news_sync_started_at', now()->subMinutes(3)->toIso8601String());

        $response = $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.fetch-news.restart'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertContains(\App\Models\Setting::get('news_sync_status'), ['queued', 'running', 'completed']);
        $this->assertSame('testing-worker', \App\Models\Setting::get('news_sync_process_id'));
        $this->assertStringContainsString('Manual stop and resync requested', \App\Models\Setting::get('news_sync_log'));
    }

    /**
     * Test admin can update profile settings.
     */
    public function test_admin_can_update_profile_settings()
    {
        $response = $this->withSession(['admin_authenticated' => true])
            ->post('/admin/profile', [
                'name' => 'Super Admin',
                'current_passcode' => 'admin123',
                'new_passcode' => 'newsecretpass',
                'new_passcode_confirmation' => 'newsecretpass',
            ]);

        $response->assertRedirect();
        
        // Assert settings in database updated
        $this->assertEquals('Super Admin', \App\Models\Setting::get('admin_name'));
        $this->assertEquals('newsecretpass', \App\Models\Setting::get('admin_passcode'));
    }

    /**
     * Test admin destroy page ranks least-clicked articles first.
     */
    public function test_admin_destroy_page_loads_least_clicked_articles()
    {
        $topic = NewsTopic::create(['name' => 'Topic', 'keyword' => 'topic']);

        NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Zero Click Article',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/zero-click',
            'hash' => 'zero-click-hash',
            'published_at' => now()->subHour(),
            'is_visible' => true,
            'views_count' => 1,
            'clicks_count' => 0,
        ]);

        NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Popular Article',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/popular',
            'hash' => 'popular-hash',
            'published_at' => now(),
            'is_visible' => true,
            'views_count' => 15,
            'clicks_count' => 9,
        ]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.destroy'));

        $response->assertOk();
        $response->assertSee('Low-performance article cleanup');
        $response->assertSeeInOrder(['Zero Click Article', 'Popular Article']);
    }

    /**
     * Test admin can bulk delete selected articles from destroy page.
     */
    public function test_admin_can_bulk_delete_articles()
    {
        $topic = NewsTopic::create(['name' => 'Topic', 'keyword' => 'topic']);

        $deleteOne = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Delete Me One',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/delete-one',
            'hash' => 'delete-one-hash',
            'published_at' => now(),
            'is_visible' => true,
        ]);

        $deleteTwo = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Delete Me Two',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/delete-two',
            'hash' => 'delete-two-hash',
            'published_at' => now()->subMinute(),
            'is_visible' => true,
        ]);

        $keep = NewsItem::create([
            'news_topic_id' => $topic->id,
            'title' => 'Keep Me',
            'source_name' => 'FIFA',
            'url' => 'https://example.com/keep-me',
            'hash' => 'keep-me-hash',
            'published_at' => now()->subMinutes(2),
            'is_visible' => true,
        ]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.articles.bulk-delete'), [
                'article_ids' => [$deleteOne->id, $deleteTwo->id],
            ]);

        $response->assertRedirect(route('admin.destroy'));
        $this->assertDatabaseMissing('news_items', ['id' => $deleteOne->id]);
        $this->assertDatabaseMissing('news_items', ['id' => $deleteTwo->id]);
        $this->assertDatabaseHas('news_items', ['id' => $keep->id]);
    }
}
