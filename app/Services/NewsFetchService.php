<?php

namespace App\Services;

use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NewsFetchService
{
    protected int $maxItemsPerTopic = 12;

    protected int $openGraphBudgetPerSection = 4;

    public function __construct(
        protected FifaOfficialNewsService $fifaOfficialNewsService,
        protected NewsSyncStateService $syncState
    ) {
    }

    public function fetchSection(NewsSection $section, int $cycleLimit = 120): array
    {
        $topics = $section->newsTopics()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        $sectionLimit = max(1, (int) ($section->card_limit ?: 6));
        $stats = [
            'new_articles' => 0,
            'google_articles' => 0,
            'official_articles' => 0,
            'skipped_duplicates' => 0,
            'images_recovered' => 0,
        ];
        $openGraphAttempts = 0;
        $processedTopics = 0;
        $totalTopics = max(1, $topics->count());

        $this->syncState->beginSection($section, $topics->count());

        foreach ($topics as $topic) {
            if ($stats['new_articles'] >= $sectionLimit || $this->syncState->globalLimitReached()) {
                break;
            }

            $this->syncState->appendLog("Fetching Google News feed for {$section->name} / {$topic->name}.", 'info');

            $encodedKeyword = urlencode($topic->keyword);
            $hl = $topic->language ?: 'en';
            $gl = $topic->country ?: 'US';
            $url = "https://news.google.com/rss/search?q={$encodedKeyword}&hl={$hl}&gl={$gl}&ceid={$gl}:{$hl}";

            try {
                $response = Http::timeout(10)->get($url);

                if (!$response->successful()) {
                    $this->syncState->appendLog("RSS request failed for {$topic->name} with HTTP {$response->status()}.", 'error');
                    $processedTopics++;
                    continue;
                }

                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if (!$xml || !isset($xml->channel->item)) {
                    $this->syncState->appendLog("No RSS items found for {$topic->name}.", 'warning');
                    $processedTopics++;
                    continue;
                }

                $items = array_slice(iterator_to_array($xml->channel->item, false), 0, $this->maxItemsPerTopic);
                $totalItems = max(1, count($items));

                foreach ($items as $itemIndex => $item) {
                    if ($stats['new_articles'] >= $sectionLimit || $this->syncState->globalLimitReached()) {
                        break;
                    }

                    $title = (string) $item->title;
                    $link = (string) $item->link;
                    $description = (string) $item->description;
                    $pubDate = (string) $item->pubDate;
                    $itemNumber = $itemIndex + 1;

                    $this->syncState->updateTopicProgress(
                        $section,
                        $topic->name,
                        $processedTopics + 1,
                        $topics->count(),
                        $itemNumber,
                        $totalItems,
                        Str::limit(trim($title), 120)
                    );

                    if ($title === '' || $link === '') {
                        continue;
                    }

                    $sourceName = isset($item->source) ? (string) $item->source : '';

                    if ($sourceName === '' && str_contains($title, ' - ')) {
                        $parts = explode(' - ', $title);
                        $sourceName = trim(array_pop($parts));
                        $title = implode(' - ', $parts);
                    }

                    $sourceName = $sourceName ?: 'Google News';
                    $suffix = ' - ' . $sourceName;

                    if (str_ends_with($title, $suffix)) {
                        $title = substr($title, 0, -strlen($suffix));
                    }

                    $imageUrl = $this->extractImageUrl(
                        $item,
                        $description,
                        $link,
                        $openGraphAttempts < $this->openGraphBudgetPerSection
                    );

                    if ($imageUrl && !str_contains($description, $imageUrl)) {
                        $stats['images_recovered']++;
                        $openGraphAttempts++;
                    }

                    $cleanDescription = html_entity_decode(strip_tags($description));
                    $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription ?: '');
                    $cleanDescription = Str::limit(trim((string) $cleanDescription), 250);
                    $hash = NewsItem::generateHash($title, $link);

                    if (NewsItem::where('hash', $hash)->exists()) {
                        $stats['skipped_duplicates']++;
                        continue;
                    }

                    try {
                        $publishedAt = Carbon::parse($pubDate);
                    } catch (\Throwable) {
                        $publishedAt = now();
                    }

                    NewsItem::create([
                        'news_topic_id' => $topic->id,
                        'news_section_id' => $section->id,
                        'title' => Str::limit(trim($title), 490),
                        'source_name' => trim($sourceName),
                        'description' => $cleanDescription ?: null,
                        'url' => $link,
                        'image_url' => $imageUrl,
                        'hash' => $hash,
                        'published_at' => $publishedAt,
                        'is_visible' => true,
                        'is_featured' => false,
                    ]);

                    $stats['new_articles']++;
                    $stats['google_articles']++;
                    $this->syncState->accumulateStats([
                        'new_articles' => 1,
                        'google_articles' => 1,
                    ]);

                    if ($imageUrl && !str_contains($description, $imageUrl)) {
                        $this->syncState->accumulateStats([
                            'images_recovered' => 1,
                        ]);
                    }
                }

                $processedTopics++;
                $this->syncState->appendLog("Topic {$topic->name} processed in {$section->name}.", 'success');
            } catch (\Throwable $exception) {
                $processedTopics++;
                $this->syncState->appendLog("Topic {$topic->name} failed: {$exception->getMessage()}", 'error');
            }
        }

        if ($section->slug === 'fifa-2026' && !$this->syncState->globalLimitReached() && $stats['new_articles'] < $sectionLimit) {
            $officialCount = $this->syncOfficialFifaArticles($section, $topics->first(), min($sectionLimit - $stats['new_articles'], $cycleLimit - $this->syncState->savedArticles()));
            if ($officialCount > 0) {
                $stats['new_articles'] += $officialCount;
                $stats['official_articles'] += $officialCount;
                $this->syncState->accumulateStats([
                    'new_articles' => $officialCount,
                    'official_articles' => $officialCount,
                ]);
            }
            $this->syncState->appendLog("Official FIFA sync saved {$officialCount} articles for {$section->name}.", 'success');
        }

        if ($stats['skipped_duplicates'] > 0) {
            $this->syncState->accumulateStats([
                'skipped_duplicates' => $stats['skipped_duplicates'],
            ]);
        }

        return $stats;
    }

    protected function syncOfficialFifaArticles(NewsSection $section, ?NewsTopic $topic, int $remainingSlots): int
    {
        if ($remainingSlots <= 0 || !$topic) {
            return 0;
        }

        $saved = 0;

        foreach ($this->fifaOfficialNewsService->latestArticles(min(12, $remainingSlots)) as $article) {
            if ($saved >= $remainingSlots || $this->syncState->globalLimitReached()) {
                break;
            }

            $hash = NewsItem::generateHash($article['title'], $article['url']);

            if (NewsItem::where('hash', $hash)->exists()) {
                continue;
            }

            NewsItem::create([
                'news_topic_id' => $topic->id,
                'news_section_id' => $section->id,
                'title' => Str::limit(trim($article['title']), 490),
                'source_name' => $article['source_name'],
                'description' => $article['description'],
                'url' => $article['url'],
                'image_url' => $article['image_url'],
                'hash' => $hash,
                'published_at' => now(),
                'is_visible' => true,
                'is_featured' => false,
            ]);

            $saved++;
        }

        return $saved;
    }

    protected function fetchOpenGraphImage(string $url): ?string
    {
        try {
            $response = Http::timeout(5)
                ->connectTimeout(4)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 FIFA2026Bot/1.0'])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            foreach ([
                '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
                '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i',
                '/<img[^>]+src=["\']([^"\']+)["\']/i',
            ] as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected function extractImageUrl(\SimpleXMLElement $item, string $description, string $articleUrl, bool $allowOpenGraph): ?string
    {
        $candidates = [];

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', html_entity_decode($description), $matches)) {
            $candidates[] = $matches[1];
        }

        foreach (['media', 'content', 'itunes'] as $prefix) {
            foreach ($item->children($prefix, true) as $node) {
                $attributes = $node->attributes();
                foreach (['url', 'src', 'href'] as $attribute) {
                    if (isset($attributes[$attribute])) {
                        $candidates[] = (string) $attributes[$attribute];
                    }
                }
            }
        }

        foreach ($item->enclosure ?? [] as $enclosure) {
            $attributes = $enclosure->attributes();
            if (isset($attributes['url'])) {
                $candidates[] = (string) $attributes['url'];
            }
        }

        if ($allowOpenGraph) {
            $candidates[] = $this->fetchOpenGraphImage($articleUrl);
        }

        foreach (array_filter($candidates) as $candidate) {
            $normalized = $this->normalizeImageUrl($candidate, $articleUrl);

            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    protected function normalizeImageUrl(string $url, string $articleUrl): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));

        if ($url === '' || Str::startsWith($url, ['data:', 'javascript:'])) {
            return null;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:' . $url;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        $base = parse_url($articleUrl);

        if (!isset($base['scheme'], $base['host'])) {
            return null;
        }

        if (Str::startsWith($url, '/')) {
            return $base['scheme'] . '://' . $base['host'] . $url;
        }

        $path = Arr::get($base, 'path', '/');
        $directory = Str::beforeLast($path, '/');

        return $base['scheme'] . '://' . $base['host'] . rtrim($directory, '/') . '/' . ltrim($url, '/');
    }
}
