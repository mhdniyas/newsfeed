<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use App\Services\AutomaticNewsSyncService;
use App\Services\ArticleContentExtractionService;
use App\Services\FifaMatchService;
use App\Services\FifaPlaceholderImageService;
use App\Services\PromotionHubService;
use App\Services\TrendLandingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class NewsController extends Controller
{
    /**
     * Display the public World Cup News Explorer.
     */
    public function index(Request $request, FifaMatchService $fifaMatchService)
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
        $this->trackArticleViews($request, $articles->pluck('id')->all());

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

        $sharedPublicData = $this->publicPageContext($request);
        $homepageSections = $sections->map(function (NewsSection $section) {
            $section->setRelation('latestArticles', NewsItem::query()
                ->visible()
                ->with(['newsTopic', 'newsSection'])
                ->where('news_section_id', $section->id)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->take(5)
                ->get());

            return $section;
        })->filter(fn (NewsSection $section) => $section->latestArticles->isNotEmpty())->values();
        $trendPages = app(TrendLandingService::class)->homepagePages(3);

        return view('news.index', array_merge($sharedPublicData, compact('articles', 'sections', 'topics', 'selectedTopicId', 'selectedSection', 'search', 'featuredCount', 'homepageSections', 'showSectionLanding', 'trendPages')));
    }

    protected function publicNewsSchemaReady(): bool
    {
        foreach (['news_items', 'news_sections', 'news_topics', 'settings'] as $table) {
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
        $trendPages = collect();
        $tickerArticles = collect();
        $selectedTopicId = $request->input('topic');
        $selectedSection = null;
        $search = $request->input('search');
        $featuredCount = 0;
        $showSectionLanding = false;
        return view('news.index', array_merge($this->publicFallbackContext(), compact('articles', 'sections', 'topics', 'selectedTopicId', 'selectedSection', 'search', 'featuredCount', 'homepageSections', 'showSectionLanding', 'trendPages')));
    }

    public function showArticle(Request $request, NewsItem $article, ArticleContentExtractionService $extractionService)
    {
        if (!$this->publicNewsSchemaReady() || !$article->is_visible) {
            abort(404);
        }

        if ($article->extraction_status !== 'extracted' || $article->excerptParagraphs() === []) {
            $extractionService->extractForArticle($article->fresh());
            $article = $article->fresh(['newsTopic', 'newsSection']);
        } else {
            $article->loadMissing(['newsTopic', 'newsSection']);
        }

        $pageContext = $this->publicPageContext($request);
        $relatedArticles = NewsItem::visible()
            ->with(['newsTopic', 'newsSection'])
            ->where('id', '!=', $article->id)
            ->where(function ($query) use ($article): void {
                $query->where('news_topic_id', $article->news_topic_id)
                    ->orWhere('news_section_id', $article->news_section_id);
            })
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return view('news.article', array_merge($pageContext, compact('article', 'relatedArticles')));
    }

    public function trendPage(string $slug, Request $request, TrendLandingService $trendLandingService)
    {
        $page = $trendLandingService->resolve($slug);

        if (!$page) {
            abort(404);
        }

        $articles = $trendLandingService->articlesFor($page);
        $this->trackArticleViews($request, $articles->pluck('id')->all());
        $pageContext = $this->publicPageContext($request);

        return view('news.trend-page', array_merge($pageContext, compact('page', 'articles')));
    }

    public function fixtures(Request $request, FifaMatchService $fifaMatchService)
    {
        return view('news.fixtures', array_merge(
            $this->publicPageContext($request),
            ['scoreboard' => $this->safeScoreboard($fifaMatchService)]
        ));
    }

    public function topStories(Request $request)
    {
        return $this->renderCuratedFeed(
            $request,
            $visitorMetrics,
            [
                'eyebrow' => 'Featured Feed',
                'title' => 'Top Stories',
                'description' => 'Featured headlines curated from the strongest stories across the news stream.',
                'empty_title' => 'No top stories yet',
                'empty_description' => 'Feature articles in admin and they will appear here.',
                'stat_label' => 'Featured Stories',
                'accent_classes' => 'border-amber-200 bg-[radial-gradient(circle_at_top_left,_rgba(251,191,36,0.18),_transparent_30%),linear-gradient(135deg,_#ffffff_0%,_#fffbeb_58%,_#fff7ed_100%)]',
                'stat_classes' => 'border-amber-200 bg-white/90',
                'stat_text_classes' => 'text-amber-700/70',
                'hero_text_classes' => 'text-slate-950',
                'hero_copy_classes' => 'text-slate-600',
            ],
            fn (Builder $query) => $query->featured()
        );
    }

    public function trending(Request $request)
    {
        return $this->renderCuratedFeed(
            $request,
            $visitorMetrics,
            [
                'eyebrow' => 'Latest Feed',
                'title' => 'Trending',
                'description' => 'The newest visible stories across every active section, sorted by fresh publication time.',
                'empty_title' => 'No trending stories yet',
                'empty_description' => 'Run a sync and the latest stories will appear here.',
                'stat_label' => 'Recent Stories',
                'accent_classes' => 'border-sky-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_32%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_58%,_#ecfeff_100%)]',
                'stat_classes' => 'border-sky-200 bg-white/90',
                'stat_text_classes' => 'text-sky-700/70',
                'hero_text_classes' => 'text-slate-950',
                'hero_copy_classes' => 'text-slate-600',
            ],
            fn (Builder $query) => $query
        );
    }

    public function fifa(Request $request)
    {
        $sportsSection = NewsSection::query()
            ->where('slug', 'sports')
            ->orWhere('name', 'Sports')
            ->first();

        return $this->renderCuratedFeed(
            $request,
            $visitorMetrics,
            [
                'eyebrow' => 'Sports Feed',
                'title' => 'FIFA',
                'description' => $sportsSection?->description ?: 'Sports coverage gathered into one public feed for fast mobile browsing.',
                'empty_title' => 'No FIFA stories yet',
                'empty_description' => 'Sync the Sports section and stories will appear here.',
                'stat_label' => 'Sports Stories',
                'accent_classes' => 'border-emerald-200 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_30%),linear-gradient(135deg,_#ffffff_0%,_#f0fdf4_58%,_#ecfeff_100%)]',
                'stat_classes' => 'border-emerald-200 bg-white/90',
                'stat_text_classes' => 'text-emerald-700/70',
                'hero_text_classes' => 'text-slate-950',
                'hero_copy_classes' => 'text-slate-600',
            ],
            function (Builder $query) use ($sportsSection) {
                if ($sportsSection) {
                    $query->where('news_section_id', $sportsSection->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        );
    }

    public function scores(Request $request, FifaMatchService $fifaMatchService)
    {
        return view('news.scores', array_merge(
            $this->publicPageContext($request),
            ['scoreboard' => $this->safeScoreboard($fifaMatchService)]
        ));
    }

    public function gallery(Request $request)
    {
        $articles = NewsItem::visible()
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->with(['newsTopic', 'newsSection'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(24);
        $this->trackArticleViews($request, $articles->pluck('id')->all());

        $galleryStats = [
            'recovered_images' => NewsItem::visible()
                ->whereNotNull('image_url')
                ->where('image_url', '!=', '')
                ->count(),
        ];

        return view('news.gallery', array_merge(
            $this->publicPageContext($request),
            compact('articles', 'galleryStats')
        ));
    }

    public function aiNews(Request $request)
    {
        $aiSection = NewsSection::query()
            ->where('slug', 'ai')
            ->orWhere('name', 'AI')
            ->first();

        $articles = NewsItem::visible()
            ->with(['newsTopic', 'newsSection'])
            ->when($aiSection, fn ($query) => $query->where('news_section_id', $aiSection->id))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(18);
        $this->trackArticleViews($request, $articles->pluck('id')->all());

        $aiTopics = $aiSection
            ? NewsTopic::query()
                ->where('news_section_id', $aiSection->id)
                ->where('is_active', true)
                ->withCount(['newsItems' => fn ($query) => $query->visible()])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
            : collect();

        return view('news.ai', array_merge(
            $this->publicPageContext($request),
            compact('articles', 'aiSection', 'aiTopics')
        ));
    }

    protected function renderCuratedFeed(
        Request $request,
        array $feedMeta,
        callable $scope
    ) {
        $query = NewsItem::visible()
            ->with(['newsTopic', 'newsSection'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $scope($query);

        $articles = $query->paginate(18);
        $this->trackArticleViews($request, $articles->pluck('id')->all());
        $feedMeta['stat_value'] = max($articles->total(), $articles->count());

        return view('news.feed', array_merge(
            $this->publicPageContext($request),
            [
                'articles' => $articles,
                'feedMeta' => $feedMeta,
            ]
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

    protected function publicPageContext(Request $request): array
    {
        app(AutomaticNewsSyncService::class)->maybeTriggerDueSync('Automatic fallback sync triggered from public page request.');

        return array_merge($this->publicFallbackContext(), [
            'visitStats' => null,
            'tickerArticles' => NewsItem::visible()
                ->where(function ($query) {
                    $query->whereNull('news_section_id')
                        ->orWhereHas('newsSection', function ($q) {
                            $q->where('slug', '!=', 'google-trends');
                        });
                })
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->take(8)
                ->get(['id', 'title', 'slug']),
            'schemaReady' => true,
        ]);
    }

    protected function publicFallbackContext(): array
    {
        $homepagePromo = app(PromotionHubService::class)->publicPayload();

        return [
            'visitStats' => [
                'total' => 0,
                'today' => 0,
                'page_views_total' => 0,
                'page_views_today' => 0,
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
                'interval_minutes' => AutomaticNewsSyncService::SYNC_INTERVAL_MINUTES,
                'next_scheduled_at' => $this->nextScheduledFetchAt(AutomaticNewsSyncService::SYNC_INTERVAL_MINUTES)->toIso8601String(),
            ],
            'homepagePromo' => $homepagePromo,
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

        $remoteImage = (string) ($article->extracted_image_url ?: $article->image_url);

        if (!$this->isSafeRemoteImageUrl($remoteImage)) {
            return $fallback();
        }

        try {
            $cacheKey = 'news-card-image:' . $article->id . ':' . md5($remoteImage);
            $image = Cache::remember($cacheKey, now()->addHours(6), function () use ($remoteImage) {
                $response = Http::timeout(8)
                    ->connectTimeout(4)
                    ->withHeaders([
                        'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                        'User-Agent' => 'Mozilla/5.0 FIFA2026NewsBot/1.0',
                    ])
                    ->get($remoteImage);

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

    protected function trackArticleViews(Request $request, array $articleIds): void
    {
        
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

    /**
     * Display a static policy or info page.
     */
    public function staticPage(string $page, Request $request)
    {
        $allowedPages = [
            'about-us' => 'about',
            'contact-us' => 'contact',
            'privacy-policy' => 'privacy',
            'terms' => 'terms',
            'disclaimer' => 'disclaimer',
            'affiliate-disclosure' => 'affiliate',
        ];

        if (!array_key_exists($page, $allowedPages)) {
            abort(404);
        }

        $viewName = 'news.' . $allowedPages[$page];
        $pageContext = $this->publicPageContext($request);

        return view($viewName, $pageContext);
    }

    /**
     * AJAX: return the next batch of articles for a section on the landing page.
     */
    public function sectionMoreArticles(\App\Models\NewsSection $section, Request $request): JsonResponse
    {
        $offset  = max(0, (int) $request->input('offset', 5));
        $perPage = 6;

        $articles = NewsItem::visible()
            ->with(['newsTopic', 'newsSection'])
            ->where('news_section_id', $section->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $totalVisible = NewsItem::visible()
            ->where('news_section_id', $section->id)
            ->count();

        $html = $articles->map(fn ($article) => view('news.partials.section-card', compact('article'))->render())->implode('');

        return response()->json([
            'html'       => $html,
            'hasMore'    => ($offset + $perPage) < $totalVisible,
            'nextOffset' => $offset + $perPage,
            'total'      => $totalVisible,
        ]);
    }
}
