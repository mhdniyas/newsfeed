<?php

namespace App\Jobs;

use App\Services\AgentParser;
use App\Services\AnalyticsStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $pagePath;
    protected string $pageType;
    protected ?int $modelId;
    protected ?string $sessionId;
    protected string $ipAddress;
    protected string $userAgent;
    protected ?string $referrer;
    protected string $countryCode;
    protected ?int $responseTimeMs;
    protected int $timestamp;
    protected ?string $searchQuery;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->pagePath = $payload['page_path'];
        $this->pageType = $payload['page_type'];
        $this->modelId = $payload['model_id'] ?? null;
        $this->sessionId = $payload['session_id'] ?? null;
        $this->ipAddress = $payload['ip_address'];
        $this->userAgent = $payload['user_agent'];
        $this->referrer = $payload['referrer'] ?? null;
        $this->countryCode = $payload['country_code'];
        $this->responseTimeMs = $payload['response_time_ms'] ?? null;
        $this->timestamp = $payload['timestamp'];
        $this->searchQuery = $payload['search_query'] ?? null;
    }

    /**
     * Execute the job.
     */
    public function handle(AnalyticsStorageService $storage): void
    {
        $createdAt = Carbon::createFromTimestamp($this->timestamp);
        
        // 1. Generate visitor fingerprint (GDPR compliant hash)
        $fingerprint = md5($this->ipAddress . '|' . $this->userAgent);

        // 2. Parse User-Agent using custom optimized AgentParser
        $agent = AgentParser::parse($this->userAgent);

        // 3. Resolve referrer host
        $referrerHost = null;
        if ($this->referrer) {
            $parsed = parse_url($this->referrer);
            if (isset($parsed['host'])) {
                $referrerHost = strtolower($parsed['host']);
                // Clean www. prefix
                if (str_starts_with($referrerHost, 'www.')) {
                    $referrerHost = substr($referrerHost, 4);
                }
            }
        }

        // 4. Update Database: analytics_visitors
        $visitor = DB::table('analytics_visitors')->where('visitor_fingerprint', $fingerprint)->first();
        if (!$visitor) {
            DB::table('analytics_visitors')->insert([
                'visitor_fingerprint' => $fingerprint,
                'first_seen_at' => $createdAt,
                'last_seen_at' => $createdAt,
                'views_count' => 1,
                'sessions_count' => $this->sessionId ? 1 : 0,
                'is_human' => !$agent['is_bot'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('analytics_visitors')->where('visitor_fingerprint', $fingerprint)->update([
                'last_seen_at' => $createdAt,
                'views_count' => $visitor->views_count + 1,
                'updated_at' => now(),
            ]);
        }

        // 5. Update Database: analytics_sessions
        if ($this->sessionId) {
            $session = DB::table('analytics_sessions')->where('session_id', $this->sessionId)->first();
            if (!$session) {
                DB::table('analytics_sessions')->insert([
                    'session_id' => $this->sessionId,
                    'visitor_fingerprint' => $fingerprint,
                    'duration_seconds' => 0,
                    'pages_count' => 1,
                    'bounce_rate' => true,
                    'country_code' => $this->countryCode,
                    'device_type' => $agent['device_type'],
                    'browser_name' => $agent['browser_name'],
                    'os_name' => $agent['os_name'],
                    'is_human' => !$agent['is_bot'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Increment session count on visitor since it's a new session
                DB::table('analytics_visitors')->where('visitor_fingerprint', $fingerprint)->increment('sessions_count');
            } else {
                $sessionStart = Carbon::parse($session->created_at)->getTimestamp();
                $duration = max(0, $createdAt->getTimestamp() - $sessionStart);
                DB::table('analytics_sessions')->where('session_id', $this->sessionId)->update([
                    'duration_seconds' => $duration,
                    'pages_count' => $session->pages_count + 1,
                    'bounce_rate' => false,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        // 6. Log raw page view in database
        DB::table('analytics_page_views')->insert([
            'session_id' => $this->sessionId,
            'visitor_fingerprint' => $fingerprint,
            'page_path' => $this->pagePath,
            'page_type' => $this->pageType,
            'model_id' => $this->modelId,
            'referrer_host' => $referrerHost,
            'device_type' => $agent['device_type'],
            'browser_name' => $agent['browser_name'],
            'os_name' => $agent['os_name'],
            'country_code' => $this->countryCode,
            'is_bot' => $agent['is_bot'],
            'bot_type' => $agent['bot_type'],
            'user_agent' => $this->userAgent,
            'response_time_ms' => $this->responseTimeMs,
            'created_at' => $createdAt,
        ]);

        // 7. Track search keyword if applicable
        if ($this->pageType === 'search' && $this->searchQuery) {
            $keyword = trim(strtolower($this->searchQuery));
            if ($keyword !== '') {
                // We'll update the live top searches in Redis, and flush to MySQL in aggregate steps
                $storage->recordTopItem('analytics:live_top_searches', $keyword);
            }
        }

        // 8. Track Live Redis metrics (Real-time Dashboard datasource)
        $storage->activeRegister('analytics:active_users', $fingerprint, $this->timestamp);
        if ($this->sessionId) {
            $storage->activeRegister('analytics:active_sessions', $this->sessionId, $this->timestamp);
        }

        $storage->increment('analytics:live_views_count');
        $storage->recordTopItem('analytics:live_top_pages', $this->pagePath);
        $storage->recordTopItem('analytics:live_top_countries', $this->countryCode);
        $storage->recordTopItem('analytics:live_top_devices', $agent['device_type']);
        $storage->recordTopItem('analytics:live_top_browsers', $agent['browser_name']);

        if ($agent['is_bot']) {
            $storage->recordTopItem('analytics:live_top_bots', $agent['bot_type'] ?: 'Unknown Bot');
        }

        // Article section/topic specific top items
        if ($this->pageType === 'news' && $this->modelId) {
            $newsMeta = DB::table('news_items')
                ->leftJoin('news_sections', 'news_items.news_section_id', '=', 'news_sections.id')
                ->leftJoin('news_topics', 'news_items.news_topic_id', '=', 'news_topics.id')
                ->where('news_items.id', $this->modelId)
                ->select('news_sections.name as section_name', 'news_topics.name as topic_name')
                ->first();

            if ($newsMeta) {
                if ($newsMeta->section_name) {
                    $storage->recordTopItem('analytics:live_top_sections', $newsMeta->section_name);
                }
                if ($newsMeta->topic_name) {
                    $storage->recordTopItem('analytics:live_top_topics', $newsMeta->topic_name);
                }
            }
        }

        // 9. Push recent visit logs to list
        $storage->pushRecentVisit('analytics:recent_visits', [
            'fingerprint' => substr($fingerprint, 0, 8) . '...',
            'session_id' => $this->sessionId ? substr($this->sessionId, 0, 6) . '...' : 'N/A',
            'page_path' => $this->pagePath,
            'page_type' => $this->pageType,
            'country_code' => $this->countryCode,
            'device_type' => $agent['device_type'],
            'browser_name' => $agent['browser_name'],
            'os_name' => $agent['os_name'],
            'is_bot' => $agent['is_bot'],
            'bot_type' => $agent['bot_type'],
            'response_time_ms' => $this->responseTimeMs,
            'time' => $createdAt->format('H:i:s'),
        ], 30);
    }
}
