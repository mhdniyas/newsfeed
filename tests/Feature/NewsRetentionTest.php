<?php

namespace Tests\Feature;

use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NewsRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_deletes_only_old_low_click_non_favorites(): void
    {
        [$section, $topic] = $this->makeNewsContext();

        $eligible = $this->makeArticle($section, $topic, 'Eligible Story', 4, 10, false);
        $highClicks = $this->makeArticle($section, $topic, 'Protected High Clicks', 4, 50, false);
        $favorite = $this->makeArticle($section, $topic, 'Protected Favorite', 4, 0, true);
        $fresh = $this->makeArticle($section, $topic, 'Fresh Story', 1, 0, false);

        Artisan::call('news:prune-old');

        $this->assertDatabaseMissing('news_items', ['id' => $eligible->id]);
        $this->assertDatabaseHas('news_items', ['id' => $highClicks->id]);
        $this->assertDatabaseHas('news_items', ['id' => $favorite->id]);
        $this->assertDatabaseHas('news_items', ['id' => $fresh->id]);
    }

    public function test_bulk_delete_skips_protected_articles(): void
    {
        [$section, $topic] = $this->makeNewsContext();

        $eligible = $this->makeArticle($section, $topic, 'Eligible Story', 4, 10, false);
        $favorite = $this->makeArticle($section, $topic, 'Favorite Story', 4, 0, true);
        $highClicks = $this->makeArticle($section, $topic, 'High Click Story', 4, 50, false);

        $response = $this->withSession(['admin_authenticated' => true])->post(route('admin.articles.bulk-delete'), [
            'article_ids' => [$eligible->id, $favorite->id, $highClicks->id],
            'section' => 'all',
            'topic' => 'all',
            'sort' => 'least_clicked',
        ]);

        $response->assertRedirect(route('admin.destroy', ['section' => 'all', 'topic' => 'all', 'sort' => 'least_clicked']));
        $response->assertSessionHas('success', '1 article(s) deleted. 2 protected article(s) were skipped.');

        $this->assertDatabaseMissing('news_items', ['id' => $eligible->id]);
        $this->assertDatabaseHas('news_items', ['id' => $favorite->id]);
        $this->assertDatabaseHas('news_items', ['id' => $highClicks->id]);
    }

    public function test_favorite_toggle_marks_article_as_protected(): void
    {
        [$section, $topic] = $this->makeNewsContext();
        $article = $this->makeArticle($section, $topic, 'Toggle Favorite', 4, 0, false);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.articles.toggle-favorite', $article))
            ->assertRedirect();

        $this->assertDatabaseHas('news_items', [
            'id' => $article->id,
            'is_favorite' => true,
        ]);
    }

    protected function makeNewsContext(): array
    {
        $suffix = (string) str()->uuid();

        $section = NewsSection::create([
            'name' => 'Sports',
            'slug' => 'sports-' . $suffix,
            'description' => 'Sports',
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'refresh_interval_minutes' => 10,
            'card_limit' => 12,
        ]);

        $topic = NewsTopic::create([
            'news_section_id' => $section->id,
            'name' => 'Sports Headlines',
            'slug' => 'sports-headlines-' . $suffix,
            'keyword' => 'sports-headlines-' . $suffix,
            'country' => 'US',
            'language' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return [$section, $topic];
    }

    protected function makeArticle(NewsSection $section, NewsTopic $topic, string $title, int $daysOld, int $clicks, bool $favorite): NewsItem
    {
        return NewsItem::create([
            'news_section_id' => $section->id,
            'news_topic_id' => $topic->id,
            'title' => $title,
            'source_name' => 'Example Source',
            'description' => 'Example',
            'url' => 'https://example.com/' . str()->slug($title),
            'hash' => NewsItem::generateHash($title, 'https://example.com/' . str()->slug($title)),
            'published_at' => now()->subDays($daysOld),
            'is_visible' => true,
            'is_featured' => false,
            'is_favorite' => $favorite,
            'views_count' => 100,
            'clicks_count' => $clicks,
        ]);
    }
}
