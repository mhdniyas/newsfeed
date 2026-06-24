<?php

namespace App\Services;

use App\Models\NewsItem;
use App\Models\NewsItemDailyMetric;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use App\Models\VisitorAnalytic;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VisitorMetricsService
{
    public const PAGE_VISIT_XP = 50;
    public const ARTICLE_VIEW_XP = 10;
    public const SOURCE_CLICK_XP = 30;
    public const DEFAULT_DAILY_TARGET_XP = 10000;
    public const DEFAULT_STREAK_MINIMUM_XP = 5000;

    protected int $liveWindowMinutes = 5;
    protected int $visitDedupSeconds = 90;
    protected int $articleViewDedupSeconds = 1800;
    protected int $articleDetailDedupSeconds = 1800;

    public function recordPublicVisit(Request $request): array
    {
        $fingerprint = $this->fingerprint($request);
        $todayDate = now()->toDateString();
        $pagePath = $this->normalizedPagePath($request);

        $pageViewsTodayKey = 'page_views_public_' . $todayDate;
        $pageViewsTotal = ((int) Setting::get('page_views_public_total', '0')) + 1;
        $pageViewsToday = ((int) Setting::get($pageViewsTodayKey, '0')) + 1;

        Setting::set('page_views_public_total', (string) $pageViewsTotal);
        Setting::set($pageViewsTodayKey, (string) $pageViewsToday);

        $visitor = VisitorAnalytic::where('fingerprint', $fingerprint)
            ->whereDate('visit_date', $todayDate)
            ->first();

        if (!$visitor) {
            $visitor = new VisitorAnalytic([
                'fingerprint' => $fingerprint,
                'visit_date' => $todayDate,
                'visit_count' => 0,
            ]);
        }

        $shouldCountVisit = $this->shouldCountVisit($fingerprint, $pagePath);

        if ($shouldCountVisit) {
            $todayKey = 'visits_public_' . $todayDate;
            $total = ((int) Setting::get('visits_public_total', '0')) + 1;
            $today = ((int) Setting::get($todayKey, '0')) + 1;

            Setting::set('visits_public_total', (string) $total);
            Setting::set($todayKey, (string) $today);
        }

        Setting::set('visits_public_last_seen_at', now()->toIso8601String());

        $visitor->fill(array_merge(
            $this->parseRequestContext($request),
            [
                'ip_address' => $request->ip(),
                'last_seen_at' => now(),
                'page_path' => $pagePath,
                'visit_count' => ((int) $visitor->visit_count) + ($shouldCountVisit ? 1 : 0),
            ]
        ));
        $visitor->save();

        return $this->getPublicStats();
    }

    public function getPublicStats(): array
    {
        return [
            'total' => (int) Setting::get('visits_public_total', '0'),
            'today' => (int) Setting::get('visits_public_' . now()->toDateString(), '0'),
            'page_views_total' => (int) Setting::get('page_views_public_total', '0'),
            'page_views_today' => (int) Setting::get('page_views_public_' . now()->toDateString(), '0'),
            'unique_today' => VisitorAnalytic::whereDate('visit_date', now()->toDateString())->count(),
            'unique_total' => VisitorAnalytic::distinct('fingerprint')->count('fingerprint'),
            'live_now' => $this->liveVisitorsQuery()->count(),
            'last_seen_at' => Setting::get('visits_public_last_seen_at'),
        ];
    }

    public function updateClientContext(Request $request): void
    {
        $request->validate([
            'timezone' => 'nullable|string|max:64',
            'country_code' => 'nullable|string|max:8',
            'page_path' => 'nullable|string|max:255',
        ]);

        $visitor = VisitorAnalytic::query()
            ->where('fingerprint', $this->fingerprint($request))
            ->whereDate('visit_date', now()->toDateString())
            ->first();

        if (!$visitor) {
            $visitor = new VisitorAnalytic([
                'fingerprint' => $this->fingerprint($request),
                'visit_date' => now()->toDateString(),
                'visit_count' => 1,
            ]);
        }

        $visitor->fill(array_merge(
            $this->parseRequestContext($request),
            [
                'ip_address' => $request->ip(),
                'timezone' => $request->string('timezone')->toString() ?: null,
                'country_code' => strtoupper($request->string('country_code')->toString()) ?: null,
                'page_path' => $request->string('page_path')->toString() ?: null,
                'last_seen_at' => now(),
            ]
        ));

        $visitor->save();
    }

    public function articleAnalyticsSummary(): array
    {
        $articleViews = (int) DB::table('news_items')->sum('views_count');
        $articleClicks = (int) DB::table('news_items')->sum('clicks_count');

        $todayViews = $this->metricTotals(now()->toDateString(), now()->toDateString())['views'];
        $todayClicks = $this->metricTotals(now()->toDateString(), now()->toDateString())['clicks'];

        $weekStart = now()->startOfWeek()->toDateString();
        $today = now()->toDateString();
        $weekTotals = $this->metricTotals($weekStart, $today);
        $weekViews = $weekTotals['views'];
        $weekClicks = $weekTotals['clicks'];

        $monthStart = now()->startOfMonth()->toDateString();
        $monthTotals = $this->metricTotals($monthStart, $today);
        $monthViews = $monthTotals['views'];
        $monthClicks = $monthTotals['clicks'];

        $rate = fn (int $clicks, int $views): float => $views > 0 ? round(($clicks / $views) * 100, 2) : 0.0;
        $todayConversionRate = $rate($todayClicks, $todayViews);
        $dailyConversionBonus = $todayConversionRate > 5.0 ? 100 : 0;
        $masterPointBreakdown = $this->masterPointsForCounts($articleViews, $articleClicks, $todayConversionRate);

        return [
            'article_views'  => $articleViews,
            'article_clicks' => $articleClicks,
            'daily_views' => $todayViews,
            'daily_clicks' => $todayClicks,
            'daily_rank' => $this->dailyViewRank($todayViews),
            'master_points' => $masterPointBreakdown['total'],
            'master_points_from_views' => $masterPointBreakdown['view_points'],
            'master_points_from_clicks' => $masterPointBreakdown['click_points'],
            'master_points_from_bonus' => $masterPointBreakdown['bonus_points'],
            'master_rank' => $this->masterPointRank($masterPointBreakdown['total']),
            'view_rank_score' => $masterPointBreakdown['total'],
            'view_rank_bonus' => $dailyConversionBonus,
            'view_rank_bonus_active' => $dailyConversionBonus > 0,
            'view_rank_bonus_threshold' => 5.0,
            'view_rank' => $this->dailyViewRank($todayViews),
            'conversion' => [
                'overall_rate' => $rate($articleClicks, $articleViews),
                'today'  => ['views' => $todayViews,  'clicks' => $todayClicks,  'rate' => $todayConversionRate],
                'week'   => ['views' => $weekViews,   'clicks' => $weekClicks,   'rate' => $rate($weekClicks, $weekViews)],
                'month'  => ['views' => $monthViews,  'clicks' => $monthClicks,  'rate' => $rate($monthClicks, $monthViews)],
            ],
        ];
    }

    public function adminXpDashboard(): array
    {
        $settings = [
            'page_visit_xp' => max(0, (int) Setting::get('admin_xp_page_visit_points', (string) self::PAGE_VISIT_XP)),
            'article_view_xp' => max(0, (int) Setting::get('admin_xp_article_view_points', (string) self::ARTICLE_VIEW_XP)),
            'source_click_xp' => max(0, (int) Setting::get('admin_xp_source_click_points', (string) self::SOURCE_CLICK_XP)),
            'daily_target_xp' => max(1000, (int) Setting::get('admin_xp_daily_target', (string) self::DEFAULT_DAILY_TARGET_XP)),
            'streak_minimum_xp' => max(1000, (int) Setting::get('admin_xp_streak_minimum', (string) self::DEFAULT_STREAK_MINIMUM_XP)),
        ];

        $timeline = $this->buildXpTimeline($settings);
        $today = now()->toDateString();
        $todaySnapshot = $timeline->firstWhere('date', $today) ?? $this->emptyXpDay($today, $settings);

        $currentWeekStart = now()->startOfWeek()->toDateString();
        $previousWeekStart = now()->subWeek()->startOfWeek()->toDateString();
        $previousWeekEnd = now()->subWeek()->endOfWeek()->toDateString();

        $currentWeek = $this->sumTimelineRange($timeline, $currentWeekStart, now()->toDateString());
        $previousWeek = $this->sumTimelineRange($timeline, $previousWeekStart, $previousWeekEnd);
        $weeklyGrowth = $previousWeek['total_xp'] > 0
            ? round((($currentWeek['total_xp'] - $previousWeek['total_xp']) / $previousWeek['total_xp']) * 100, 1)
            : ($currentWeek['total_xp'] > 0 ? 100.0 : 0.0);

        $lifetimeXp = (int) $timeline->sum('total_xp');
        $lifetimeRank = $this->lifetimeXpRank($lifetimeXp);
        $nextLifetimeRank = $this->nextLifetimeXpRank($lifetimeXp);
        $earnedBadges = $this->earnedBadges($timeline, $lifetimeXp);
        $lockedBadges = $this->lockedBadges($timeline, $lifetimeXp);

        $weeklyPoints = collect(range(6, 0))
            ->reverse()
            ->map(function (int $daysAgo) use ($timeline) {
                $date = now()->copy()->subDays($daysAgo)->toDateString();
                $row = $timeline->firstWhere('date', $date);

                return [
                    'label' => Carbon::parse($date)->format('M d'),
                    'value' => (int) ($row['total_xp'] ?? 0),
                ];
            })
            ->values();

        $todayTopCountries = VisitorAnalytic::query()
            ->selectRaw("COALESCE(NULLIF(country_code, ''), 'Unknown') as label, COUNT(*) as total")
            ->whereDate('visit_date', now()->toDateString())
            ->groupBy('label')
            ->orderByDesc('total')
            ->take(6)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();

        $topCategories = NewsSection::query()
            ->leftJoin('news_items', 'news_sections.id', '=', 'news_items.news_section_id')
            ->leftJoin('news_item_daily_metrics', function ($join) {
                $join->on('news_items.id', '=', 'news_item_daily_metrics.news_item_id')
                    ->whereDate('news_item_daily_metrics.metric_date', now()->toDateString());
            })
            ->groupBy('news_sections.id', 'news_sections.name')
            ->selectRaw('news_sections.name, COALESCE(SUM(news_item_daily_metrics.views_count), 0) as today_views')
            ->orderByDesc('today_views')
            ->orderBy('news_sections.name')
            ->take(6)
            ->get()
            ->map(fn ($row) => ['label' => $row->name, 'total' => (int) $row->today_views])
            ->all();

        $topKeywords = NewsTopic::query()
            ->leftJoin('news_items', 'news_topics.id', '=', 'news_items.news_topic_id')
            ->leftJoin('news_item_daily_metrics', function ($join) {
                $join->on('news_items.id', '=', 'news_item_daily_metrics.news_item_id')
                    ->whereDate('news_item_daily_metrics.metric_date', now()->toDateString());
            })
            ->groupBy('news_topics.id', 'news_topics.name')
            ->selectRaw('news_topics.name, COALESCE(SUM(news_item_daily_metrics.views_count), 0) as today_views')
            ->orderByDesc('today_views')
            ->orderBy('news_topics.name')
            ->take(6)
            ->get()
            ->map(fn ($row) => ['label' => $row->name, 'total' => (int) $row->today_views])
            ->all();

        return [
            'point_rules' => [
                ['label' => 'Page Visit', 'points' => $settings['page_visit_xp'], 'note' => 'Unique visit points only'],
                ['label' => 'Article View', 'points' => $settings['article_view_xp'], 'note' => 'Deduped article impressions'],
                ['label' => 'Source Click', 'points' => $settings['source_click_xp'], 'note' => 'Real outbound source clicks'],
                ['label' => 'Ad Click', 'points' => 0, 'note' => 'Disabled for safety'],
            ],
            'today' => $todaySnapshot,
            'week' => [
                'current' => $currentWeek,
                'previous' => $previousWeek,
                'growth_percentage' => $weeklyGrowth,
                'rank' => $this->weeklyXpRank($currentWeek['total_xp']),
            ],
            'lifetime' => [
                'total_xp' => $lifetimeXp,
                'rank' => $lifetimeRank,
                'next_rank' => $nextLifetimeRank,
                'needed_for_next_rank' => $nextLifetimeRank ? max(0, $nextLifetimeRank['threshold'] - $lifetimeXp) : 0,
            ],
            'streak' => [
                'current_days' => (int) ($todaySnapshot['current_streak'] ?? 0),
                'minimum_xp' => $settings['streak_minimum_xp'],
                'next_reward' => $this->nextStreakReward((int) ($todaySnapshot['current_streak'] ?? 0)),
                'latest_bonus_xp' => (int) ($todaySnapshot['streak_bonus_xp'] ?? 0),
            ],
            'missions' => $todaySnapshot['missions'] ?? [],
            'badges' => [
                'earned' => $earnedBadges,
                'locked' => $lockedBadges,
                'recent' => array_slice($earnedBadges, 0, 4),
                'next' => $lockedBadges[0] ?? null,
            ],
            'charts' => [
                'weekly_xp' => [
                    'title' => 'Last 7 Days XP',
                    'headline' => $todaySnapshot['total_xp'],
                    'headline_label' => 'today total xp',
                    'total' => (int) $weeklyPoints->sum('value'),
                    'points' => $weeklyPoints->all(),
                    'max' => max(1, (int) $weeklyPoints->max('value')),
                ],
            ],
            'insights' => [
                'countries' => $todayTopCountries,
                'categories' => $topCategories,
                'keywords' => $topKeywords,
            ],
            'timeline' => $timeline->take(-14)->values()->all(),
            'settings' => $settings,
        ];
    }

    public function trendsAnalyticsSummary(): array
    {
        $sectionId = DB::table('news_sections')->where('slug', 'google-trends')->value('id');

        if (!$sectionId) {
            return [
                'section_name' => 'Google Trends',
                'article_views' => 0,
                'article_clicks' => 0,
                'conversion' => [
                    'overall_rate' => 0.0,
                    'today' => ['views' => 0, 'clicks' => 0, 'rate' => 0.0],
                    'week' => ['views' => 0, 'clicks' => 0, 'rate' => 0.0],
                    'month' => ['views' => 0, 'clicks' => 0, 'rate' => 0.0],
                ],
                'assessment' => $this->conversionAssessment(0.0),
                'top_viewed' => collect(),
                'top_clicked' => collect(),
            ];
        }

        $articleViews = (int) DB::table('news_items')->where('news_section_id', $sectionId)->sum('views_count');
        $articleClicks = (int) DB::table('news_items')->where('news_section_id', $sectionId)->sum('clicks_count');
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $todayTotals = $this->metricTotals($today, $today, $sectionId);
        $weekTotals = $this->metricTotals($weekStart, $today, $sectionId);
        $monthTotals = $this->metricTotals($monthStart, $today, $sectionId);
        $rate = fn (int $clicks, int $views): float => $views > 0 ? round(($clicks / $views) * 100, 2) : 0.0;
        $overallRate = $rate($articleClicks, $articleViews);

        return [
            'section_name' => 'Google Trends',
            'article_views' => $articleViews,
            'article_clicks' => $articleClicks,
            'conversion' => [
                'overall_rate' => $overallRate,
                'today' => [
                    'views' => $todayTotals['views'],
                    'clicks' => $todayTotals['clicks'],
                    'rate' => $rate($todayTotals['clicks'], $todayTotals['views']),
                ],
                'week' => [
                    'views' => $weekTotals['views'],
                    'clicks' => $weekTotals['clicks'],
                    'rate' => $rate($weekTotals['clicks'], $weekTotals['views']),
                ],
                'month' => [
                    'views' => $monthTotals['views'],
                    'clicks' => $monthTotals['clicks'],
                    'rate' => $rate($monthTotals['clicks'], $monthTotals['views']),
                ],
            ],
            'assessment' => $this->conversionAssessment($overallRate),
            'top_viewed' => \App\Models\NewsItem::with('newsTopic')
                ->where('news_section_id', $sectionId)
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->take(8)
                ->get(),
            'top_clicked' => \App\Models\NewsItem::with('newsTopic')
                ->where('news_section_id', $sectionId)
                ->orderByDesc('clicks_count')
                ->orderByDesc('published_at')
                ->take(8)
                ->get(),
        ];
    }

    public function trackArticleImpressions(Request $request, array $articleIds): void
    {
        $articleIds = array_values(array_unique(array_filter(array_map('intval', $articleIds))));

        if ($articleIds === []) {
            return;
        }

        $fingerprint = $this->fingerprint($request);
        $newImpressions = [];

        foreach ($articleIds as $articleId) {
            $cacheKey = "article-impression:{$fingerprint}:{$articleId}";

            if (Cache::add($cacheKey, now()->toIso8601String(), now()->addSeconds($this->articleViewDedupSeconds))) {
                $newImpressions[] = $articleId;
            }
        }

        if ($newImpressions === []) {
            return;
        }

        DB::transaction(function () use ($newImpressions): void {
            DB::table('news_items')
                ->whereIn('id', $newImpressions)
                ->increment('views_count');

            DB::table('news_items')
                ->whereIn('id', $newImpressions)
                ->update(['last_viewed_at' => now()]);

            if (!$this->dailyMetricTableReady()) {
                return;
            }

            foreach ($newImpressions as $articleId) {
                $metric = NewsItemDailyMetric::query()->firstOrCreate(
                    [
                        'news_item_id' => $articleId,
                        'metric_date' => now()->toDateString(),
                    ],
                    [
                        'views_count' => 0,
                        'clicks_count' => 0,
                    ]
                );

                $metric->increment('views_count');
            }
        });
    }

    public function trackArticleClick(int $articleId): void
    {
        DB::transaction(function () use ($articleId): void {
            DB::table('news_items')
                ->where('id', $articleId)
                ->increment('clicks_count');

            DB::table('news_items')
                ->where('id', $articleId)
                ->update(['last_clicked_at' => now()]);

            if (!$this->dailyMetricTableReady()) {
                return;
            }

            $metric = NewsItemDailyMetric::query()->firstOrCreate(
                [
                    'news_item_id' => $articleId,
                    'metric_date' => now()->toDateString(),
                ],
                [
                    'views_count' => 0,
                    'clicks_count' => 0,
                ]
            );

            $metric->increment('clicks_count');
        });
    }

    public function trackArticleDetailView(Request $request, int $articleId): void
    {
        $fingerprint = $this->fingerprint($request);
        $cacheKey = "article-detail:{$fingerprint}:{$articleId}";

        if (!Cache::add($cacheKey, now()->toIso8601String(), now()->addSeconds($this->articleDetailDedupSeconds))) {
            return;
        }

        $today = now()->toDateString();
        Setting::set(
            'article_detail_page_views_total',
            (string) (((int) Setting::get('article_detail_page_views_total', '0')) + 1)
        );
        Setting::set(
            'article_detail_page_views_' . $today,
            (string) (((int) Setting::get('article_detail_page_views_' . $today, '0')) + 1)
        );

        DB::table('news_items')
            ->where('id', $articleId)
            ->increment('detail_views_count');

        DB::table('news_items')
            ->where('id', $articleId)
            ->update(['last_detail_viewed_at' => now()]);
    }

    public function trackTrendPageView(string $slug): void
    {
        $todayKey = 'trend_page_views_' . now()->toDateString() . '_' . $slug;
        $totalKey = 'trend_page_views_total_' . $slug;

        Setting::set($todayKey, (string) (((int) Setting::get($todayKey, '0')) + 1));
        Setting::set($totalKey, (string) (((int) Setting::get($totalKey, '0')) + 1));
    }

    public function trackLotteryPageView(?int $resultId = null): array
    {
        $today = now()->toDateString();
        $todayKey = 'lottery_page_views_' . $today;
        $totalKey = 'lottery_page_views_total';

        $todayViews = ((int) Setting::get($todayKey, '0')) + 1;
        $totalViews = ((int) Setting::get($totalKey, '0')) + 1;

        Setting::set($todayKey, (string) $todayViews);
        Setting::set($totalKey, (string) $totalViews);

        if ($resultId) {
            $resultTodayKey = 'lottery_result_views_' . $today . '_' . $resultId;
            $resultTotalKey = 'lottery_result_views_total_' . $resultId;

            Setting::set($resultTodayKey, (string) (((int) Setting::get($resultTodayKey, '0')) + 1));
            Setting::set($resultTotalKey, (string) (((int) Setting::get($resultTotalKey, '0')) + 1));
        }

        return [
            'today' => $todayViews,
            'total' => $totalViews,
        ];
    }

    public function fingerprintForRequest(Request $request): string
    {
        return $this->fingerprint($request);
    }

    public function dailyViewRank(int $views): array
    {
        return match (true) {
            $views < 1000 => [
                'tier' => 'Bronze',
                'badge' => 'Bronze',
                'tone' => 'amber',
                'range' => '< 1000 views today',
            ],
            $views < 2000 => [
                'tier' => 'Silver',
                'badge' => 'Silver',
                'tone' => 'slate',
                'range' => '1000 - 1999 views today',
            ],
            $views < 3000 => [
                'tier' => 'Gold',
                'badge' => 'Gold',
                'tone' => 'yellow',
                'range' => '2000 - 2999 views today',
            ],
            $views < 4000 => [
                'tier' => 'Platinum',
                'badge' => 'Platinum',
                'tone' => 'emerald',
                'range' => '3000 - 3999 views today',
            ],
            $views < 5000 => [
                'tier' => 'Diamond',
                'badge' => 'Diamond',
                'tone' => 'sky',
                'range' => '4000 - 4999 views today',
            ],
            default => [
                'tier' => 'Conqueror',
                'badge' => 'Conqueror',
                'tone' => 'rose',
                'range' => '5000+ views today',
            ],
        };
    }

    public function masterPointRank(int $points): array
    {
        return match (true) {
            $points < 1000 => [
                'tier' => 'Bronze',
                'badge' => 'Bronze',
                'tone' => 'amber',
                'range' => '< 1000 master points',
            ],
            $points < 2000 => [
                'tier' => 'Silver',
                'badge' => 'Silver',
                'tone' => 'slate',
                'range' => '1000 - 1999 master points',
            ],
            $points < 3000 => [
                'tier' => 'Gold',
                'badge' => 'Gold',
                'tone' => 'yellow',
                'range' => '2000 - 2999 master points',
            ],
            $points < 4000 => [
                'tier' => 'Platinum',
                'badge' => 'Platinum',
                'tone' => 'emerald',
                'range' => '3000 - 3999 master points',
            ],
            $points < 5000 => [
                'tier' => 'Diamond',
                'badge' => 'Diamond',
                'tone' => 'sky',
                'range' => '4000 - 4999 master points',
            ],
            $points < 6000 => [
                'tier' => 'Crown',
                'badge' => 'Crown',
                'tone' => 'violet',
                'range' => '5000 - 5999 master points',
            ],
            $points < 7000 => [
                'tier' => 'Ace',
                'badge' => 'Ace',
                'tone' => 'rose',
                'range' => '6000 - 6999 master points',
            ],
            $points < 8000 => [
                'tier' => 'Ace Master',
                'badge' => 'Ace Master',
                'tone' => 'rose',
                'range' => '7000 - 7999 master points',
            ],
            $points < 9000 => [
                'tier' => 'Ace Dominator',
                'badge' => 'Ace Dominator',
                'tone' => 'rose',
                'range' => '8000 - 8999 master points',
            ],
            default => [
                'tier' => 'Conqueror',
                'badge' => 'Conqueror',
                'tone' => 'rose',
                'range' => '9000+ master points',
            ],
        };
    }

    public function viewRank(int $views, ?int $position = null): array
    {
        return $this->dailyViewRank($views);
    }

    public function masterPointsForCounts(int $views, int $clicks, ?float $conversionRate = null): array
    {
        $conversionRate ??= $views > 0 ? round(($clicks / $views) * 100, 2) : 0.0;

        $viewPoints = intdiv(max($views, 0), 1000) * 1000;
        $clickPoints = max($clicks, 0) * 25;
        $bonusPoints = $conversionRate > 5.0 ? 100 : 0;

        return [
            'view_points' => $viewPoints,
            'click_points' => $clickPoints,
            'bonus_points' => $bonusPoints,
            'total' => $viewPoints + $clickPoints + $bonusPoints,
        ];
    }

    public function dailyViewLadder(): array
    {
        return [
            ['tier' => 'Bronze', 'range' => '< 1000 views today'],
            ['tier' => 'Silver', 'range' => '1000 - 1999 views today'],
            ['tier' => 'Gold', 'range' => '2000 - 2999 views today'],
            ['tier' => 'Platinum', 'range' => '3000 - 3999 views today'],
            ['tier' => 'Diamond', 'range' => '4000 - 4999 views today'],
            ['tier' => 'Conqueror', 'range' => '5000+ views today'],
        ];
    }

    public function masterPointLadder(): array
    {
        return [
            ['tier' => 'Bronze', 'range' => '< 1000 master points'],
            ['tier' => 'Silver', 'range' => '1000 - 1999 master points'],
            ['tier' => 'Gold', 'range' => '2000 - 2999 master points'],
            ['tier' => 'Platinum', 'range' => '3000 - 3999 master points'],
            ['tier' => 'Diamond', 'range' => '4000 - 4999 master points'],
            ['tier' => 'Crown', 'range' => '5000 - 5999 master points'],
            ['tier' => 'Ace', 'range' => '6000 - 6999 master points'],
            ['tier' => 'Ace Master', 'range' => '7000 - 7999 master points'],
            ['tier' => 'Ace Dominator', 'range' => '8000 - 8999 master points'],
            ['tier' => 'Conqueror', 'range' => '9000+ master points'],
        ];
    }

    protected function buildXpTimeline(array $settings)
    {
        $dates = $this->trackedDates();
        $currentStreak = 0;
        $timeline = collect();
        $lifetimeXp = 0;

        foreach ($dates as $date) {
            $day = $this->dayMetricsForDate($date, $settings);
            $missions = $this->dailyMissionsForDay($day, $settings);
            $missionBonusXp = collect($missions)->where('completed', true)->sum('reward_xp');
            $preStreakXp = $day['base_xp'] + $missionBonusXp;

            if ($preStreakXp >= $settings['streak_minimum_xp']) {
                $currentStreak++;
            } else {
                $currentStreak = 0;
            }

            $streakBonusXp = $this->streakRewardForCount($currentStreak);
            $totalXp = $preStreakXp + $streakBonusXp;
            $lifetimeXp += $totalXp;

            $timeline->push(array_merge($day, [
                'missions' => $missions,
                'mission_bonus_xp' => $missionBonusXp,
                'streak_bonus_xp' => $streakBonusXp,
                'total_xp' => $totalXp,
                'target_completed' => $totalXp >= $settings['daily_target_xp'],
                'current_streak' => $currentStreak,
                'lifetime_xp' => $lifetimeXp,
            ]));
        }

        return $timeline;
    }

    protected function trackedDates()
    {
        $visitDates = Setting::query()
            ->where('key', 'like', 'visits_public_%')
            ->pluck('key')
            ->map(function (string $key) {
                if (!str_starts_with($key, 'visits_public_')) {
                    return null;
                }

                $value = substr($key, strlen('visits_public_'));

                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
            })
            ->filter()
            ->values();

        $metricDates = NewsItemDailyMetric::query()
            ->selectRaw('DISTINCT metric_date')
            ->pluck('metric_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $allDates = $visitDates->merge($metricDates)->filter()->unique()->sort()->values();

        if ($allDates->isEmpty()) {
            return collect([now()->toDateString()]);
        }

        $start = Carbon::parse($allDates->first())->startOfDay();
        $end = now()->startOfDay();
        $filled = collect();

        while ($start->lte($end)) {
            $filled->push($start->toDateString());
            $start->addDay();
        }

        return $filled;
    }

    protected function dayMetricsForDate(string $date, array $settings): array
    {
        $article = $this->metricTotals($date, $date);
        $pageVisits = (int) Setting::get('visits_public_' . $date, '0');
        $pageViews = (int) Setting::get('page_views_public_' . $date, '0');
        $countries = VisitorAnalytic::query()
            ->whereDate('visit_date', $date)
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->distinct('country_code')
            ->count('country_code');
        $publishedPosts = NewsItem::query()->whereDate('published_at', $date)->count();

        return [
            'date' => $date,
            'page_visits' => $pageVisits,
            'page_views' => $pageViews,
            'article_views' => (int) $article['views'],
            'source_clicks' => (int) $article['clicks'],
            'countries' => $countries,
            'published_posts' => $publishedPosts,
            'base_xp' => ($pageVisits * $settings['page_visit_xp'])
                + ((int) $article['views'] * $settings['article_view_xp'])
                + ((int) $article['clicks'] * $settings['source_click_xp']),
            'target_xp' => $settings['daily_target_xp'],
        ];
    }

    protected function emptyXpDay(string $date, array $settings): array
    {
        return [
            'date' => $date,
            'page_visits' => 0,
            'page_views' => 0,
            'article_views' => 0,
            'source_clicks' => 0,
            'countries' => 0,
            'published_posts' => 0,
            'base_xp' => 0,
            'target_xp' => $settings['daily_target_xp'],
            'missions' => $this->dailyMissionsForDay([
                'page_visits' => 0,
                'article_views' => 0,
                'source_clicks' => 0,
                'published_posts' => 0,
                'countries' => 0,
                'base_xp' => 0,
            ], $settings),
            'mission_bonus_xp' => 0,
            'streak_bonus_xp' => 0,
            'total_xp' => 0,
            'target_completed' => false,
            'current_streak' => 0,
            'lifetime_xp' => 0,
        ];
    }

    protected function dailyMissionsForDay(array $day, array $settings): array
    {
        return [
            $this->buildMission('Get 100 page visits', (int) $day['page_visits'], 100, 1000, 'visits'),
            $this->buildMission('Get 300 article views', (int) $day['article_views'], 300, 1500, 'views'),
            $this->buildMission('Get 30 source clicks', (int) $day['source_clicks'], 30, 1000, 'clicks'),
            $this->buildMission('Publish or update 100 articles', (int) $day['published_posts'], 100, 2000, 'articles'),
            $this->buildMission('Get traffic from 3 countries', (int) $day['countries'], 3, 1000, 'countries'),
            $this->buildMission('Reach daily XP target', (int) $day['base_xp'], (int) $settings['daily_target_xp'], 2500, 'xp'),
        ];
    }

    protected function buildMission(string $label, int $current, int $target, int $rewardXp, string $unit): array
    {
        return [
            'label' => $label,
            'current' => $current,
            'target' => $target,
            'reward_xp' => $rewardXp,
            'unit' => $unit,
            'completed' => $current >= $target,
        ];
    }

    protected function streakRewardForCount(int $streak): int
    {
        return match ($streak) {
            3 => 1000,
            7 => 5000,
            15 => 15000,
            30 => 50000,
            100 => 250000,
            default => 0,
        };
    }

    protected function nextStreakReward(int $currentStreak): ?array
    {
        $rewards = [
            ['days' => 3, 'reward_xp' => 1000, 'label' => '3-Day Spark'],
            ['days' => 7, 'reward_xp' => 5000, 'label' => '7-Day Signal Builder'],
            ['days' => 15, 'reward_xp' => 15000, 'label' => '15-Day Growth Run'],
            ['days' => 30, 'reward_xp' => 50000, 'label' => '30-Day Operator'],
            ['days' => 100, 'reward_xp' => 250000, 'label' => '100-Day Elite Streak'],
        ];

        foreach ($rewards as $reward) {
            if ($currentStreak < $reward['days']) {
                $reward['remaining_days'] = $reward['days'] - $currentStreak;

                return $reward;
            }
        }

        return null;
    }

    protected function weeklyXpRank(int $xp): array
    {
        $ranks = [
            ['threshold' => 500000, 'name' => 'Signalz Breakout Week', 'tone' => 'rose'],
            ['threshold' => 300000, 'name' => 'Viral Push Week', 'tone' => 'amber'],
            ['threshold' => 150000, 'name' => 'Strong Week', 'tone' => 'emerald'],
            ['threshold' => 75000, 'name' => 'Growth Week', 'tone' => 'sky'],
            ['threshold' => 25000, 'name' => 'Active Week', 'tone' => 'violet'],
            ['threshold' => 0, 'name' => 'Slow Week', 'tone' => 'slate'],
        ];

        foreach ($ranks as $rank) {
            if ($xp >= $rank['threshold']) {
                return $rank;
            }
        }

        return $ranks[array_key_last($ranks)];
    }

    protected function lifetimeXpRanks(): array
    {
        return [
            ['threshold' => 0, 'name' => 'Site Starter', 'tone' => 'slate'],
            ['threshold' => 50000, 'name' => 'News Builder', 'tone' => 'amber'],
            ['threshold' => 150000, 'name' => 'Traffic Hunter', 'tone' => 'yellow'],
            ['threshold' => 500000, 'name' => 'Trend Watcher', 'tone' => 'sky'],
            ['threshold' => 1000000, 'name' => 'Growth Operator', 'tone' => 'emerald'],
            ['threshold' => 2500000, 'name' => 'Signal Analyst', 'tone' => 'violet'],
            ['threshold' => 5000000, 'name' => 'News Strategist', 'tone' => 'amber'],
            ['threshold' => 10000000, 'name' => 'Signal Master', 'tone' => 'rose'],
            ['threshold' => 25000000, 'name' => 'Media Builder', 'tone' => 'sky'],
            ['threshold' => 50000000, 'name' => 'Digital Publisher', 'tone' => 'emerald'],
            ['threshold' => 100000000, 'name' => 'Signalz Legend', 'tone' => 'rose'],
        ];
    }

    protected function lifetimeXpRank(int $xp): array
    {
        return collect($this->lifetimeXpRanks())
            ->reverse()
            ->first(fn (array $rank) => $xp >= $rank['threshold']);
    }

    protected function nextLifetimeXpRank(int $xp): ?array
    {
        foreach ($this->lifetimeXpRanks() as $rank) {
            if ($xp < $rank['threshold']) {
                return $rank;
            }
        }

        return null;
    }

    protected function sumTimelineRange($timeline, string $startDate, string $endDate): array
    {
        $rows = $timeline->filter(fn (array $row) => $row['date'] >= $startDate && $row['date'] <= $endDate);

        return [
            'page_visits' => (int) $rows->sum('page_visits'),
            'article_views' => (int) $rows->sum('article_views'),
            'source_clicks' => (int) $rows->sum('source_clicks'),
            'base_xp' => (int) $rows->sum('base_xp'),
            'mission_bonus_xp' => (int) $rows->sum('mission_bonus_xp'),
            'streak_bonus_xp' => (int) $rows->sum('streak_bonus_xp'),
            'total_xp' => (int) $rows->sum('total_xp'),
        ];
    }

    protected function earnedBadges($timeline, int $lifetimeXp): array
    {
        $maxPageVisits = (int) $timeline->max('page_visits');
        $maxArticleViews = (int) $timeline->max('article_views');
        $maxSourceClicks = (int) $timeline->max('source_clicks');
        $maxDailyXp = (int) $timeline->max('total_xp');
        $maxStreak = (int) $timeline->max('current_streak');

        $definitions = [
            ['name' => 'First Signal', 'description' => 'First 1,000 XP', 'earned' => $lifetimeXp >= 1000],
            ['name' => 'Traffic Starter', 'description' => '100 page visits in a day', 'earned' => $maxPageVisits >= 100],
            ['name' => 'Article Engine', 'description' => '500 article views in a day', 'earned' => $maxArticleViews >= 500],
            ['name' => 'Source Power', 'description' => '100 source clicks in a day', 'earned' => $maxSourceClicks >= 100],
            ['name' => 'Growth Day', 'description' => '10,000 XP in one day', 'earned' => $maxDailyXp >= 10000],
            ['name' => 'Big Push', 'description' => '50,000 XP in one day', 'earned' => $maxDailyXp >= 50000],
            ['name' => 'Viral Watch', 'description' => '100,000 XP in one day', 'earned' => $maxDailyXp >= 100000],
            ['name' => '7-Day Builder', 'description' => '7-day streak', 'earned' => $maxStreak >= 7],
            ['name' => '30-Day Operator', 'description' => '30-day streak', 'earned' => $maxStreak >= 30],
            ['name' => 'Million XP Club', 'description' => '1,000,000 lifetime XP', 'earned' => $lifetimeXp >= 1000000],
            ['name' => 'Signalz Master', 'description' => '10,000,000 lifetime XP', 'earned' => $lifetimeXp >= 10000000],
        ];

        return collect($definitions)
            ->filter(fn (array $badge) => $badge['earned'])
            ->values()
            ->all();
    }

    protected function lockedBadges($timeline, int $lifetimeXp): array
    {
        $earnedNames = collect($this->earnedBadges($timeline, $lifetimeXp))->pluck('name')->all();

        $maxPageVisits = (int) $timeline->max('page_visits');
        $maxArticleViews = (int) $timeline->max('article_views');
        $maxSourceClicks = (int) $timeline->max('source_clicks');
        $maxDailyXp = (int) $timeline->max('total_xp');
        $maxStreak = (int) $timeline->max('current_streak');

        $definitions = [
            ['name' => 'First Signal', 'description' => 'First 1,000 XP', 'progress' => $lifetimeXp, 'target' => 1000, 'unit' => 'xp'],
            ['name' => 'Traffic Starter', 'description' => '100 page visits in a day', 'progress' => $maxPageVisits, 'target' => 100, 'unit' => 'visits'],
            ['name' => 'Article Engine', 'description' => '500 article views in a day', 'progress' => $maxArticleViews, 'target' => 500, 'unit' => 'views'],
            ['name' => 'Source Power', 'description' => '100 source clicks in a day', 'progress' => $maxSourceClicks, 'target' => 100, 'unit' => 'clicks'],
            ['name' => 'Growth Day', 'description' => '10,000 XP in one day', 'progress' => $maxDailyXp, 'target' => 10000, 'unit' => 'xp'],
            ['name' => 'Big Push', 'description' => '50,000 XP in one day', 'progress' => $maxDailyXp, 'target' => 50000, 'unit' => 'xp'],
            ['name' => 'Viral Watch', 'description' => '100,000 XP in one day', 'progress' => $maxDailyXp, 'target' => 100000, 'unit' => 'xp'],
            ['name' => '7-Day Builder', 'description' => '7-day streak', 'progress' => $maxStreak, 'target' => 7, 'unit' => 'days'],
            ['name' => '30-Day Operator', 'description' => '30-day streak', 'progress' => $maxStreak, 'target' => 30, 'unit' => 'days'],
            ['name' => 'Million XP Club', 'description' => '1,000,000 lifetime XP', 'progress' => $lifetimeXp, 'target' => 1000000, 'unit' => 'xp'],
            ['name' => 'Signalz Master', 'description' => '10,000,000 lifetime XP', 'progress' => $lifetimeXp, 'target' => 10000000, 'unit' => 'xp'],
        ];

        return collect($definitions)
            ->reject(fn (array $badge) => in_array($badge['name'], $earnedNames, true))
            ->sortBy(fn (array $badge) => $badge['target'] - min($badge['progress'], $badge['target']))
            ->values()
            ->all();
    }

    public function adminAnalyticsSnapshot(): array
    {
        $today = now()->toDateString();
        $recentVisitors = VisitorAnalytic::query()
            ->whereDate('visit_date', $today)
            ->orderByDesc('last_seen_at')
            ->take(20)
            ->get();

        return [
            'live_now' => $this->liveVisitorsQuery()
                ->orderByDesc('last_seen_at')
                ->take(20)
                ->get(),
            'live_now_count' => $this->liveVisitorsQuery()->count(),
            'recent_visitors' => $recentVisitors,
            'device_breakdown' => $this->breakdownForToday('device_type', ['Desktop', 'Mobile', 'Tablet', 'Bot', 'Other']),
            'browser_breakdown' => $this->breakdownForToday('browser_name'),
            'platform_breakdown' => $this->breakdownForToday('os_name'),
            'country_breakdown' => $this->breakdownForToday('country_code'),
        ];
    }

    protected function fingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip() ?? 'unknown',
            substr((string) $request->userAgent(), 0, 255),
        ]));
    }

    protected function normalizedPagePath(Request $request): string
    {
        return '/' . ltrim($request->path(), '/');
    }

    protected function shouldCountVisit(string $fingerprint, string $pagePath): bool
    {
        $cacheKey = 'public-visit:' . sha1($fingerprint . '|' . $pagePath);

        return Cache::add($cacheKey, now()->toIso8601String(), now()->addSeconds($this->visitDedupSeconds));
    }

    protected function metricTotals(string $startDate, string $endDate, ?int $sectionId = null): array
    {
        if (!$this->dailyMetricTableReady()) {
            return ['views' => 0, 'clicks' => 0];
        }

        $query = NewsItemDailyMetric::query()
            ->selectRaw('COALESCE(SUM(views_count), 0) as views, COALESCE(SUM(clicks_count), 0) as clicks')
            ->whereDate('metric_date', '>=', $startDate)
            ->whereDate('metric_date', '<=', $endDate);

        if ($sectionId) {
            $query->whereHas('newsItem', fn ($newsItemQuery) => $newsItemQuery->where('news_section_id', $sectionId));
        }

        $row = $query->first();

        return [
            'views' => (int) ($row->views ?? 0),
            'clicks' => (int) ($row->clicks ?? 0),
        ];
    }

    protected function dailyMetricTableReady(): bool
    {
        return DB::getSchemaBuilder()->hasTable('news_item_daily_metrics');
    }

    protected function conversionAssessment(float $rate): array
    {
        return match (true) {
            $rate >= 5.0 => [
                'label' => 'Excellent',
                'tone' => 'emerald',
                'message' => 'This conversion rate is strong and worth scaling.',
            ],
            $rate >= 3.0 => [
                'label' => 'Good',
                'tone' => 'sky',
                'message' => 'This is a healthy conversion level for news traffic.',
            ],
            $rate >= 1.0 => [
                'label' => 'Okay',
                'tone' => 'amber',
                'message' => 'Traffic is converting, but headline quality or placement can improve.',
            ],
            default => [
                'label' => 'Weak',
                'tone' => 'rose',
                'message' => 'This traffic is not converting well yet.',
            ],
        };
    }

    protected function liveVisitorsQuery()
    {
        return VisitorAnalytic::query()
            ->whereDate('visit_date', now()->toDateString())
            ->where('last_seen_at', '>=', now()->subMinutes($this->liveWindowMinutes));
    }

    protected function breakdownForToday(string $column, array $preferredOrder = []): array
    {
        $rows = VisitorAnalytic::query()
            ->selectRaw("COALESCE(NULLIF({$column}, ''), 'Unknown') as label, COUNT(*) as total")
            ->whereDate('visit_date', now()->toDateString())
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'total' => (int) $row->total,
            ])
            ->all();

        if ($preferredOrder === []) {
            return $rows;
        }

        $ordered = [];
        foreach ($preferredOrder as $label) {
            foreach ($rows as $index => $row) {
                if ($row['label'] === $label) {
                    $ordered[] = $row;
                    unset($rows[$index]);
                }
            }
        }

        return array_values([...$ordered, ...$rows]);
    }

    protected function parseRequestContext(Request $request): array
    {
        $userAgent = substr((string) $request->userAgent(), 0, 1000);

        return [
            'device_type' => $this->detectDeviceType($userAgent),
            'browser_name' => $this->detectBrowserName($userAgent),
            'os_name' => $this->detectOsName($userAgent),
            'user_agent' => $userAgent ?: null,
        ];
    }

    protected function detectDeviceType(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        if ($agent === '') {
            return 'Unknown';
        }

        if (str_contains($agent, 'bot') || str_contains($agent, 'crawl') || str_contains($agent, 'spider')) {
            return 'Bot';
        }

        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'Tablet';
        }

        if (str_contains($agent, 'mobile') || str_contains($agent, 'iphone') || str_contains($agent, 'android')) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    protected function detectBrowserName(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'edg/') => 'Edge',
            str_contains($agent, 'opr/') || str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'chrome/') && !str_contains($agent, 'edg/') => 'Chrome',
            str_contains($agent, 'firefox/') => 'Firefox',
            str_contains($agent, 'safari/') && !str_contains($agent, 'chrome/') => 'Safari',
            str_contains($agent, 'samsungbrowser/') => 'Samsung Internet',
            str_contains($agent, 'curl/') => 'cURL',
            $agent === '' => 'Unknown',
            default => 'Other',
        };
    }

    protected function detectOsName(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'iphone') || str_contains($agent, 'ipad') || str_contains($agent, 'ios') => 'iOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'mac os x') || str_contains($agent, 'macintosh') => 'macOS',
            str_contains($agent, 'linux') => 'Linux',
            str_contains($agent, 'cros') => 'ChromeOS',
            $agent === '' => 'Unknown',
            default => 'Other',
        };
    }
}
