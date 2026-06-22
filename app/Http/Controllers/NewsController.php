<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use App\Services\FifaMatchService;
use App\Services\FifaPlaceholderImageService;
use App\Services\VisitorMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class NewsController extends Controller
{
    /**
     * Display the public World Cup News Explorer.
     */
    public function index(Request $request, VisitorMetricsService $visitorMetrics, FifaMatchService $fifaMatchService)
    {
        if (!$this->publicNewsSchemaReady()) {
            return $this->renderUnavailableHomepage($request);
        }

        $search = $request->input('search');
        $selectedTopicId = $request->input('topic');
        $selectedSectionFilter = $request->input('section');
        $selectedSection = null;
        
        $query = NewsItem::visible()
            ->with(['newsTopic', 'newsSection'])
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc');

        if ($selectedSectionFilter && $selectedSectionFilter !== 'all') {
            $selectedSection = NewsSection::query()
                ->where('slug', $selectedSectionFilter)
                ->orWhere('id', $selectedSectionFilter)
                ->first();

            if ($selectedSection) {
                $query->where('news_section_id', $selectedSection->id);
            }
        }

        // Apply topic filter
        if ($selectedTopicId && $selectedTopicId !== 'all') {
            if ($selectedTopicId === 'featured') {
                $query->featured();
            } elseif (is_numeric($selectedTopicId)) {
                $query->where('news_topic_id', $selectedTopicId);
            }
        }

        // Apply search query filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('source_name', 'like', "%{$search}%");
            });
        }

        $showSectionLanding = !$search && (!$selectedTopicId || $selectedTopicId === 'all') && !$selectedSection;

        // Paginate - 12 articles per page
        $articles = $query->paginate(12);
        $this->trackArticleViews($articles->pluck('id')->all());

        $sections = NewsSection::where('is_active', true)
            ->withCount(['newsItems' => function ($query) {
                $query->visible();
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $topics = NewsTopic::where('is_active', true)
            ->when($selectedSection, fn ($query) => $query->where('news_section_id', $selectedSection->id))
            ->withCount(['newsItems' => function ($q) {
                $q->visible();
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $featuredCount = NewsItem::visible()->featured()->count();

        // If AJAX request, return rendered cards HTML and pagination status
        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'html' => view('news.partials.cards', compact('articles'))->render(),
                'hasMorePages' => $articles->hasMorePages()
            ]);
        }

        $sharedPublicData = $this->publicPageContext($request, $visitorMetrics);
        $homepageSections = $sections->map(function (NewsSection $section) {
            $section->setRelation('latestArticles', NewsItem::query()
                ->visible()
                ->with(['newsTopic', 'newsSection'])
                ->where('news_section_id', $section->id)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->take($section->card_limit ?: 6)
                ->get());

            return $section;
        })->filter(fn (NewsSection $section) => $section->latestArticles->isNotEmpty())->values();

        return view('news.index', array_merge($sharedPublicData, compact('articles', 'sections', 'topics', 'selectedTopicId', 'selectedSection', 'search', 'featuredCount', 'homepageSections', 'showSectionLanding')));
    }

    protected function publicNewsSchemaReady(): bool
    {
        foreach (['news_items', 'news_sections', 'news_topics', 'settings', 'visitor_analytics'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    protected function renderUnavailableHomepage(Request $request)
    {
        $articles = new LengthAwarePaginator([], 0, 12, LengthAwarePaginator::resolveCurrentPage(), [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        $sections = collect();
        $topics = collect();
        $homepageSections = collect();
        $tickerArticles = collect();
        $selectedTopicId = $request->input('topic');
        $selectedSection = null;
        $search = $request->input('search');
        $featuredCount = 0;
        $showSectionLanding = false;
        return view('news.index', array_merge($this->publicFallbackContext(), compact('articles', 'sections', 'topics', 'selectedTopicId', 'selectedSection', 'search', 'featuredCount', 'homepageSections', 'showSectionLanding')));
    }

    public function fixtures(Request $request, VisitorMetricsService $visitorMetrics, FifaMatchService $fifaMatchService)
    {
        return view('news.fixtures', array_merge(
            $this->publicPageContext($request, $visitorMetrics),
            ['scoreboard' => $this->safeScoreboard($fifaMatchService)]
        ));
    }

    public function scores(Request $request, VisitorMetricsService $visitorMetrics, FifaMatchService $fifaMatchService)
    {
        return view('news.scores', array_merge(
            $this->publicPageContext($request, $visitorMetrics),
            ['scoreboard' => $this->safeScoreboard($fifaMatchService)]
        ));
    }

    protected function safeScoreboard(FifaMatchService $fifaMatchService): array
    {
        try {
            return $fifaMatchService->getScoreboard();
        } catch (Throwable) {
            return [
                'recent' => [],
                'upcoming' => [],
                'source_url' => 'https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/scores-fixtures',
                'synced_at' => null,
                'diagnostics' => [
                    'chrome_available' => false,
                    'chrome_binary' => null,
                ],
                'message' => 'Fixtures and live scores are temporarily unavailable on this server.',
            ];
        }
    }

    protected function publicPageContext(Request $request, VisitorMetricsService $visitorMetrics): array
    {
        $visitStats = $visitorMetrics->recordPublicVisit($request);

        return array_merge($this->publicFallbackContext(), [
            'visitStats' => $visitStats,
            'tickerArticles' => NewsItem::visible()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->take(8)
                ->get(['id', 'title']),
            'schemaReady' => true,
        ]);
    }

    protected function publicFallbackContext(): array
    {
        return [
            'visitStats' => [
                'total' => 0,
                'today' => 0,
                'unique_today' => 0,
                'unique_total' => 0,
                'live_now' => 0,
                'last_seen_at' => null,
            ],
            'tickerArticles' => collect(),
            'adsense' => [
                'client' => config('services.adsense.client'),
                'infeed_slot' => config('services.adsense.infeed_slot'),
                'tab_slot' => config('services.adsense.tab_slot'),
            ],
            'fetchStats' => [
                'total_runs' => (int) Setting::get('news_sync_total_runs', '0'),
                'last_success_at' => Setting::get('news_sync_last_success_at'),
                'interval_minutes' => 10,
                'next_scheduled_at' => $this->nextScheduledFetchAt(10)->toIso8601String(),
            ],
            'homepagePromo' => [
                'quotex_url' => config('services.promotions.quotex_url'),
                'signals_url' => config('services.promotions.signals_url'),
            ],
            'schemaReady' => false,
        ];
    }

    public function placeholderImage(string $seed, FifaPlaceholderImageService $placeholderImageService): Response
    {
        return response($placeholderImageService->svgForSeed($seed), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function articleImage(NewsItem $article, FifaPlaceholderImageService $placeholderImageService): Response
    {
        $seed = $article->hash ?: (string) $article->id;
        $fallback = fn () => response($placeholderImageService->svgForSeed($seed), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);

        if (!$this->isSafeRemoteImageUrl((string) $article->image_url)) {
            return $fallback();
        }

        try {
            $cacheKey = 'news-card-image:' . $article->id . ':' . md5((string) $article->image_url);
            $image = Cache::remember($cacheKey, now()->addHours(6), function () use ($article) {
                $response = Http::timeout(8)
                    ->connectTimeout(4)
                    ->withHeaders([
                        'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                        'User-Agent' => 'Mozilla/5.0 FIFA2026NewsBot/1.0',
                    ])
                    ->get($article->image_url);

                $contentType = strtolower((string) $response->header('content-type', ''));

                if (!$response->successful() || !Str::startsWith($contentType, 'image/') || $response->body() === '') {
                    return null;
                }

                return [
                    'body' => base64_encode($response->body()),
                    'content_type' => Str::before($contentType, ';') ?: 'image/jpeg',
                ];
            });

            if (!$image) {
                return $fallback();
            }

            return response(base64_decode($image['body'], true) ?: '', 200, [
                'Content-Type' => $image['content_type'],
                'Cache-Control' => 'public, max-age=21600, stale-while-revalidate=86400',
            ]);
        } catch (Throwable) {
            return $fallback();
        }
    }

    public function trackArticleClick(NewsItem $article): RedirectResponse
    {
        $article->increment('clicks_count');
        $article->forceFill(['last_clicked_at' => now()])->save();

        return redirect()->away($article->url);
    }

    public function updateVisitorContext(Request $request, VisitorMetricsService $visitorMetrics): JsonResponse
    {
        $visitorMetrics->updateClientContext($request);

        return response()->json(['ok' => true]);
    }

    public function refreshScoreboard(FifaMatchService $fifaMatchService): JsonResponse
    {
        $scoreboard = $fifaMatchService->getScoreboard(true);

        return response()->json([
            'fixtures_html' => view('news.partials.fixtures', compact('scoreboard'))->render(),
            'scores_html' => view('news.partials.scores', compact('scoreboard'))->render(),
            'synced_at' => optional($scoreboard['synced_at'])->toIso8601String(),
            'message' => $scoreboard['message'],
            'diagnostics' => $scoreboard['diagnostics'] ?? [],
        ]);
    }

    protected function trackArticleViews(array $articleIds): void
    {
        $articleIds = array_values(array_filter($articleIds));

        if ($articleIds === []) {
            return;
        }

        NewsItem::whereIn('id', $articleIds)->increment('views_count');
        NewsItem::whereIn('id', $articleIds)->update(['last_viewed_at' => now()]);
    }

    protected function nextScheduledFetchAt(int $intervalMinutes): \Illuminate\Support\Carbon
    {
        $now = now();
        $next = $now->copy()->second(0);
        $minutesToAdd = $intervalMinutes - ($now->minute % $intervalMinutes);

        if ($minutesToAdd === 0) {
            $minutesToAdd = $intervalMinutes;
        }

        return $next->addMinutes($minutesToAdd);
    }

    protected function isSafeRemoteImageUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }
}
