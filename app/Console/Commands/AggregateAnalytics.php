<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:aggregate {--date= : The date to aggregate (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Aggregate raw page views and session logs into hourly and daily summaries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateInput = $this->option('date');
        
        if ($dateInput) {
            $dates = [Carbon::parse($dateInput)];
        } else {
            // Aggregate today and yesterday to ensure late-arriving queues are captured
            $dates = [
                Carbon::today(),
                Carbon::yesterday()
            ];
        }

        foreach ($dates as $carbonDate) {
            $dateStr = $carbonDate->toDateString();
            $this->info("Aggregating analytics for date: {$dateStr}");

            $this->aggregateDailyGeneral($carbonDate);
            $this->aggregateHourlyGeneral($carbonDate);
            $this->aggregateContentModules($carbonDate);
            $this->aggregateDimensions($carbonDate);
        }

        // Clean up raw logs older than 30 days to save disk space
        $retentionDate = now()->subDays(30);
        $deleted = DB::table('analytics_page_views')->where('created_at', '<', $retentionDate)->delete();
        $this->info("Cleaned up {$deleted} raw page view logs older than 30 days.");

        return 0;
    }

    /**
     * Aggregate daily general summary metrics.
     */
    protected function aggregateDailyGeneral(Carbon $date): void
    {
        $dateStr = $date->toDateString();

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $totalViews = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $uniqueVisitors = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->distinct('visitor_fingerprint')
            ->count('visitor_fingerprint');

        $humanViews = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->where('is_bot', false)
            ->count();

        $botViews = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->where('is_bot', true)
            ->count();

        // Calculate session statistics
        $sessions = DB::table('analytics_sessions')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalSessions = $sessions->count();
        $bounces = $sessions->where('pages_count', 1)->count();
        $bounceRate = $totalSessions > 0 ? ($bounces / $totalSessions) * 100 : 0.0;
        $avgDuration = $totalSessions > 0 ? (int) $sessions->avg('duration_seconds') : 0;

        DB::table('analytics_daily')->updateOrInsert(
            ['date' => $dateStr],
            [
                'total_views' => $totalViews,
                'unique_visitors' => $uniqueVisitors,
                'total_sessions' => $totalSessions,
                'bounce_rate' => $bounceRate,
                'avg_duration_seconds' => $avgDuration,
                'human_views' => $humanViews,
                'bot_views' => $botViews,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Aggregate hourly general metrics.
     */
    protected function aggregateHourlyGeneral(Carbon $date): void
    {
        for ($hourNum = 0; $hourNum < 24; $hourNum++) {
            $hourStart = $date->copy()->startOfDay()->addHours($hourNum);
            $hourEnd = $hourStart->copy()->endOfHour();

            $totalViews = DB::table('analytics_page_views')
                ->whereBetween('created_at', [$hourStart, $hourEnd])
                ->count();

            // Skip hours with no traffic to keep tables small
            if ($totalViews === 0) {
                continue;
            }

            $uniqueVisitors = DB::table('analytics_page_views')
                ->whereBetween('created_at', [$hourStart, $hourEnd])
                ->distinct('visitor_fingerprint')
                ->count('visitor_fingerprint');

            $humanViews = DB::table('analytics_page_views')
                ->whereBetween('created_at', [$hourStart, $hourEnd])
                ->where('is_bot', false)
                ->count();

            $botViews = DB::table('analytics_page_views')
                ->whereBetween('created_at', [$hourStart, $hourEnd])
                ->where('is_bot', true)
                ->count();

            DB::table('analytics_hourly')->updateOrInsert(
                ['date_hour' => $hourStart],
                [
                    'total_views' => $totalViews,
                    'unique_visitors' => $uniqueVisitors,
                    'human_views' => $humanViews,
                    'bot_views' => $botViews,
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Aggregate content modules (Articles, Sections, Topics, Lottery, Gold, Jobs).
     */
    protected function aggregateContentModules(Carbon $date): void
    {
        $dateStr = $date->toDateString();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        // 1. Articles
        $articleRows = DB::table('analytics_page_views')
            ->where('page_type', 'news')
            ->whereNotNull('model_id')
            ->whereBetween('created_at', [$start, $end])
            ->select('model_id', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_fingerprint) as uniques'), DB::raw('avg(response_time_ms) as avg_res'))
            ->groupBy('model_id')
            ->get();

        foreach ($articleRows as $row) {
            DB::table('analytics_articles')->updateOrInsert(
                ['article_id' => $row->model_id, 'date' => $dateStr],
                [
                    'views_count' => $row->views,
                    'unique_visitors' => $row->uniques,
                    // Reading time simulated or resolved (e.g. from session updates)
                    'reading_time_seconds' => (int)($row->views * 45), // average estimated 45 seconds per read
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Sections
        $sectionRows = DB::table('analytics_page_views')
            ->where('page_type', 'news')
            ->whereNotNull('model_id')
            ->whereBetween('analytics_page_views.created_at', [$start, $end])
            ->join('news_items', 'analytics_page_views.model_id', '=', 'news_items.id')
            ->select('news_items.news_section_id', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_fingerprint) as uniques'))
            ->groupBy('news_items.news_section_id')
            ->get();

        foreach ($sectionRows as $row) {
            if ($row->news_section_id) {
                DB::table('analytics_sections')->updateOrInsert(
                    ['section_id' => $row->news_section_id, 'date' => $dateStr],
                    [
                        'views_count' => $row->views,
                        'unique_visitors' => $row->uniques,
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 3. Topics
        $topicRows = DB::table('analytics_page_views')
            ->where('page_type', 'news')
            ->whereNotNull('model_id')
            ->whereBetween('analytics_page_views.created_at', [$start, $end])
            ->join('news_items', 'analytics_page_views.model_id', '=', 'news_items.id')
            ->select('news_items.news_topic_id', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_fingerprint) as uniques'))
            ->groupBy('news_items.news_topic_id')
            ->get();

        foreach ($topicRows as $row) {
            if ($row->news_topic_id) {
                DB::table('analytics_topics')->updateOrInsert(
                    ['topic_id' => $row->news_topic_id, 'date' => $dateStr],
                    [
                        'views_count' => $row->views,
                        'unique_visitors' => $row->uniques,
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 4. Lottery
        $lotteryRows = DB::table('analytics_page_views')
            ->where('page_type', 'lottery')
            ->whereBetween('created_at', [$start, $end])
            ->select('model_id', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_fingerprint) as uniques'))
            ->groupBy('model_id')
            ->get();

        foreach ($lotteryRows as $row) {
            DB::table('analytics_lottery')->updateOrInsert(
                ['lottery_result_id' => $row->model_id, 'date' => $dateStr],
                [
                    'views_count' => $row->views,
                    'unique_visitors' => $row->uniques,
                    'updated_at' => now(),
                ]
            );
        }

        // 5. Gold Rates (Parsed from path or model_id)
        $goldRows = DB::table('analytics_page_views')
            ->where('page_type', 'gold')
            ->whereBetween('created_at', [$start, $end])
            ->select('page_path', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_fingerprint) as uniques'))
            ->groupBy('page_path')
            ->get();

        foreach ($goldRows as $row) {
            // Extract city from path (e.g. /gold-rate/kochi)
            $city = 'Default';
            $parts = explode('/', trim($row->page_path, '/'));
            if (count($parts) >= 2 && $parts[0] === 'gold-rate') {
                $city = urldecode($parts[1]);
            }
            
            DB::table('analytics_gold')->updateOrInsert(
                ['city' => $city, 'date' => $dateStr],
                [
                    'views_count' => $row->views,
                    'unique_visitors' => $row->uniques,
                    'updated_at' => now(),
                ]
            );
        }

        // 6. Jobs
        $jobRows = DB::table('analytics_page_views')
            ->where('page_type', 'jobs')
            ->whereBetween('created_at', [$start, $end])
            ->select('model_id', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_fingerprint) as uniques'))
            ->groupBy('model_id')
            ->get();

        foreach ($jobRows as $row) {
            DB::table('analytics_jobs')->updateOrInsert(
                ['job_post_id' => $row->model_id, 'date' => $dateStr],
                [
                    'views_count' => $row->views,
                    'unique_visitors' => $row->uniques,
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Aggregate technical dimensions (referrers, devices, browsers, bots, search, errors).
     */
    protected function aggregateDimensions(Carbon $date): void
    {
        $dateStr = $date->toDateString();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        // 1. Referrers
        $refRows = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('referrer_host')
            ->select('referrer_host', DB::raw('count(*) as views'))
            ->groupBy('referrer_host')
            ->get();

        foreach ($refRows as $row) {
            DB::table('analytics_referrers')->updateOrInsert(
                ['referrer_host' => $row->referrer_host, 'date' => $dateStr],
                [
                    'views_count' => $row->views,
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Devices
        $devRows = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('device_type')
            ->select('device_type', DB::raw('count(*) as views'))
            ->groupBy('device_type')
            ->get();

        foreach ($devRows as $row) {
            DB::table('analytics_devices')->updateOrInsert(
                ['device_type' => $row->device_type, 'date' => $dateStr],
                [
                    'views_count' => $row->views,
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Browsers
        $browRows = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('browser_name')
            ->select('browser_name', DB::raw('count(*) as views'))
            ->groupBy('browser_name')
            ->get();

        foreach ($browRows as $row) {
            DB::table('analytics_browsers')->updateOrInsert(
                ['browser_name' => $row->browser_name, 'date' => $dateStr],
                [
                    'views_count' => $row->views,
                    'updated_at' => now(),
                ]
            );
        }

        // 4. Bots
        $botRows = DB::table('analytics_page_views')
            ->whereBetween('created_at', [$start, $end])
            ->where('is_bot', true)
            ->whereNotNull('bot_type')
            ->select('bot_type', 'user_agent', DB::raw('count(*) as views'))
            ->groupBy('bot_type', 'user_agent')
            ->get();

        foreach ($botRows as $row) {
            DB::table('analytics_bots')->updateOrInsert(
                ['bot_type' => $row->bot_type, 'date' => $dateStr],
                [
                    'user_agent' => $row->user_agent,
                    'requests_count' => $row->views,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
