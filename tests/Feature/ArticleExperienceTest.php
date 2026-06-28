<?php

namespace Tests\Feature;

use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Services\ArticleContentExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_article_page_renders_source_content(): void
    {
        [$section, $topic] = $this->makeContext();

        $article = NewsItem::create([
            'news_topic_id' => $topic->id,
            'news_section_id' => $section->id,
            'title' => 'Signalz Story',
            'source_name' => 'Example News',
            'source_courtesy' => 'example.com',
            'url' => 'https://example.com/story',
            'canonical_url' => 'https://example.com/story',
            'hash' => NewsItem::generateHash('Signalz Story', 'https://example.com/story'),
            'published_at' => now(),
            'is_visible' => true,
            'extraction_status' => 'extracted',
            'extracted_body' => [
                'First readable paragraph from the original source article that should render on the internal page.',
                'Second readable paragraph from the original source article that should also render clearly.',
            ],
        ]);

        $response = $this->get(route('news.article', ['article' => $article->slug]));

        $response->assertOk();
        $response->assertSee('Signalz Story');
        $response->assertSee('example.com');
        $response->assertSee('First readable paragraph');
        $this->assertEquals(1, $article->fresh()->detail_views_count);
    }

    public function test_homepage_story_links_point_to_internal_article_pages(): void
    {
        [$section, $topic] = $this->makeContext();

        $article = NewsItem::create([
            'news_topic_id' => $topic->id,
            'news_section_id' => $section->id,
            'title' => 'Homepage Story',
            'source_name' => 'Example News',
            'url' => 'https://example.com/homepage-story',
            'hash' => NewsItem::generateHash('Homepage Story', 'https://example.com/homepage-story'),
            'published_at' => now(),
            'is_visible' => true,
            'extraction_status' => 'extracted',
            'extracted_body' => ['Readable body paragraph for the homepage story.'],
        ]);

        $response = $this->get(route('news.index'));

        $response->assertOk();
        $response->assertSee(route('news.article', ['article' => $article->slug]), false);
    }


    public function test_article_extraction_service_saves_cleaned_source_content(): void
    {
        [$section, $topic] = $this->makeContext();

        $article = NewsItem::create([
            'news_topic_id' => $topic->id,
            'news_section_id' => $section->id,
            'title' => 'Extraction Story',
            'source_name' => 'Example News',
            'url' => 'https://example.com/extraction-story',
            'hash' => NewsItem::generateHash('Extraction Story', 'https://example.com/extraction-story'),
            'published_at' => now(),
            'is_visible' => true,
        ]);

        Http::fake([
            'https://example.com/extraction-story' => Http::response($this->sourceHtml('Extraction Story'), 200),
        ]);

        $service = app(ArticleContentExtractionService::class);
        $service->extractForArticle($article);

        $article->refresh();

        $this->assertSame('extracted', $article->extraction_status);
        $this->assertNotEmpty($article->excerptParagraphs());
        $this->assertSame('example.com', $article->source_courtesy);
    }

    public function test_article_extraction_batch_limit_is_ten(): void
    {
        [$section, $topic] = $this->makeContext();

        for ($i = 1; $i <= 12; $i++) {
            NewsItem::create([
                'news_topic_id' => $topic->id,
                'news_section_id' => $section->id,
                'title' => "Batch Story {$i}",
                'source_name' => 'Example News',
                'url' => "https://example.com/batch-story-{$i}",
                'hash' => NewsItem::generateHash("Batch Story {$i}", "https://example.com/batch-story-{$i}"),
                'published_at' => now()->subMinutes($i),
                'is_visible' => true,
            ]);
        }

        Http::fake(function () {
            return Http::response($this->sourceHtml('Batch Story'), 200);
        });

        $stats = app(ArticleContentExtractionService::class)->processPending(10);

        $this->assertSame(10, $stats['processed']);
        $this->assertSame(10, NewsItem::where('extraction_status', 'extracted')->count());
        $this->assertSame(2, NewsItem::where('extraction_status', 'pending')->count());
    }

    public function test_fixed_and_dynamic_trend_pages_render_and_homepage_links_show(): void
    {
        [$section, $topic] = $this->makeContext();
        $trendsSection = NewsSection::firstOrCreate([
            'slug' => 'google-trends',
        ], [
            'name' => 'Google Trends',
            'description' => 'Google Trends',
            'sort_order' => 9,
            'is_active' => true,
            'is_default' => false,
            'refresh_interval_minutes' => 5,
            'card_limit' => 10,
        ]);

        $dynamicTopic = NewsTopic::create([
            'news_section_id' => $trendsSection->id,
            'name' => 'Mbappe',
            'keyword' => 'Mbappe',
            'country' => 'FR',
            'language' => 'fr',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        NewsItem::create([
            'news_topic_id' => $dynamicTopic->id,
            'news_section_id' => $trendsSection->id,
            'title' => 'Mbappe reaches new milestone',
            'source_name' => 'Le Monde',
            'url' => 'https://example.com/mbappe',
            'hash' => NewsItem::generateHash('Mbappe reaches new milestone', 'https://example.com/mbappe'),
            'published_at' => now(),
            'is_visible' => true,
            'extraction_status' => 'extracted',
            'extracted_body' => ['Dynamic trend body paragraph.'],
        ]);

        NewsItem::create([
            'news_topic_id' => $topic->id,
            'news_section_id' => $section->id,
            'title' => 'Middle East summit updates',
            'source_name' => 'BBC',
            'url' => 'https://example.com/middle-east',
            'hash' => NewsItem::generateHash('Middle East summit updates', 'https://example.com/middle-east'),
            'published_at' => now()->subMinute(),
            'is_visible' => true,
            'extraction_status' => 'extracted',
            'extracted_body' => ['Fixed trend body paragraph.'],
        ]);

        $homepage = $this->get(route('news.index'));
        $homepage->assertOk();
        $homepage->assertSee(route('news.trend-page', ['slug' => 'mbappe']), false);
        $homepage->assertDontSee(route('news.trend-page', ['slug' => 'messi']), false);

        $this->get(route('news.trend-page', ['slug' => 'middle-east']))
            ->assertOk()
            ->assertSee('Middle East summit updates');

        $this->get(route('news.trend-page', ['slug' => 'mbappe']))
            ->assertOk()
            ->assertSee('Mbappe reaches new milestone');
    }

    public function test_homepage_trending_pages_show_latest_top_three_dynamic_keywords(): void
    {
        $trendsSection = NewsSection::firstOrCreate([
            'slug' => 'google-trends',
        ], [
            'name' => 'Google Trends',
            'description' => 'Google Trends',
            'sort_order' => 9,
            'is_active' => true,
            'is_default' => false,
            'refresh_interval_minutes' => 5,
            'card_limit' => 10,
        ]);

        foreach ([
            ['name' => 'Trend One', 'keyword' => 'trend one', 'minutes' => 5],
            ['name' => 'Trend Two', 'keyword' => 'trend two', 'minutes' => 4],
            ['name' => 'Trend Three', 'keyword' => 'trend three', 'minutes' => 3],
            ['name' => 'Trend Four', 'keyword' => 'trend four', 'minutes' => 2],
            ['name' => 'Trend Five', 'keyword' => 'trend five', 'minutes' => 1],
        ] as $trendTopic) {
            $topic = NewsTopic::create([
                'news_section_id' => $trendsSection->id,
                'name' => $trendTopic['name'],
                'keyword' => $trendTopic['keyword'],
                'country' => 'US',
                'language' => 'en',
                'sort_order' => 1,
                'is_active' => true,
            ]);

            $topic->forceFill([
                'created_at' => now()->subMinutes($trendTopic['minutes']),
                'updated_at' => now()->subMinutes($trendTopic['minutes']),
            ])->saveQuietly();
        }

        $response = $this->get(route('news.index'));

        $response->assertOk();
        $response->assertSee(route('news.trend-page', ['slug' => 'trend-five']), false);
        $response->assertSee(route('news.trend-page', ['slug' => 'trend-four']), false);
        $response->assertSee(route('news.trend-page', ['slug' => 'trend-three']), false);
        $response->assertDontSee(route('news.trend-page', ['slug' => 'trend-two']), false);
        $response->assertDontSee(route('news.trend-page', ['slug' => 'messi']), false);
    }

    protected function makeContext(): array
    {
        $section = NewsSection::firstOrCreate([
            'slug' => 'world',
        ], [
            'name' => 'World',
            'description' => 'World news',
            'sort_order' => 2,
            'is_active' => true,
            'is_default' => false,
            'refresh_interval_minutes' => 10,
            'card_limit' => 6,
        ]);

        $topic = NewsTopic::create([
            'news_section_id' => $section->id,
            'name' => 'World Topic',
            'keyword' => 'world topic',
            'country' => 'US',
            'language' => 'en',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return [$section, $topic];
    }

    protected function sourceHtml(string $title): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$title}</title>
    <meta name="author" content="Reporter Name">
    <meta property="og:url" content="https://example.com/extraction-story">
    <meta property="og:image" content="https://example.com/story.jpg">
</head>
<body>
    <article>
        <p>This is the first long paragraph from the original article source that the extractor should keep for the generated detail page.</p>
        <p>This is the second long paragraph from the original article source and it should also be kept for medium-length readable excerpts.</p>
    </article>
</body>
</html>
HTML;
    }
}
