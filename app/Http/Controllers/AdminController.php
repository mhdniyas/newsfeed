<?php

namespace App\Http\Controllers;

use App\Jobs\RunNewsSyncCycle;
use App\Models\NewsItem;
use App\Models\NewsItemDailyMetric;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\LotteryResult;
use App\Models\Setting;
use App\Models\User;
use App\Services\AutomaticNewsSyncService;
use App\Services\AutomaticTrendSyncService;
use App\Services\KeralaLotteryService;
use App\Services\NewsRetentionService;
use App\Services\PromotionHubService;
use App\Services\TrendingNewsService;
use App\Services\VisitorMetricsService;
use Illuminate\Bus\UniqueLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    /**
     * Get the admin passcode from config or environment.
     */
    protected function getAdminPasscode(): string
    {
        return Setting::get('admin_passcode', env('ADMIN_PASSWORD', 'admin123'));
    }

    /**
     * Show the admin passcode login page.
     */
    public function showLoginForm()
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    /**
     * Handle the admin passcode login submit.
     */
    public function login(Request $request)
    {
        $request->validate([
            'passcode' => 'required|string',
        ]);

        if ($request->input('passcode') === $this->getAdminPasscode()) {
            session(['admin_authenticated' => true]);
            return redirect()->route('admin.dashboard')->with('success', 'Logged in successfully!');
        }

        return back()->withErrors(['passcode' => 'Invalid admin passcode. Please try again.']);
    }

    /**
     * Handle the admin logout.
     */
    public function logout()
    {
        session()->forget('admin_authenticated');
        return redirect()->route('admin.login')->with('success', 'Logged out successfully.');
    }

    /**
     * Display the admin dashboard.
     */
    public function index(Request $request, VisitorMetricsService $visitorMetrics, AutomaticNewsSyncService $automaticNewsSync)
    {
        $automaticNewsSync->maybeTriggerDueSync('Automatic fallback sync triggered from admin dashboard request.');

        $sections = NewsSection::withCount([
                'newsTopics',
                'newsItems',
            ])
            ->where('slug', '!=', 'google-trends')
            ->with(['newsTopics' => fn ($query) => $query->withCount('newsItems')->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $search = $request->input('search');
        $selectedTopicId = $request->input('topic');
        $selectedSectionId = $request->input('section', 'all');
        $sort = $request->input('sort', 'latest');

        $articles = $this->buildArticleQuery($selectedSectionId, $selectedTopicId, $search, $sort)->paginate(15);
        $adminName = Setting::get('admin_name', 'Admin');
        $syncState = $this->syncState();
        $fetchStats = $this->fetchStats();

        return view('admin.dashboard', compact('sections', 'articles', 'selectedTopicId', 'selectedSectionId', 'search', 'adminName', 'sort', 'syncState', 'fetchStats'));
    }

    /**
     * Display the dedicated analytics page.
     */
    public function analytics(VisitorMetricsService $visitorMetrics)
    {
        $visitStats = $visitorMetrics->getPublicStats();
        $visitorSnapshot = $visitorMetrics->adminAnalyticsSnapshot();
        $analyticsSummary = $this->analyticsSummary($visitorMetrics);
        $contentAnalytics = $this->contentAnalyticsSummary(app(NewsRetentionService::class));
        $articlePageAnalytics = $this->articlePageAnalyticsSummary();
        $trendsAnalyticsSummary = $visitorMetrics->trendsAnalyticsSummary();
        $lotteryAnalyticsSummary = $this->keralaLotteryAnalyticsSummary();
        $analyticsCharts = [
            'live_users' => $this->liveUserChart(),
            'news_total' => $this->newsTotalChart(),
        ];
        $contentCharts = [
            'hourly_publish' => $this->hourlyPublishChart(),
            'daily_publish' => $this->newsTotalChart(),
        ];
        $fetchStats = $this->fetchStats();

        return view('admin.analytics', compact('visitStats', 'analyticsSummary', 'contentAnalytics', 'articlePageAnalytics', 'trendsAnalyticsSummary', 'lotteryAnalyticsSummary', 'visitorSnapshot', 'analyticsCharts', 'contentCharts', 'fetchStats'));
    }

    public function rankingAnalytics(VisitorMetricsService $visitorMetrics)
    {
        $visitStats = $visitorMetrics->getPublicStats();
        $xpDashboard = $visitorMetrics->adminXpDashboard();
        $fetchStats = $this->fetchStats();

        return view('admin.analytics-ranking', compact('visitStats', 'xpDashboard', 'fetchStats'));
    }

    public function destroyPage(Request $request, NewsRetentionService $newsRetention)
    {
        $sections = NewsSection::with(['newsTopics' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $search = $request->input('search');
        $selectedTopicId = $request->input('topic');
        $selectedSectionId = $request->input('section', 'all');
        $savedSettings = $newsRetention->settings();
        $sort = (string) $request->input('sort', $savedSettings['sort']);
        $destroyMode = (string) $request->input('mode', $savedSettings['mode']);
        $destroyDays = max(1, (int) $request->input('days', $savedSettings['days']));
        $destroyClickThreshold = max(0, (int) $request->input('click_threshold', $savedSettings['click_threshold']));
        $destroyBatchLimit = max(NewsRetentionService::MIN_BATCH_LIMIT, min(NewsRetentionService::MAX_BATCH_LIMIT, (int) $request->input('batch_limit', $savedSettings['batch_limit'])));
        $destroySettings = $newsRetention->settings([
            'days' => $destroyDays,
            'click_threshold' => $destroyClickThreshold,
            'mode' => $destroyMode,
            'sort' => $sort,
            'batch_limit' => $destroyBatchLimit,
            'enabled' => $savedSettings['enabled'],
        ]);

        $articles = $this->buildDestroyQuery($selectedSectionId, $selectedTopicId, $search, $destroySettings, $newsRetention)->paginate(25)->withQueryString();
        $statsQuery = $this->buildDestroyQuery($selectedSectionId, $selectedTopicId, $search, $destroySettings, $newsRetention);
        $destroyStats = [
            'eligible_articles' => (clone $statsQuery)->count(),
            'zero_click_articles' => (clone $statsQuery)->where('clicks_count', '<=', 0)->count(),
            'zero_view_articles' => (clone $statsQuery)->where('views_count', '<=', 0)->count(),
            'favorite_articles' => NewsItem::query()
                ->whereNotNull('published_at')
                ->where('published_at', '<', $destroySettings['cutoff_at'])
                ->where('is_favorite', true)
                ->count(),
        ];
        $autoSettings = $newsRetention->settings();
        $autoDeleteReport = [
            ...$autoSettings,
            'cutoff_at' => $autoSettings['cutoff_at'],
            'eligible_now' => $newsRetention->eligibleQuery($autoSettings)->count(),
            'protected_now' => $newsRetention->protectedQuery($autoSettings)->count(),
            'favorite_protected_now' => NewsItem::query()
                ->where('published_at', '<', $autoSettings['cutoff_at'])
                ->where('is_favorite', true)
                ->count(),
            'last_run_at' => Setting::get('news_prune_last_run_at'),
            'last_cutoff_at' => Setting::get('news_prune_last_cutoff_at'),
            'last_eligible_count' => (int) Setting::get('news_prune_last_eligible_count', '0'),
            'last_deleted_count' => (int) Setting::get('news_prune_last_deleted_count', '0'),
            'last_protected_count' => (int) Setting::get('news_prune_last_protected_count', '0'),
            'last_favorite_protected_count' => (int) Setting::get('news_prune_last_favorite_protected_count', '0'),
        ];
        $fetchStats = $this->fetchStats();

        return view('admin.destroy', compact(
            'sections',
            'articles',
            'selectedTopicId',
            'selectedSectionId',
            'search',
            'sort',
            'destroyMode',
            'destroyDays',
            'destroyClickThreshold',
            'destroyBatchLimit',
            'destroyStats',
            'destroySettings',
            'autoDeleteReport',
            'fetchStats'
        ));
    }

    public function promotions(AutomaticNewsSyncService $automaticNewsSync, PromotionHubService $promotionHub)
    {
        $automaticNewsSync->maybeTriggerDueSync('Automatic fallback sync triggered from admin promotions request.');

        $fetchStats = $automaticNewsSync->fetchStats();
        $promotions = [
            'cards' => $promotionHub->cards(),
            'labels' => $promotionHub->labels(),
            'whatsapp_message' => Setting::get('promo_whatsapp_message', config('services.promotions.whatsapp_message')),
        ];
        $previewPromo = $promotionHub->publicPayload();

        return view('admin.promotions', compact('promotions', 'previewPromo', 'fetchStats'));
    }

    public function runDestroyProcess(Request $request, NewsRetentionService $newsRetention)
    {
        $result = $newsRetention->prune([
            'days' => (int) $request->input('days', $newsRetention->retentionDays()),
            'click_threshold' => (int) $request->input('click_threshold', $newsRetention->clickThreshold()),
            'mode' => $request->input('mode', $newsRetention->mode()),
            'sort' => $request->input('sort', $newsRetention->sort()),
            'batch_limit' => (int) $request->input('batch_limit', $newsRetention->batchLimit()),
            'enabled' => $newsRetention->autoDeletionEnabled(),
        ]);

        return redirect()
            ->route('admin.destroy', $request->only(['section', 'topic', 'search', 'sort', 'mode', 'days', 'click_threshold', 'batch_limit']))
            ->with('success', "Destroy process completed. Deleted {$result['deleted_count']} article(s) from {$result['eligible_count']} eligible using {$result['mode']} mode. Batch limit {$result['batch_limit']}, protected {$result['protected_count']}, favorites protected {$result['favorite_protected_count']}.");
    }

    public function saveDestroySettings(Request $request, NewsRetentionService $newsRetention)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'days' => 'required|integer|min:1|max:30',
            'click_threshold' => 'required|integer|min:0|max:100000',
            'batch_limit' => 'required|integer|min:500|max:3000',
            'mode' => 'required|in:standard,no_clicks,no_views,viewed_no_clicks',
            'sort' => 'required|in:oldest,latest,least_clicked,least_viewed,most_clicked,most_viewed,title',
        ]);

        $settings = $newsRetention->saveSettings([
            'enabled' => $request->boolean('enabled'),
            'days' => $validated['days'],
            'click_threshold' => $validated['click_threshold'],
            'batch_limit' => $validated['batch_limit'],
            'mode' => $validated['mode'],
            'sort' => $validated['sort'],
        ]);

        return redirect()
            ->route('admin.destroy')
            ->with('success', "Automatic destroy settings saved. Mode {$settings['mode']}, {$settings['days']} day window, {$settings['click_threshold']} click threshold, {$settings['batch_limit']} delete limit.");
    }

    public function trends(Request $request, AutomaticNewsSyncService $automaticNewsSync, AutomaticTrendSyncService $automaticTrendSync, TrendingNewsService $trendingNewsService)
    {
        $automaticNewsSync->maybeTriggerDueSync('Automatic fallback sync triggered from admin trends request.');
        $automaticTrendSync->maybeTriggerDueSync('Automatic fallback trend sync triggered from admin trends request.');

        $fetchStats = $automaticNewsSync->fetchStats();
        $trendSyncState = $automaticTrendSync->syncState();
        $trendFetchStats = $automaticTrendSync->fetchStats();
        $trendsSnapshot = $trendingNewsService->adminSnapshot();
        $selectedCountry = strtoupper((string) $request->input('country', 'ALL'));
        $trendsSectionId = $trendsSnapshot['section']?->id;
        $trendArticles = NewsItem::with(['newsTopic', 'newsSection'])
            ->when($trendsSectionId, fn ($query) => $query->where('news_section_id', $trendsSectionId))
            ->when($selectedCountry !== 'ALL', function ($query) use ($selectedCountry) {
                $query->whereHas('newsTopic', fn ($topicQuery) => $topicQuery->where('country', $selectedCountry));
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.trends', compact('fetchStats', 'trendSyncState', 'trendFetchStats', 'trendsSnapshot', 'trendArticles', 'selectedCountry'));
    }

    public function updatePromotions(Request $request, PromotionHubService $promotionHub)
    {
        $request->validate([
            'cards' => 'required|array',
            'cards.*.enabled' => 'nullable',
            'cards.*.badge' => 'nullable|string|max:80',
            'cards.*.title' => 'nullable|string|max:160',
            'cards.*.body' => 'nullable|string|max:600',
            'cards.*.primary_label' => 'nullable|string|max:80',
            'cards.*.primary_url' => 'nullable|string|max:1000',
            'cards.*.secondary_label' => 'nullable|string|max:80',
            'cards.*.secondary_url' => 'nullable|string|max:1000',
            'cards.*.note' => 'nullable|string|max:160',
            'whatsapp_message' => 'nullable|string|max:1000',
        ]);

        $cards = $promotionHub->sanitizeInput($request->input('cards', []));

        foreach ($cards as $key => $card) {
            validator([
                'primary_url' => $card['primary_url'] ?: null,
                'secondary_url' => $card['secondary_url'] ?: null,
            ], [
                'primary_url' => 'nullable|url|max:1000',
                'secondary_url' => 'nullable|url|max:1000',
            ], [], [
                'primary_url' => $promotionHub->labels()[$key] . ' primary URL',
                'secondary_url' => $promotionHub->labels()[$key] . ' secondary URL',
            ])->validate();
        }

        $promotionHub->save($cards, $request->input('whatsapp_message'));

        return back()->with('success', 'Promotion hub updated successfully.');
    }

    public function refreshTrends(Request $request, TrendingNewsService $trendingNewsService, AutomaticTrendSyncService $automaticTrendSync)
    {
        if ($request->boolean('sync_news')) {
            $syncState = $automaticTrendSync->syncState();

            if (in_array($syncState['status'], ['queued', 'running'], true) && !$syncState['is_stale']) {
                return back()->with('success', 'A trend sync is already in progress. The live monitor will continue updating below.');
            }

            if ($syncState['is_stale']) {
                $automaticTrendSync->stopTrackedSyncProcess('Previous trend sync state was stale and has been replaced with a fresh run.');
            }

            [$started, $message] = $automaticTrendSync->launchQueuedSync('Manual trend sync requested from admin trends page.');

            return back()->with('success', $started
                ? 'Trend sync started in background. The live monitor below will update progress automatically.'
                : $message);
        }

        $trends = $trendingNewsService->fetchTrends();
        $stats = $trendingNewsService->updateKeywordSpots($trends);
        $countrySummary = collect($stats)
            ->map(fn ($countryStats, $countryCode) => "{$countryCode}: {$countryStats['active']} active")
            ->implode(', ');

        return redirect()
            ->route('admin.trends')
            ->with('success', "Trend keywords refreshed. {$countrySummary}");
    }

    public function cleanupTrendKeywords(TrendingNewsService $trendingNewsService)
    {
        $deletedCount = $trendingNewsService->cleanupExpiredKeywords();

        return redirect()
            ->route('admin.trends')
            ->with('success', "Trend keyword cleanup complete. Deleted {$deletedCount} keyword(s) older than 24 hours.");
    }

    public function stopAndResyncTrends(AutomaticTrendSyncService $automaticTrendSync)
    {
        $automaticTrendSync->stopTrackedSyncProcess('Active trend sync was stopped manually before restarting.');

        [$started, $message] = $automaticTrendSync->launchQueuedSync('Manual stop and resync requested from admin trends page.');

        return back()->with('success', $started
            ? 'Existing trend sync was stopped and a fresh background trend sync has been started.'
            : $message);
    }

    public function trendsSyncStatus(AutomaticTrendSyncService $automaticTrendSync): JsonResponse
    {
        $automaticTrendSync->maybeTriggerDueSync('Automatic fallback trend sync triggered from admin trend sync monitor.');

        return response()->json($automaticTrendSync->syncState());
    }

    public function storeSection(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        NewsSection::create([
            'name' => $request->input('name'),
            'slug' => \Illuminate\Support\Str::slug($request->input('name')),
            'description' => $request->input('description'),
            'sort_order' => ((int) NewsSection::max('sort_order')) + 1,
            'is_active' => true,
            'is_default' => NewsSection::query()->doesntExist(),
            'refresh_interval_minutes' => 10,
            'card_limit' => 20,
        ]);

        return back()->with('success', 'News section created successfully.');
    }

    /**
     * Store a new news topic.
     */
    public function storeTopic(Request $request)
    {
        $request->validate([
            'news_section_id' => 'required|exists:news_sections,id',
            'name' => 'required|string|max:255',
            'keyword' => 'required|string|max:255',
            'language' => 'required|string|size:2',
            'country' => 'required|string|size:2',
        ]);

        NewsTopic::create([
            'news_section_id' => $request->integer('news_section_id'),
            'name' => $request->input('name'),
            'keyword' => $request->input('keyword'),
            'language' => strtolower($request->input('language')),
            'country' => strtoupper($request->input('country')),
            'sort_order' => (int) NewsTopic::query()
                ->where('news_section_id', $request->integer('news_section_id'))
                ->max('sort_order') + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'News topic created successfully!');
    }

    public function toggleSection(NewsSection $section)
    {
        $section->is_active = !$section->is_active;
        $section->save();

        return back()->with('success', "Section '{$section->name}' has been " . ($section->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function setDefaultSection(NewsSection $section)
    {
        NewsSection::query()->update(['is_default' => false]);
        $section->forceFill(['is_default' => true])->save();

        return back()->with('success', "Section '{$section->name}' is now the default homepage section.");
    }

    public function deleteSection(NewsSection $section)
    {
        $name = $section->name;
        $section->delete();

        return back()->with('success', "Section '{$name}' and its topics were deleted.");
    }

    /**
     * Toggle the active status of a topic.
     */
    public function toggleTopic(NewsTopic $topic)
    {
        $topic->is_active = !$topic->is_active;
        $topic->save();

        $status = $topic->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Topic '{$topic->name}' has been {$status}!");
    }

    /**
     * Delete a news topic.
     */
    public function deleteTopic(NewsTopic $topic)
    {
        $topicName = $topic->name;
        $deletedCount = NewsItem::query()->where('news_topic_id', $topic->id)->count();
        $topic->delete();
        app(NewsRetentionService::class)->recordDeletedCount($deletedCount);

        return back()->with('success', "Topic '{$topicName}' and all its associated articles have been deleted.");
    }

    /**
     * Toggle visibility of an article.
     */
    public function toggleArticleVisibility(NewsItem $article)
    {
        $article->is_visible = !$article->is_visible;
        $article->save();

        $status = $article->is_visible ? 'visible' : 'hidden';
        return back()->with('success', "Article visibility updated to {$status}.");
    }

    /**
     * Toggle featured status of an article.
     */
    public function toggleArticleFeatured(NewsItem $article)
    {
        $article->is_featured = !$article->is_featured;
        $article->save();

        $status = $article->is_featured ? 'marked as featured' : 'removed from featured';
        return back()->with('success', "Article {$status}.");
    }

    /**
     * Delete an article.
     */
    public function deleteArticle(NewsItem $article, NewsRetentionService $newsRetention)
    {
        if ($article->is_favorite) {
            return back()->withErrors(['article' => 'Favorite articles are protected from deletion. Remove favorite first.']);
        }

        $article->delete();
        $newsRetention->recordDeletedCount(1);
        return back()->with('success', 'Article deleted successfully.');
    }

    public function bulkDeleteArticles(Request $request, NewsRetentionService $newsRetention)
    {
        $validated = $request->validate([
            'article_ids' => 'required|array|min:1',
            'article_ids.*' => 'integer|exists:news_items,id',
        ]);

        $articles = NewsItem::query()
            ->whereIn('id', $validated['article_ids'])
            ->get();
        $destroySettings = [
            'days' => (int) $request->input('days', $newsRetention->retentionDays()),
            'click_threshold' => (int) $request->input('click_threshold', $newsRetention->clickThreshold()),
            'mode' => $request->input('mode', $newsRetention->mode()),
            'sort' => $request->input('sort', $newsRetention->sort()),
            'batch_limit' => (int) $request->input('batch_limit', $newsRetention->batchLimit()),
        ];

        $eligibleIds = $articles
            ->filter(fn (NewsItem $article) => $newsRetention->isEligibleForDeletion($article, $destroySettings))
            ->pluck('id');

        $deletedCount = NewsItem::query()
            ->whereIn('id', $eligibleIds)
            ->delete();
        $newsRetention->recordDeletedCount($deletedCount);

        $skippedCount = $articles->count() - $deletedCount;

        return redirect()
            ->route('admin.destroy', $request->only(['section', 'topic', 'search', 'sort', 'mode', 'days', 'click_threshold', 'batch_limit']))
            ->with('success', "{$deletedCount} article(s) deleted. {$skippedCount} protected article(s) were skipped.");
    }

    public function toggleArticleFavorite(NewsItem $article)
    {
        $article->is_favorite = !$article->is_favorite;
        $article->save();

        $status = $article->is_favorite ? 'added to favorites and protected from deletion' : 'removed from favorites';

        return back()->with('success', "Article {$status}.");
    }

    /**
     * Trigger manual news fetch command.
     */
    public function fetchNewsNow()
    {
        $syncState = $this->syncState();

        if (in_array($syncState['status'], ['queued', 'running'], true) && !$syncState['is_stale']) {
            return back()->with('success', 'A news sync is already in progress. The live monitor will continue updating below.');
        }

        if ($syncState['is_stale']) {
            $this->stopTrackedSyncProcess('Previous sync state was stale and has been replaced with a fresh run.');
        }

        [$started, $message] = $this->launchQueuedSync('Manual sync requested from admin dashboard.');

        return back()->with('success', $started
            ? 'News sync started in background. The live monitor below will update progress automatically.'
            : $message);
    }

    public function stopAndResync()
    {
        $this->stopTrackedSyncProcess('Active sync was stopped manually before restarting.');

        [$started, $message] = $this->launchQueuedSync('Manual stop and resync requested from admin dashboard.');

        return back()->with('success', $started
            ? 'Existing sync was stopped and a fresh background sync has been started.'
            : $message);
    }

    public function syncStatus(AutomaticNewsSyncService $automaticNewsSync): JsonResponse
    {
        $automaticNewsSync->maybeTriggerDueSync('Automatic fallback sync triggered from admin sync monitor.');

        return response()->json($this->syncState($automaticNewsSync));
    }

    /**
     * Update admin profile settings (passcode and name).
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'current_passcode' => 'required_with:new_passcode|nullable|string',
            'new_passcode' => 'nullable|string|min:4|confirmed',
        ]);

        // If trying to change passcode, check current passcode
        if ($request->filled('new_passcode')) {
            if ($request->input('current_passcode') !== $this->getAdminPasscode()) {
                return back()->withErrors(['current_passcode' => 'Current passcode is incorrect.']);
            }
            Setting::set('admin_passcode', $request->input('new_passcode'));
        }

        Setting::set('admin_name', $request->input('name'));

        return back()->with('success', 'Admin profile details updated successfully!');
    }

    protected function syncState(?AutomaticNewsSyncService $automaticNewsSync = null): array
    {
        $status = Setting::get('news_sync_status', 'idle');
        $requestedAt = Setting::get('news_sync_requested_at');
        $startedAt = Setting::get('news_sync_started_at');
        $finishedAt = Setting::get('news_sync_finished_at');
        $isStale = $this->syncIsStale($status, $requestedAt, $startedAt, $finishedAt);
        $automaticNewsSync ??= app(AutomaticNewsSyncService::class);

        return [
            'status' => $isStale ? 'stalled' : $status,
            'raw_status' => $status,
            'is_stale' => $isStale,
            'requested_at' => $requestedAt,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'last_output' => Setting::get('news_sync_last_output'),
            'meta' => json_decode(Setting::get('news_sync_meta', '{}') ?: '{}', true) ?: [],
            'log' => json_decode(Setting::get('news_sync_log', '[]') ?: '[]', true) ?: [],
            'fetch_stats' => $automaticNewsSync->fetchStats(),
        ];
    }

    protected function fetchStats(?AutomaticNewsSyncService $automaticNewsSync = null): array
    {
        $automaticNewsSync ??= app(AutomaticNewsSyncService::class);

        return $automaticNewsSync->fetchStats();
    }

    protected function analyticsSummary(VisitorMetricsService $visitorMetrics): array
    {
        $today = now()->toDateString();
        $masterPointExpression = '(FLOOR(views_count / 1000) * 1000) + (clicks_count * 25) + (CASE WHEN views_count > 0 AND ((clicks_count * 100.0) / views_count) > 5 THEN 100 ELSE 0 END)';

        return array_merge($visitorMetrics->articleAnalyticsSummary(), [
            'daily_ladder' => $visitorMetrics->dailyViewLadder(),
            'master_ladder' => $visitorMetrics->masterPointLadder(),
            'top_viewed' => NewsItem::with('newsTopic')
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->take(12)
                ->get()
                ->values()
                ->map(function (NewsItem $article, int $index) use ($visitorMetrics) {
                    $article->setAttribute('view_rank', $visitorMetrics->viewRank((int) $article->views_count, $index + 1));

                    return $article;
                }),
            'top_daily_ranked' => NewsItemDailyMetric::query()
                ->with(['newsItem.newsTopic'])
                ->whereDate('metric_date', $today)
                ->orderByDesc('views_count')
                ->orderByDesc('clicks_count')
                ->take(12)
                ->get()
                ->values()
                ->map(function (NewsItemDailyMetric $metric) use ($visitorMetrics) {
                    $metric->setAttribute('daily_rank', $visitorMetrics->dailyViewRank((int) $metric->views_count));

                    return $metric;
                }),
            'top_master_ranked' => NewsItem::query()
                ->with('newsTopic')
                ->select('news_items.*')
                ->selectRaw("{$masterPointExpression} as master_points")
                ->orderByRaw("{$masterPointExpression} desc")
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->take(12)
                ->get()
                ->values()
                ->map(function (NewsItem $article) use ($visitorMetrics) {
                    $article->setAttribute('master_rank', $visitorMetrics->masterPointRank((int) $article->master_points));
                    $article->setAttribute('master_points_breakdown', $visitorMetrics->masterPointsForCounts(
                        (int) $article->views_count,
                        (int) $article->clicks_count
                    ));

                    return $article;
                }),
            'top_clicked' => NewsItem::with('newsTopic')->orderByDesc('clicks_count')->orderByDesc('published_at')->take(12)->get(),
            'recent_activity' => NewsItem::with('newsTopic')
                ->orderByDesc('last_clicked_at')
                ->orderByDesc('last_viewed_at')
                ->take(12)
                ->get(),
        ]);
    }

    protected function contentAnalyticsSummary(NewsRetentionService $newsRetention): array
    {
        $today = now()->toDateString();
        $lastHour = now()->subHour();

        $totalPosts = NewsItem::query()->count();
        $visiblePosts = NewsItem::query()->where('is_visible', true)->count();
        $hiddenPosts = max(0, $totalPosts - $visiblePosts);
        $todayPosts = NewsItem::query()->whereDate('published_at', $today)->count();
        $lastHourPosts = NewsItem::query()->where('published_at', '>=', $lastHour)->count();
        $featuredPosts = NewsItem::query()->where('is_featured', true)->count();
        $favoritePosts = NewsItem::query()->where('is_favorite', true)->count();
        $trendPosts = NewsItem::query()
            ->whereHas('newsSection', fn ($query) => $query->where('slug', 'google-trends'))
            ->count();
        $extractedPosts = NewsItem::query()->whereIn('extraction_status', ['completed', 'extracted'])->count();
        $destroyReady = $newsRetention->eligibleQuery()->count();
        $destroyProtected = $newsRetention->protectedQuery()->count();

        return [
            'total_posts' => $totalPosts,
            'visible_posts' => $visiblePosts,
            'hidden_posts' => $hiddenPosts,
            'today_posts' => $todayPosts,
            'last_hour_posts' => $lastHourPosts,
            'featured_posts' => $featuredPosts,
            'favorite_posts' => $favoritePosts,
            'trend_posts' => $trendPosts,
            'extracted_posts' => $extractedPosts,
            'destroyed_total' => $newsRetention->totalDestroyedCount(),
            'destroyed_last_run' => (int) Setting::get('news_prune_last_deleted_count', '0'),
            'destroy_ready' => $destroyReady,
            'destroy_protected' => $destroyProtected,
            'latest_posts' => NewsItem::query()
                ->with(['newsTopic', 'newsSection'])
                ->orderByDesc('published_at')
                ->take(8)
                ->get(),
            'top_sections' => NewsSection::query()
                ->withCount('newsItems')
                ->orderByDesc('news_items_count')
                ->orderBy('name')
                ->take(8)
                ->get(),
        ];
    }

    protected function articlePageAnalyticsSummary(): array
    {
        $today = now()->toDateString();
        $detailViewsTotal = (int) Setting::get(
            'article_detail_page_views_total',
            (string) NewsItem::query()->sum('detail_views_count')
        );
        $detailViewsToday = (int) Setting::get('article_detail_page_views_' . $today, '0');
        $totalPages = NewsItem::query()->whereNotNull('slug')->where('slug', '!=', '')->count();
        $livePages = NewsItem::query()->where('is_visible', true)->whereNotNull('slug')->where('slug', '!=', '')->count();
        $sourceClicksTotal = (int) NewsItem::query()->sum('clicks_count');
        $sourceClicksToday = (int) NewsItemDailyMetric::query()->whereDate('metric_date', $today)->sum('clicks_count');
        $readyPages = NewsItem::query()->whereIn('extraction_status', ['completed', 'extracted'])->count();
        $pendingPages = NewsItem::query()->whereIn('extraction_status', ['pending', 'retry'])->count();
        $detailClickRate = $detailViewsTotal > 0 ? round(($sourceClicksTotal / $detailViewsTotal) * 100, 2) : 0.0;

        return [
            'total_pages' => $totalPages,
            'live_pages' => $livePages,
            'detail_views_total' => $detailViewsTotal,
            'detail_views_today' => $detailViewsToday,
            'source_clicks_total' => $sourceClicksTotal,
            'source_clicks_today' => $sourceClicksToday,
            'ready_pages' => $readyPages,
            'pending_pages' => $pendingPages,
            'detail_click_rate' => $detailClickRate,
            'top_pages' => NewsItem::query()
                ->with(['newsTopic', 'newsSection'])
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->orderByDesc('detail_views_count')
                ->orderByDesc('published_at')
                ->take(10)
                ->get(),
            'latest_links' => NewsItem::query()
                ->with(['newsTopic', 'newsSection'])
                ->where('is_visible', true)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->take(12)
                ->get(),
        ];
    }

    protected function keralaLotteryAnalyticsSummary(): array
    {
        $timezone = KeralaLotteryService::TIMEZONE;
        $nowInIndia = now($timezone);
        $today = $nowInIndia->toDateString();
        $firstWindow = $nowInIndia->copy()->setTime(
            KeralaLotteryService::FIRST_FETCH_HOUR,
            KeralaLotteryService::FIRST_FETCH_MINUTE
        );

        if (!Schema::hasTable('lottery_results')) {
            return [
                'today_result' => null,
                'latest_result' => null,
                'recent_results' => collect(),
                'total_results' => 0,
                'available_results' => 0,
                'parse_failed_results' => 0,
                'pdf_waiting_results' => 0,
                'today_result_count' => 0,
                'today_status' => 'not_ready',
                'last_attempt_at' => null,
                'last_status' => 'not_ready',
                'last_message' => 'Lottery results table is not ready yet.',
                'next_attempt_at' => $firstWindow,
                'window_opens_at' => $firstWindow,
                'india_now' => $nowInIndia,
            ];
        }

        $todayResult = LotteryResult::query()
            ->whereDate('result_date', $today)
            ->orderByDesc('id')
            ->first();

        $latestResult = LotteryResult::query()
            ->orderByDesc('result_date')
            ->orderByDesc('id')
            ->first();

        $lastAttemptAt = Setting::get('lottery_kerala_last_attempt_at');
        $lastStatus = Setting::get('lottery_kerala_last_status', 'waiting_for_window');
        $lastMessage = Setting::get('lottery_kerala_message', 'Waiting for the official Kerala lottery result.');
        $storedNextAttemptAt = Setting::get('lottery_kerala_next_attempt_at');

        $nextAttemptAt = $storedNextAttemptAt
            ? Carbon::parse($storedNextAttemptAt)
            : ($todayResult
                ? $firstWindow->copy()->addDay()
                : ($nowInIndia->lt($firstWindow) ? $firstWindow : $nowInIndia->copy()->addMinutes(30)));

        return [
            'today_result' => $todayResult,
            'latest_result' => $latestResult,
            'recent_results' => LotteryResult::query()
                ->orderByDesc('result_date')
                ->orderByDesc('id')
                ->take(8)
                ->get(),
            'total_results' => LotteryResult::query()->count(),
            'available_results' => LotteryResult::query()->where('status', 'available')->count(),
            'parse_failed_results' => LotteryResult::query()->whereIn('status', ['parse_failed', 'failed'])->count(),
            'pdf_waiting_results' => LotteryResult::query()->whereIn('status', ['waiting', 'pdf_available'])->count(),
            'today_result_count' => LotteryResult::query()->whereDate('result_date', $today)->count(),
            'page_views_total' => (int) Setting::get('lottery_page_views_total', '0'),
            'page_views_today' => (int) Setting::get('lottery_page_views_' . $today, '0'),
            'today_result_views_total' => $todayResult ? (int) Setting::get('lottery_result_views_total_' . $todayResult->id, '0') : 0,
            'today_result_views_today' => $todayResult ? (int) Setting::get('lottery_result_views_' . $today . '_' . $todayResult->id, '0') : 0,
            'today_status' => $todayResult?->status ?? $lastStatus,
            'last_attempt_at' => $lastAttemptAt ? Carbon::parse($lastAttemptAt) : null,
            'last_status' => $lastStatus,
            'last_message' => $lastMessage,
            'next_attempt_at' => $nextAttemptAt,
            'window_opens_at' => $firstWindow,
            'india_now' => $nowInIndia,
        ];
    }

    protected function buildArticleQuery($selectedSectionId, $selectedTopicId, ?string $search, string $sort)
    {
        $articlesQuery = NewsItem::with(['newsTopic', 'newsSection'])
            ->when($sort === 'most_viewed', fn ($query) => $query->orderByDesc('views_count')->orderByDesc('published_at'))
            ->when($sort === 'most_clicked', fn ($query) => $query->orderByDesc('clicks_count')->orderByDesc('published_at'))
            ->when($sort === 'least_clicked', fn ($query) => $query->orderBy('clicks_count')->orderBy('views_count')->orderBy('published_at'))
            ->when($sort === 'least_viewed', fn ($query) => $query->orderBy('views_count')->orderBy('clicks_count')->orderBy('published_at'))
            ->when($sort === 'oldest', fn ($query) => $query->orderBy('published_at')->orderBy('id'))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->when(!in_array($sort, ['most_viewed', 'most_clicked', 'least_clicked', 'least_viewed', 'oldest', 'title'], true), fn ($query) => $query->orderBy('published_at', 'desc')->orderBy('id', 'desc'));

        if ($selectedSectionId && $selectedSectionId !== 'all') {
            $articlesQuery->where('news_section_id', $selectedSectionId);
        } else {
            $articlesQuery->where(function ($q) {
                $q->whereNull('news_section_id')
                    ->orWhereHas('newsSection', function ($sub) {
                        $sub->where('slug', '!=', 'google-trends');
                    });
            });
        }

        if ($selectedTopicId && $selectedTopicId !== 'all') {
            $articlesQuery->where('news_topic_id', $selectedTopicId);
        }

        if ($search) {
            $articlesQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('source_name', 'like', "%{$search}%");
            });
        }

        return $articlesQuery;
    }

    protected function buildDestroyQuery($selectedSectionId, $selectedTopicId, ?string $search, array $destroySettings, NewsRetentionService $newsRetention)
    {
        $articlesQuery = $newsRetention->applySort(
            $newsRetention->eligibleQuery($destroySettings)->with(['newsTopic', 'newsSection']),
            $destroySettings['sort']
        );

        if ($selectedSectionId && $selectedSectionId !== 'all') {
            $articlesQuery->where('news_section_id', $selectedSectionId);
        }

        if ($selectedTopicId && $selectedTopicId !== 'all') {
            $articlesQuery->where('news_topic_id', $selectedTopicId);
        }

        if ($search) {
            $articlesQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('source_name', 'like', "%{$search}%");
            });
        }

        return $articlesQuery;
    }

    protected function liveUserChart(): array
    {
        $points = collect(range(0, 11))
            ->map(function (int $offset) {
                $hour = now()->copy()->startOfHour()->subHours(11 - $offset);

                return [
                    'label' => $hour->format('H:00'),
                    'value' => DB::table('visitor_analytics')
                        ->whereDate('visit_date', now()->toDateString())
                        ->whereBetween('last_seen_at', [$hour, $hour->copy()->endOfHour()])
                        ->count(),
                ];
            })
            ->values();

        return [
            'title' => 'Live Users',
            'subtitle' => 'Hourly active visitor records for today',
            'total' => (int) $points->sum('value'),
            'headline' => (int) DB::table('visitor_analytics')
                ->whereDate('visit_date', now()->toDateString())
                ->where('last_seen_at', '>=', now()->subMinutes(5))
                ->count(),
            'headline_label' => 'active now',
            'points' => $points->all(),
            'max' => max(1, (int) $points->max('value')),
        ];
    }

    protected function newsTotalChart(): array
    {
        $points = collect(range(6, 0))
            ->reverse()
            ->map(function (int $daysAgo) {
                $day = now()->copy()->subDays($daysAgo);

                return [
                    'label' => $day->format('M d'),
                    'value' => NewsItem::query()
                        ->whereDate('published_at', $day->toDateString())
                        ->count(),
                ];
            })
            ->values();

        return [
            'title' => 'Total News On Site',
            'subtitle' => 'Stories published over the last 7 days',
            'total' => NewsItem::query()->count(),
            'headline' => NewsItem::query()->count(),
            'headline_label' => 'stories total',
            'points' => $points->all(),
            'max' => max(1, (int) $points->max('value')),
        ];
    }

    protected function hourlyPublishChart(): array
    {
        $points = collect(range(0, 11))
            ->map(function (int $offset) {
                $hour = now()->copy()->startOfHour()->subHours(11 - $offset);

                return [
                    'label' => $hour->format('H:00'),
                    'value' => NewsItem::query()
                        ->whereBetween('published_at', [$hour, $hour->copy()->endOfHour()])
                        ->count(),
                ];
            })
            ->values();

        return [
            'title' => 'Last 12 Hours',
            'subtitle' => 'Publishing volume in the last 12 hours',
            'total' => (int) $points->sum('value'),
            'headline' => NewsItem::query()->where('published_at', '>=', now()->subHour())->count(),
            'headline_label' => 'posts in last hour',
            'points' => $points->all(),
            'max' => max(1, (int) $points->max('value')),
        ];
    }

    protected function registeredUsersChart(): array
    {
        $points = collect(range(6, 0))
            ->reverse()
            ->map(function (int $daysAgo) {
                $day = now()->copy()->subDays($daysAgo);

                return [
                    'label' => $day->format('M d'),
                    'value' => User::query()
                        ->whereDate('created_at', $day->toDateString())
                        ->count(),
                ];
            })
            ->values();

        return [
            'title' => 'Registered Users',
            'subtitle' => 'New accounts created over the last 7 days',
            'total' => User::query()->count(),
            'headline' => User::query()->count(),
            'headline_label' => 'accounts total',
            'points' => $points->all(),
            'max' => max(1, (int) $points->max('value')),
        ];
    }

    protected function returningVisitorsChart(): array
    {
        $points = collect(range(6, 0))
            ->reverse()
            ->map(function (int $daysAgo) {
                $day = now()->copy()->subDays($daysAgo)->toDateString();

                return [
                    'label' => now()->copy()->subDays($daysAgo)->format('M d'),
                    'value' => DB::table('visitor_analytics as current_day')
                        ->whereDate('current_day.visit_date', $day)
                        ->whereExists(function ($query) use ($day) {
                            $query->select(DB::raw(1))
                                ->from('visitor_analytics as prior_day')
                                ->whereColumn('prior_day.fingerprint', 'current_day.fingerprint')
                                ->whereDate('prior_day.visit_date', '<', $day);
                        })
                        ->count(),
                ];
            })
            ->values();

        $today = now()->toDateString();
        $returningToday = DB::table('visitor_analytics as current_day')
            ->whereDate('current_day.visit_date', $today)
            ->whereExists(function ($query) use ($today) {
                $query->select(DB::raw(1))
                    ->from('visitor_analytics as prior_day')
                    ->whereColumn('prior_day.fingerprint', 'current_day.fingerprint')
                    ->whereDate('prior_day.visit_date', '<', $today);
            })
            ->count();

        $returningOverall = DB::table('visitor_analytics')
            ->select('fingerprint')
            ->groupBy('fingerprint')
            ->havingRaw('COUNT(DISTINCT visit_date) > 1')
            ->get()
            ->count();

        return [
            'title' => 'Returning Visitors',
            'subtitle' => 'Repeat visitors seen across different days',
            'total' => $returningOverall,
            'headline' => $returningToday,
            'headline_label' => 'returning today',
            'points' => $points->all(),
            'max' => max(1, (int) $points->max('value')),
        ];
    }

    protected function startDetachedQueueWorker(): ?string
    {
        if (app()->environment('testing') || !function_exists('exec')) {
            return app()->environment('testing') ? 'testing-worker' : null;
        }

        $workerCommand = sprintf(
            '%s artisan queue:work --stop-when-empty --queue=syncs,default --tries=1 --timeout=900',
            escapeshellarg($this->phpCliBinary())
        );

        $shellCommand = sprintf(
            'cd %s && if command -v setsid >/dev/null 2>&1; then setsid %s > %s 2>&1 < /dev/null & echo $!; else %s > %s 2>&1 < /dev/null & echo $!; fi',
            escapeshellarg(base_path()),
            $workerCommand,
            escapeshellarg(storage_path('logs/news-sync-worker.log')),
            $workerCommand,
            escapeshellarg(storage_path('logs/news-sync-worker.log'))
        );
        $command = '/bin/sh -c ' . escapeshellarg($shellCommand);

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        $pid = trim($output[0] ?? '');

        return $pid !== '' ? $pid : null;
    }

    protected function phpCliBinary(): string
    {
        $candidates = [
            PHP_BINDIR . DIRECTORY_SEPARATOR . 'php',
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
        ];

        if (PHP_SAPI === 'cli' && !str_contains(basename(PHP_BINARY), 'php-fpm')) {
            array_unshift($candidates, PHP_BINARY);
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }

    protected function launchQueuedSync(string $requestedMessage): array
    {
        $this->releaseSyncUniqueLock();

        Setting::set('news_sync_status', 'queued');
        Setting::set('news_sync_requested_at', now()->toIso8601String());
        Setting::set('news_sync_started_at', null);
        Setting::set('news_sync_finished_at', null);
        Setting::set('news_sync_last_output', null);
        Setting::set('news_sync_process_id', null);
        Setting::set('news_sync_meta', json_encode([
            'progress' => 0,
            'stage' => 'Queueing sync job',
            'processed_topics' => 0,
            'total_topics' => 0,
            'stats' => [
                'new_articles' => 0,
                'skipped_duplicates' => 0,
                'images_recovered' => 0,
            ],
        ]));
        Setting::set('news_sync_log', json_encode([
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => $requestedMessage,
            ],
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => 'Dispatching sync job to the background queue.',
            ],
        ]));

        if (!app()->environment('testing')) {
            RunNewsSyncCycle::dispatch($requestedMessage);
        }

        $pid = $this->startDetachedQueueWorker();

        if ($pid === null) {
            Setting::set('news_sync_status', 'failed');
            Setting::set('news_sync_finished_at', now()->toIso8601String());
            Setting::set('news_sync_last_output', 'Unable to launch detached queue worker.');

            return [false, 'Could not start the detached queue worker. Check PHP exec availability and server logs.'];
        }

        Setting::set('news_sync_process_id', $pid);
        Setting::set('news_sync_log', json_encode([
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => $requestedMessage,
            ],
            [
                'time' => now()->toIso8601String(),
                'level' => 'success',
                'message' => "Detached queue worker started with PID {$pid}.",
            ],
        ]));

        return [true, 'started'];
    }

    protected function stopTrackedSyncProcess(string $message): void
    {
        $pid = Setting::get('news_sync_process_id');

        if ($pid && !$this->killProcessById($pid)) {
            $message .= ' Worker process could not be terminated cleanly.';
        }

        $this->releaseSyncUniqueLock();

        Setting::set('news_sync_status', 'stopped');
        Setting::set('news_sync_finished_at', now()->toIso8601String());
        Setting::set('news_sync_process_id', null);
        Setting::set('news_sync_last_output', $message);
        Setting::set('news_sync_log', json_encode([
            [
                'time' => now()->toIso8601String(),
                'level' => 'warning',
                'message' => $message,
            ],
        ]));
    }

    protected function killProcessById(string $pid): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $pid = trim($pid);

        if ($pid === '' || !ctype_digit($pid)) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill((int) $pid, 15);
        }

        if (!function_exists('exec')) {
            return false;
        }

        $output = [];
        $exitCode = 0;
        exec('/bin/kill -15 ' . escapeshellarg($pid), $output, $exitCode);

        return $exitCode === 0;
    }

    protected function releaseSyncUniqueLock(): void
    {
        app(UniqueLock::class)->release(new RunNewsSyncCycle());
    }

    protected function nextScheduledFetchAt(int $intervalMinutes): Carbon
    {
        $now = now();
        $next = $now->copy()->second(0);
        $minutesToAdd = $intervalMinutes - ($now->minute % $intervalMinutes);

        if ($minutesToAdd === 0) {
            $minutesToAdd = $intervalMinutes;
        }

        return $next->addMinutes($minutesToAdd);
    }

    protected function syncIsStale(?string $status, ?string $requestedAt, ?string $startedAt, ?string $finishedAt): bool
    {
        if (!in_array($status, ['queued', 'running'], true) || $finishedAt) {
            return false;
        }

        try {
            if ($status === 'queued' && $requestedAt && Carbon::parse($requestedAt)->lt(now()->subSeconds(20)) && !$startedAt) {
                return true;
            }

            if ($status === 'running' && $startedAt && Carbon::parse($startedAt)->lt(now()->subMinutes(20))) {
                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
}
