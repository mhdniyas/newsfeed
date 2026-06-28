<?php

namespace Tests\Feature;

use App\Jobs\ProcessAnalyticsJob;
use App\Services\AnalyticsStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_dispatches_analytics_queued_job(): void
    {
        Bus::fake();

        // Perform a public request
        $response = $this->get('/');

        $response->assertStatus(200);

        // Assert the job was dispatched to the queue
        Bus::assertDispatched(ProcessAnalyticsJob::class);
    }

    public function test_queued_job_logs_to_database_and_redis(): void
    {
        $payload = [
            'page_path' => '/test-route',
            'page_type' => 'news',
            'model_id' => 99,
            'session_id' => 'session_xyz123',
            'ip_address' => '1.1.1.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'referrer' => 'https://google.com',
            'country_code' => 'US',
            'response_time_ms' => 120,
            'timestamp' => time(),
            'search_query' => null,
        ];

        // Resolve the storage service
        $storage = app(AnalyticsStorageService::class);
        $storage->delete('analytics:active_users');
        $storage->delete('analytics:live_views_count');

        // Dispatch and execute the job directly
        $job = new ProcessAnalyticsJob($payload);
        $job->handle($storage);

        // 1. Assert database records were logged
        $this->assertDatabaseHas('analytics_page_views', [
            'session_id' => 'session_xyz123',
            'page_path' => '/test-route',
            'page_type' => 'news',
            'model_id' => 99,
            'referrer_host' => 'google.com',
            'device_type' => 'desktop',
            'browser_name' => 'Chrome',
            'os_name' => 'macOS',
            'country_code' => 'US',
            'is_bot' => false,
        ]);

        $this->assertDatabaseHas('analytics_visitors', [
            'is_human' => true,
            'views_count' => 1,
        ]);

        $this->assertDatabaseHas('analytics_sessions', [
            'session_id' => 'session_xyz123',
            'device_type' => 'desktop',
            'browser_name' => 'Chrome',
            'os_name' => 'macOS',
            'is_human' => true,
        ]);

        // 2. Assert Redis/Cache metrics were updated
        $this->assertGreaterThanOrEqual(1, $storage->getActiveCount('analytics:active_users', time() - 300));
        $this->assertGreaterThanOrEqual(1, $storage->getCount('analytics:live_views_count'));
    }

    public function test_aggregation_command_consolidates_metrics(): void
    {
        $today = Carbon::today();
        $dateStr = $today->toDateString();

        // 1. Insert mock raw logs in analytics_page_views
        DB::table('analytics_page_views')->insert([
            [
                'session_id' => 's1',
                'visitor_fingerprint' => 'f1',
                'page_path' => '/world-cup-news/article/1',
                'page_type' => 'news',
                'model_id' => 1,
                'referrer_host' => 'facebook.com',
                'device_type' => 'mobile',
                'browser_name' => 'Chrome',
                'os_name' => 'Android',
                'country_code' => 'IN',
                'is_bot' => false,
                'bot_type' => null,
                'user_agent' => 'Mozilla/5.0 (Android; Mobile)',
                'response_time_ms' => 45,
                'created_at' => $today->copy()->hour(10)->minute(30),
            ],
            [
                'session_id' => 's1',
                'visitor_fingerprint' => 'f1',
                'page_path' => '/world-cup-news/article/2',
                'page_type' => 'news',
                'model_id' => 2,
                'referrer_host' => 'facebook.com',
                'device_type' => 'mobile',
                'browser_name' => 'Chrome',
                'os_name' => 'Android',
                'country_code' => 'IN',
                'is_bot' => false,
                'bot_type' => null,
                'user_agent' => 'Mozilla/5.0 (Android; Mobile)',
                'response_time_ms' => 55,
                'created_at' => $today->copy()->hour(10)->minute(45),
            ],
            // Bot request
            [
                'session_id' => null,
                'visitor_fingerprint' => 'f2',
                'page_path' => '/',
                'page_type' => 'homepage',
                'model_id' => null,
                'referrer_host' => null,
                'device_type' => 'desktop',
                'browser_name' => 'Bot',
                'os_name' => 'Bot',
                'country_code' => 'US',
                'is_bot' => true,
                'bot_type' => 'GPTBot',
                'user_agent' => 'Mozilla/5.0 (compatible; GPTBot/2.0)',
                'response_time_ms' => 10,
                'created_at' => $today->copy()->hour(11)->minute(0),
            ]
        ]);

        // Insert matching mock active sessions
        DB::table('analytics_sessions')->insert([
            'session_id' => 's1',
            'visitor_fingerprint' => 'f1',
            'duration_seconds' => 120,
            'pages_count' => 2,
            'bounce_rate' => false,
            'country_code' => 'IN',
            'device_type' => 'mobile',
            'browser_name' => 'Chrome',
            'os_name' => 'Android',
            'is_human' => true,
            'created_at' => $today->copy()->hour(10)->minute(30),
            'updated_at' => $today->copy()->hour(10)->minute(45),
        ]);

        // 2. Execute the aggregation Artisan command
        $this->artisan('analytics:aggregate', ['--date' => $dateStr])->assertExitCode(0);

        // 3. Assert roll-ups exist in analytics_daily
        $this->assertDatabaseHas('analytics_daily', [
            'date' => $dateStr,
            'total_views' => 3,
            'unique_visitors' => 2,
            'total_sessions' => 1,
            'bounce_rate' => 0.0, // pages_count = 2, so bounce_rate should be 0
            'avg_duration_seconds' => 120,
            'human_views' => 2,
            'bot_views' => 1,
        ]);

        // 4. Assert hourly aggregation
        $this->assertDatabaseHas('analytics_hourly', [
            'date_hour' => $today->copy()->hour(10)->startOfHour(),
            'total_views' => 2,
            'human_views' => 2,
            'bot_views' => 0,
        ]);

        // 5. Assert device dimensions
        $this->assertDatabaseHas('analytics_devices', [
            'device_type' => 'mobile',
            'date' => $dateStr,
            'views_count' => 2,
        ]);

        // 6. Assert crawler bot dimensions
        $this->assertDatabaseHas('analytics_bots', [
            'bot_type' => 'GPTBot',
            'date' => $dateStr,
            'requests_count' => 1,
        ]);
    }

    public function test_realtime_json_endpoint_auth(): void
    {
        // Unauthenticated returns redirect to login
        $this->get(route('admin.analytics.realtime.data'))->assertRedirect(route('admin.login'));

        // Authenticated admin succeeds
        $response = $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.analytics.realtime.data'));

        $response->assertOk();
        $response->assertJsonStructure([
            'online_visitors',
            'active_sessions',
            'top_pages',
            'top_countries',
            'top_devices',
            'top_browsers',
            'top_bots',
            'recent_visits',
            'synced_at',
        ]);
    }
}
