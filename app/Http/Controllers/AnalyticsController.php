<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    /**
     * Executive and general visitor analytics dashboard.
     */
    public function index(Request $request)
    {
        $period = $request->input('period', '30d');
        [$start, $end] = $this->getDateRange($period);

        // Fetch daily general aggregates
        $dailyAggregates = DB::table('analytics_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        // Calculate period summaries
        $summary = [
            'total_views' => (int) $dailyAggregates->sum('total_views'),
            'unique_visitors' => $dailyAggregates->count() > 0 ? (int) $dailyAggregates->max('unique_visitors') : 0, // max concurrent unique estimate
            'total_sessions' => (int) $dailyAggregates->sum('total_sessions'),
            'bounce_rate' => $dailyAggregates->count() > 0 ? round($dailyAggregates->avg('bounce_rate'), 1) : 0.0,
            'avg_duration' => $dailyAggregates->count() > 0 ? (int) $dailyAggregates->avg('avg_duration_seconds') : 0,
            'human_views' => (int) $dailyAggregates->sum('human_views'),
            'bot_views' => (int) $dailyAggregates->sum('bot_views'),
        ];

        // Breakdowns: Top Referrers
        $referrers = DB::table('analytics_referrers')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->select('referrer_host', DB::raw('sum(views_count) as views'))
            ->groupBy('referrer_host')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        // Breakdowns: Top Devices
        $devices = DB::table('analytics_devices')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->select('device_type', DB::raw('sum(views_count) as views'))
            ->groupBy('device_type')
            ->orderByDesc('views')
            ->get();

        // Breakdowns: Top Browsers
        $browsers = DB::table('analytics_browsers')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->select('browser_name', DB::raw('sum(views_count) as views'))
            ->groupBy('browser_name')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        return view('admin.analytics.index', compact('dailyAggregates', 'summary', 'referrers', 'devices', 'browsers', 'period'));
    }

    /**
     * Real-time dashboard view.
     */
    public function realtime()
    {
        return view('admin.analytics.realtime');
    }

    /**
     * Real-time statistics JSON endpoint (polled via AJAX).
     */
    public function realtimeData(AnalyticsStorageService $storage)
    {
        $now = time();

        // Get counts
        $onlineVisitors = $storage->getActiveCount('analytics:active_users', $now - 300); // last 5 minutes
        $activeSessions = $storage->getActiveCount('analytics:active_sessions', $now - 1800); // last 30 minutes
        
        // Live items lists
        $topPages = $storage->getTopItems('analytics:live_top_pages', 10);
        $topCountries = $storage->getTopItems('analytics:live_top_countries', 5);
        $topDevices = $storage->getTopItems('analytics:live_top_devices', 3);
        $topBrowsers = $storage->getTopItems('analytics:live_top_browsers', 3);
        $topBots = $storage->getTopItems('analytics:live_top_bots', 5);
        $recentVisits = $storage->getRecentVisits('analytics:recent_visits', 20);

        return response()->json([
            'online_visitors' => $onlineVisitors,
            'active_sessions' => $activeSessions,
            'top_pages' => $topPages,
            'top_countries' => $topCountries,
            'top_devices' => $topDevices,
            'top_browsers' => $topBrowsers,
            'top_bots' => $topBots,
            'recent_visits' => $recentVisits,
            'synced_at' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Content module-specific analytics (Gold, Lottery, Jobs).
     */
    public function modules(Request $request)
    {
        $period = $request->input('period', '30d');
        [$start, $end] = $this->getDateRange($period);
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        // 1. Lottery Analytics
        $lotteryStats = DB::table('analytics_lottery')
            ->whereBetween('date', [$startStr, $endStr])
            ->leftJoin('lottery_results', 'analytics_lottery.lottery_result_id', '=', 'lottery_results.id')
            ->select(
                'lottery_results.lottery_name',
                'lottery_results.draw_number',
                DB::raw('sum(views_count) as views'),
                DB::raw('sum(pdf_downloads) as downloads'),
                DB::raw('sum(official_clicks) as official_clicks')
            )
            ->groupBy('lottery_results.lottery_name', 'lottery_results.draw_number')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $lotteryTotals = [
            'views' => DB::table('analytics_lottery')->whereBetween('date', [$startStr, $endStr])->sum('views_count'),
            'pdf_downloads' => DB::table('analytics_lottery')->whereBetween('date', [$startStr, $endStr])->sum('pdf_downloads'),
            'official_clicks' => DB::table('analytics_lottery')->whereBetween('date', [$startStr, $endStr])->sum('official_clicks'),
        ];

        // 2. Gold Analytics
        $goldStats = DB::table('analytics_gold')
            ->whereBetween('date', [$startStr, $endStr])
            ->select('city', DB::raw('sum(views_count) as views'), DB::raw('sum(calculator_usage) as calculator_uses'))
            ->groupBy('city')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $goldTotals = [
            'views' => DB::table('analytics_gold')->whereBetween('date', [$startStr, $endStr])->sum('views_count'),
            'calculator' => DB::table('analytics_gold')->whereBetween('date', [$startStr, $endStr])->sum('calculator_usage'),
        ];

        // 3. Job Board Analytics
        $jobStats = DB::table('analytics_jobs')
            ->whereBetween('date', [$startStr, $endStr])
            ->leftJoin('job_posts', 'analytics_jobs.job_post_id', '=', 'job_posts.id')
            ->select(
                'job_posts.title',
                'job_posts.company',
                DB::raw('sum(analytics_jobs.views_count) as views'),
                DB::raw('sum(analytics_jobs.apply_clicks) as applications')
            )
            ->groupBy('job_posts.title', 'job_posts.company')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $jobTotals = [
            'views' => DB::table('analytics_jobs')->whereBetween('date', [$startStr, $endStr])->sum('views_count'),
            'clicks' => DB::table('analytics_jobs')->whereBetween('date', [$startStr, $endStr])->sum('apply_clicks'),
        ];

        return view('admin.analytics.modules', compact(
            'lotteryStats', 'lotteryTotals',
            'goldStats', 'goldTotals',
            'jobStats', 'jobTotals',
            'period'
        ));
    }

    /**
     * Crawling bot analytics view.
     */
    public function bots(Request $request)
    {
        $period = $request->input('period', '30d');
        [$start, $end] = $this->getDateRange($period);

        $bots = DB::table('analytics_bots')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->select('bot_type', DB::raw('sum(requests_count) as hits'), DB::raw('max(updated_at) as last_seen'))
            ->groupBy('bot_type')
            ->orderByDesc('hits')
            ->get();

        return view('admin.analytics.bots', compact('bots', 'period'));
    }

    /**
     * Diagnostics and response performance monitoring.
     */
    public function performance(Request $request)
    {
        $period = $request->input('period', '30d');
        [$start, $end] = $this->getDateRange($period);
        $startStr = $start->toDateTimeString();
        $endStr = $end->toDateTimeString();

        // Average response speeds from logs
        $avgResponseTime = (int) DB::table('analytics_page_views')
            ->whereBetween('created_at', [$startStr, $endStr])
            ->avg('response_time_ms');

        // Slowest paths
        $slowPages = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$startStr, $endStr])
            ->select('page_path', DB::raw('avg(response_time_ms) as avg_time'), DB::raw('count(*) as hits'))
            ->groupBy('page_path')
            ->orderByDesc('avg_time')
            ->limit(10)
            ->get();

        // Application errors breakdown
        $errors = DB::table('analytics_errors')
            ->whereBetween('date_hour', [$startStr, $endStr])
            ->select('error_type', 'page_path', DB::raw('sum(occurrences_count) as count'), DB::raw('max(updated_at) as last_seen'))
            ->groupBy('error_type', 'page_path')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // System diagnostics
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskUsagePercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

        $diagnostics = [
            'avg_response_time' => $avgResponseTime ?: 45, // ms fallback
            'disk_usage' => $diskUsagePercent,
            'disk_free_gb' => round($diskFree / (1024 * 1024 * 1024), 2),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        return view('admin.analytics.performance', compact('slowPages', 'errors', 'diagnostics', 'period'));
    }

    /**
     * Helper to resolve Date Carbon range based on period keys.
     */
    protected function getDateRange(string $period): array
    {
        $end = now()->endOfDay();
        $start = match ($period) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            '7d' => now()->subDays(7)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        return [$start, $end];
    }
}
