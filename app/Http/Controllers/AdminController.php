<?php

namespace App\Http\Controllers;

use App\Jobs\RunNewsSyncCycle;
use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use App\Models\User;
use App\Services\VisitorMetricsService;
use Illuminate\Bus\UniqueLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
    public function index(Request $request, VisitorMetricsService $visitorMetrics)
    {
        $sections = NewsSection::withCount([
                'newsTopics',
                'newsItems',
            ])
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
        $analyticsSummary = array_merge($visitorMetrics->articleAnalyticsSummary(), [
            'top_viewed' => NewsItem::with('newsTopic')->orderByDesc('views_count')->orderByDesc('published_at')->take(12)->get(),
            'top_clicked' => NewsItem::with('newsTopic')->orderByDesc('clicks_count')->orderByDesc('published_at')->take(12)->get(),
            'recent_activity' => NewsItem::with('newsTopic')
                ->orderByDesc('last_clicked_at')
                ->orderByDesc('last_viewed_at')
                ->take(12)
                ->get(),
        ]);
        $analyticsCharts = [
            'live_users' => $this->liveUserChart(),
            'news_total' => $this->newsTotalChart(),
            'registered_users' => $this->registeredUsersChart(),
            'returning_visitors' => $this->returningVisitorsChart(),
        ];
        $fetchStats = $this->fetchStats();

        return view('admin.analytics', compact('visitStats', 'analyticsSummary', 'visitorSnapshot', 'analyticsCharts', 'fetchStats'));
    }

    public function destroyPage(Request $request)
    {
        $sections = NewsSection::with(['newsTopics' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $search = $request->input('search');
        $selectedTopicId = $request->input('topic');
        $selectedSectionId = $request->input('section', 'all');
        $sort = $request->input('sort', 'least_clicked');

        $articles = $this->buildArticleQuery($selectedSectionId, $selectedTopicId, $search, $sort)->paginate(25);
        $statsQuery = $this->buildArticleQuery($selectedSectionId, $selectedTopicId, $search, $sort);
        $destroyStats = [
            'eligible_articles' => (clone $statsQuery)->count(),
            'zero_click_articles' => (clone $statsQuery)->where('clicks_count', '<=', 0)->count(),
            'zero_view_articles' => (clone $statsQuery)->where('views_count', '<=', 0)->count(),
            'least_clicked_total' => (clone $statsQuery)->sum('clicks_count'),
        ];
        $fetchStats = $this->fetchStats();

        return view('admin.destroy', compact(
            'sections',
            'articles',
            'selectedTopicId',
            'selectedSectionId',
            'search',
            'sort',
            'destroyStats',
            'fetchStats'
        ));
    }

    public function promotions()
    {
        $fetchStats = $this->fetchStats();
        $promotions = [
            'quotex_url' => Setting::get('promo_quotex_url', config('services.promotions.quotex_url')),
            'signals_url' => Setting::get('promo_signals_url', config('services.promotions.signals_url')),
        ];

        return view('admin.promotions', compact('promotions', 'fetchStats'));
    }

    public function updatePromotions(Request $request)
    {
        $request->validate([
            'quotex_url' => 'nullable|string|max:1000',
            'signals_url' => 'nullable|string|max:1000',
        ]);

        $quotexUrl = $this->normalizePromotionUrl($request->input('quotex_url'));
        $signalsUrl = $this->normalizePromotionUrl($request->input('signals_url'));

        validator([
            'quotex_url' => $quotexUrl,
            'signals_url' => $signalsUrl,
        ], [
            'quotex_url' => 'nullable|url|max:1000',
            'signals_url' => 'nullable|url|max:1000',
        ])->validate();

        Setting::set('promo_quotex_url', $quotexUrl);
        Setting::set('promo_signals_url', $signalsUrl);

        return back()->with('success', 'Promotion links updated successfully.');
    }

    protected function normalizePromotionUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        return $value;
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
            'card_limit' => 6,
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
        $topic->delete();

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
    public function deleteArticle(NewsItem $article)
    {
        $article->delete();
        return back()->with('success', 'Article deleted successfully.');
    }

    public function bulkDeleteArticles(Request $request)
    {
        $validated = $request->validate([
            'article_ids' => 'required|array|min:1',
            'article_ids.*' => 'integer|exists:news_items,id',
        ]);

        $deletedCount = NewsItem::query()
            ->whereIn('id', $validated['article_ids'])
            ->delete();

        return redirect()
            ->route('admin.destroy', $request->only(['section', 'topic', 'search', 'sort']))
            ->with('success', "{$deletedCount} article(s) deleted successfully.");
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

    public function syncStatus(): JsonResponse
    {
        return response()->json($this->syncState());
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

    protected function syncState(): array
    {
        $status = Setting::get('news_sync_status', 'idle');
        $requestedAt = Setting::get('news_sync_requested_at');
        $startedAt = Setting::get('news_sync_started_at');
        $finishedAt = Setting::get('news_sync_finished_at');
        $isStale = $this->syncIsStale($status, $requestedAt, $startedAt, $finishedAt);

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
            'fetch_stats' => $this->fetchStats(),
        ];
    }

    protected function fetchStats(): array
    {
        return [
            'total_runs' => (int) Setting::get('news_sync_total_runs', '0'),
            'last_success_at' => Setting::get('news_sync_last_success_at'),
            'interval_minutes' => 10,
            'section_count' => (int) NewsSection::where('is_active', true)->count(),
            'next_scheduled_at' => $this->nextScheduledFetchAt(10)?->toIso8601String(),
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
