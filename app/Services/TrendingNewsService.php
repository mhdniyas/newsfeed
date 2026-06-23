<?php

namespace App\Services;

use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendingNewsService
{
    public const COUNTRY_CONFIG = [
        'IN' => ['name' => 'India', 'language' => 'en'],
        'US' => ['name' => 'United States', 'language' => 'en'],
        'ID' => ['name' => 'Indonesia', 'language' => 'id'],
        'BR' => ['name' => 'Brazil', 'language' => 'pt'],
        'JP' => ['name' => 'Japan', 'language' => 'ja'],
        'MX' => ['name' => 'Mexico', 'language' => 'es'],
        'GB' => ['name' => 'United Kingdom', 'language' => 'en'],
        'DE' => ['name' => 'Germany', 'language' => 'de'],
        'FR' => ['name' => 'France', 'language' => 'fr'],
    ];

    public const KEYWORDS_PER_COUNTRY = 10;
    public const KEYWORD_POOL_LIMIT = 250;
    public const NEWS_ARTICLE_LIMIT = 120;
    public const ARTICLES_PER_COUNTRY = 10;

    public function __construct(
        protected NewsFetchService $newsFetchService
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public function fetchTrends(): array
    {
        $responses = Http::pool(function ($pool) {
            $requests = [];

            foreach (array_keys(self::COUNTRY_CONFIG) as $countryCode) {
                $requests[] = $pool
                    ->as($countryCode)
                    ->retry(3, 500)
                    ->timeout(15)
                    ->withHeaders(['Accept' => 'application/xml, text/xml'])
                    ->get("https://trends.google.com/trending/rss?geo={$countryCode}");
            }

            return $requests;
        });

        $trends = [];

        foreach (array_keys(self::COUNTRY_CONFIG) as $countryCode) {
            $trends[$countryCode] = $this->parseTrendResponse(
                $countryCode,
                $responses[$countryCode] ?? null
            );
        }

        return $trends;
    }

    /**
     * @param array<string, array<string>> $trendsByCountry
     * @return array<string, array<string, int>>
     */
    public function updateKeywordSpots(array $trendsByCountry): array
    {
        $section = $this->trendsSection();

        if (!$section) {
            Log::error('Google Trends section not found in database.');

            return [];
        }

        $stats = [];

        foreach (self::COUNTRY_CONFIG as $countryCode => $config) {
            $keywords = collect($trendsByCountry[$countryCode] ?? [])
                ->map(fn ($keyword) => trim((string) $keyword))
                ->filter()
                ->unique(fn ($keyword) => mb_strtolower($keyword))
                ->take(self::KEYWORDS_PER_COUNTRY)
                ->values();

            $activeKeywordSet = $keywords
                ->mapWithKeys(fn ($keyword) => [mb_strtolower($keyword) => true]);

            foreach ($keywords as $index => $keyword) {
                NewsTopic::updateOrCreate(
                    [
                        'news_section_id' => $section->id,
                        'keyword' => $keyword,
                        'country' => $countryCode,
                    ],
                    [
                        'name' => $keyword,
                        'language' => $config['language'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]
                );
            }

            NewsTopic::query()
                ->where('news_section_id', $section->id)
                ->where('country', $countryCode)
                ->get()
                ->each(function (NewsTopic $topic) use ($activeKeywordSet): void {
                    $shouldBeActive = $activeKeywordSet->has(mb_strtolower($topic->keyword));

                    if ($topic->is_active !== $shouldBeActive) {
                        $topic->forceFill(['is_active' => $shouldBeActive])->save();
                    }
                });

            $countryTopics = NewsTopic::query()
                ->where('news_section_id', $section->id)
                ->where('country', $countryCode)
                ->orderByDesc('updated_at')
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get();

            $activeCount = (int) $countryTopics->where('is_active', true)->count();

            $stats[$countryCode] = [
                'fetched' => $keywords->count(),
                'active' => $activeCount,
                'stored' => $countryTopics->count(),
            ];
        }

        $this->trimKeywordPool($section);
        $this->rememberSnapshot($stats);

        return $stats;
    }

    public function syncTrendingNews(int $maxArticles = self::NEWS_ARTICLE_LIMIT): int
    {
        $section = $this->trendsSection();

        if (!$section) {
            return 0;
        }

        $saved = 0;
        $remaining = max(1, min(self::NEWS_ARTICLE_LIMIT, $maxArticles));

        foreach (array_keys(self::COUNTRY_CONFIG) as $countryCode) {
            if ($remaining <= 0) {
                break;
            }

            $summary = $this->syncCountryTrendingNews($countryCode, min(self::ARTICLES_PER_COUNTRY, $remaining));
            $saved += (int) ($summary['new_articles'] ?? 0);
            $remaining -= (int) ($summary['new_articles'] ?? 0);
        }

        return $saved;
    }

    public function syncCountryTrendingNews(string $countryCode, int $maxArticles = self::ARTICLES_PER_COUNTRY, ?TrendSyncStateService $syncState = null): array
    {
        $section = $this->trendsSection();

        if (!$section || !isset(self::COUNTRY_CONFIG[$countryCode])) {
            return [
                'new_articles' => 0,
                'google_articles' => 0,
                'official_articles' => 0,
                'skipped_duplicates' => 0,
                'images_recovered' => 0,
            ];
        }

        $topics = $this->countryTopics($countryCode);

        if ($topics->isEmpty()) {
            return [
                'new_articles' => 0,
                'google_articles' => 0,
                'official_articles' => 0,
                'skipped_duplicates' => 0,
                'images_recovered' => 0,
            ];
        }

        $countrySection = $this->countrySectionCard($countryCode, $section);
        $countryLimit = max(1, min(self::ARTICLES_PER_COUNTRY, $maxArticles));

        return $this->newsFetchService->fetchTopicBatch(
            $countrySection,
            $topics,
            $countryLimit,
            $countryLimit,
            $syncState
        );
    }

    public function countrySectionCard(string $countryCode, ?NewsSection $section = null): NewsSection
    {
        $section ??= $this->trendsSection();
        $config = self::COUNTRY_CONFIG[$countryCode] ?? ['name' => $countryCode];
        $countrySection = clone $section;
        $countrySection->name = "{$config['name']} Trends";
        $countrySection->card_limit = self::ARTICLES_PER_COUNTRY;

        return $countrySection;
    }

    /**
     * @return array<string, mixed>
     */
    public function adminSnapshot(): array
    {
        $section = $this->trendsSection();
        $stats = json_decode(Setting::get('trends_country_stats', '{}') ?: '{}', true) ?: [];
        $topics = $section
            ? NewsTopic::query()
                ->where('news_section_id', $section->id)
                ->orderBy('country')
                ->orderBy('sort_order')
                ->orderByDesc('updated_at')
                ->get()
                ->groupBy('country')
            : collect();

        $countries = collect(self::COUNTRY_CONFIG)->map(function (array $config, string $countryCode) use ($stats, $topics) {
            /** @var \Illuminate\Support\Collection<int, \App\Models\NewsTopic> $countryTopics */
            $countryTopics = $topics->get($countryCode, collect());
            $activeTopics = $countryTopics->where('is_active', true)->take(self::KEYWORDS_PER_COUNTRY)->values();

            return [
                'code' => $countryCode,
                'name' => $config['name'],
                'language' => $config['language'],
                'fetched' => (int) data_get($stats, "{$countryCode}.fetched", $activeTopics->count()),
                'active' => (int) $countryTopics->where('is_active', true)->count(),
                'stored' => (int) $countryTopics->count(),
                'keywords' => $activeTopics,
            ];
        })->values();

        return [
            'section' => $section,
            'countries' => $countries,
            'keywords_per_country' => self::KEYWORDS_PER_COUNTRY,
            'keyword_pool_limit' => self::KEYWORD_POOL_LIMIT,
            'country_count' => count(self::COUNTRY_CONFIG),
            'total_active_keywords' => (int) $countries->sum('active'),
            'total_active_keyword_limit' => count(self::COUNTRY_CONFIG) * self::KEYWORDS_PER_COUNTRY,
            'last_synced_at' => Setting::get('trends_last_synced_at'),
            'news_article_limit' => self::NEWS_ARTICLE_LIMIT,
            'articles_per_country' => self::ARTICLES_PER_COUNTRY,
        ];
    }

    /**
     * @return Collection<int, NewsTopic>
     */
    public function countryTopics(string $countryCode): Collection
    {
        $section = $this->trendsSection();

        if (!$section) {
            return collect();
        }

        return NewsTopic::query()
            ->where('news_section_id', $section->id)
            ->where('country', $countryCode)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array<string>
     */
    protected function parseTrendResponse(string $countryCode, mixed $response): array
    {
        if (!$response instanceof Response) {
            Log::error("Failed to fetch Google Trends RSS for {$countryCode}. No HTTP response object was returned.");
            return [];
        }

        if (!$response->successful()) {
            Log::error("Failed to fetch Google Trends RSS for {$countryCode}. Status: {$response->status()}");

            return [];
        }

        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

            if (!$xml || !isset($xml->channel->item)) {
                return [];
            }

            return collect(iterator_to_array($xml->channel->item, false))
                ->map(fn ($item) => trim((string) $item->title))
                ->filter()
                ->unique(fn ($keyword) => mb_strtolower($keyword))
                ->take(self::KEYWORDS_PER_COUNTRY)
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::error("Failed to parse Google Trends RSS for {$countryCode}: " . $exception->getMessage());

            return [];
        }
    }

    protected function trendsSection(): ?NewsSection
    {
        return NewsSection::query()->where('slug', 'google-trends')->first();
    }

    /**
     * @param array<string, array<string, int>> $stats
     */
    protected function rememberSnapshot(array $stats): void
    {
        Setting::set('trends_last_synced_at', now()->toIso8601String());
        Setting::set('trends_country_stats', json_encode($stats));
    }

    protected function trimKeywordPool(NewsSection $section): void
    {
        // Automatic deletion of inactive Google Trends keyword topics has been removed.
        // Inactive keywords will remain in the database unless manually deleted.
    }
}
