<?php

namespace App\Services;

use App\Jobs\RunTrendSyncCycle;
use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\Setting;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Carbon;

class AutomaticTrendSyncService
{
    public const SYNC_INTERVAL_MINUTES = 5;
    public const ARTICLES_PER_COUNTRY = 10;

    public function maybeTriggerDueSync(string $message = 'Automatic fallback trend sync triggered from web request.'): bool
    {
        $state = $this->syncState();

        if (in_array($state['status'], ['queued', 'running'], true) && !$state['is_stale']) {
            return false;
        }

        $intervalMinutes = (int) ($state['fetch_stats']['interval_minutes'] ?? self::SYNC_INTERVAL_MINUTES);
        $currentSlot = $this->currentSlotStart($intervalMinutes);
        $graceWindow = $currentSlot->copy()->addSeconds(20);

        if (now()->lt($graceWindow)) {
            return false;
        }

        $lastTriggeredSlot = Setting::get('trend_sync_auto_slot_triggered_at');

        if ($lastTriggeredSlot === $currentSlot->toIso8601String()) {
            return false;
        }

        $lastSuccessAt = Setting::get('trend_sync_last_success_at');
        if ($lastSuccessAt) {
            try {
                if (Carbon::parse($lastSuccessAt)->gte($currentSlot)) {
                    Setting::set('trend_sync_auto_slot_triggered_at', $currentSlot->toIso8601String());
                    return false;
                }
            } catch (\Throwable) {
            }
        }

        if ($state['is_stale']) {
            $this->stopTrackedSyncProcess('Stale trend sync state was cleared before automatic fallback restart.');
        }

        [$started] = $this->launchQueuedSync($message);

        if ($started) {
            Setting::set('trend_sync_auto_slot_triggered_at', $currentSlot->toIso8601String());
        }

        return $started;
    }

    public function syncState(): array
    {
        $status = Setting::get('trend_sync_status', 'idle');
        $requestedAt = Setting::get('trend_sync_requested_at');
        $startedAt = Setting::get('trend_sync_started_at');
        $finishedAt = Setting::get('trend_sync_finished_at');
        $isStale = $this->syncIsStale($status, $requestedAt, $startedAt, $finishedAt);

        return [
            'status' => $isStale ? 'stalled' : $status,
            'raw_status' => $status,
            'is_stale' => $isStale,
            'requested_at' => $requestedAt,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'last_output' => Setting::get('trend_sync_last_output'),
            'meta' => json_decode(Setting::get('trend_sync_meta', '{}') ?: '{}', true) ?: [],
            'log' => json_decode(Setting::get('trend_sync_log', '[]') ?: '[]', true) ?: [],
            'fetch_stats' => $this->fetchStats(),
        ];
    }

    public function fetchStats(): array
    {
        $section = NewsSection::query()->where('slug', 'google-trends')->first();
        $countries = count(TrendingNewsService::COUNTRY_CONFIG);
        $newsTotal = $section ? NewsItem::query()->where('news_section_id', $section->id)->count() : 0;

        return [
            'total_runs' => (int) Setting::get('trend_sync_total_runs', '0'),
            'last_success_at' => Setting::get('trend_sync_last_success_at'),
            'interval_minutes' => self::SYNC_INTERVAL_MINUTES,
            'country_count' => $countries,
            'articles_per_country' => self::ARTICLES_PER_COUNTRY,
            'article_limit' => $countries * self::ARTICLES_PER_COUNTRY,
            'next_scheduled_at' => $this->nextScheduledFetchAt(self::SYNC_INTERVAL_MINUTES)?->toIso8601String(),
            'news_total' => $newsTotal,
            'content_health' => 'monitoring',
            'content_health_label' => 'Monitoring',
            'content_health_message' => 'Watching the dedicated trends queue and country-by-country save counts.',
        ];
    }

    public function launchQueuedSync(string $requestedMessage): array
    {
        $this->releaseSyncUniqueLock();

        Setting::set('trend_sync_status', 'queued');
        Setting::set('trend_sync_requested_at', now()->toIso8601String());
        Setting::set('trend_sync_started_at', null);
        Setting::set('trend_sync_finished_at', null);
        Setting::set('trend_sync_last_output', null);
        Setting::set('trend_sync_process_id', null);
        Setting::set('trend_sync_meta', json_encode([
            'progress' => 0,
            'stage' => 'Queueing trend sync job',
            'processed_topics' => 0,
            'total_topics' => 0,
            'stats' => [
                'new_articles' => 0,
                'skipped_duplicates' => 0,
                'images_recovered' => 0,
            ],
        ]));
        Setting::set('trend_sync_log', json_encode([
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => $requestedMessage,
            ],
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => 'Dispatching trend sync job to the background queue.',
            ],
        ]));

        if (!app()->environment('testing')) {
            RunTrendSyncCycle::dispatch($requestedMessage);
        }

        $pid = $this->startDetachedQueueWorker();

        if ($pid === null) {
            Setting::set('trend_sync_status', 'failed');
            Setting::set('trend_sync_finished_at', now()->toIso8601String());
            Setting::set('trend_sync_last_output', 'Unable to launch detached queue worker.');

            return [false, 'Could not start the detached queue worker. Check PHP exec availability and server logs.'];
        }

        Setting::set('trend_sync_process_id', $pid);
        Setting::set('trend_sync_log', json_encode([
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

    public function stopTrackedSyncProcess(string $message): void
    {
        $pid = Setting::get('trend_sync_process_id');

        if ($pid && !$this->killProcessById($pid)) {
            $message .= ' Worker process could not be terminated cleanly.';
        }

        $this->releaseSyncUniqueLock();

        Setting::set('trend_sync_status', 'stopped');
        Setting::set('trend_sync_finished_at', now()->toIso8601String());
        Setting::set('trend_sync_process_id', null);
        Setting::set('trend_sync_last_output', $message);
        Setting::set('trend_sync_log', json_encode([
            [
                'time' => now()->toIso8601String(),
                'level' => 'warning',
                'message' => $message,
            ],
        ]));
    }

    protected function currentSlotStart(int $intervalMinutes): Carbon
    {
        $now = now()->copy()->second(0);
        $minute = (int) floor($now->minute / $intervalMinutes) * $intervalMinutes;

        return $now->minute($minute);
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
            escapeshellarg(storage_path('logs/trend-sync-worker.log')),
            $workerCommand,
            escapeshellarg(storage_path('logs/trend-sync-worker.log'))
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
        app(UniqueLock::class)->release(new RunTrendSyncCycle());
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
