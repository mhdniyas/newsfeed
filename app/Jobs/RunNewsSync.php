<?php

namespace App\Jobs;

use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RunNewsSync implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 900;

    public int $uniqueFor = 600;

    public function __construct(public string $source = 'Queued sync job')
    {
        $this->onQueue('syncs');
    }

    public function uniqueId(): string
    {
        return 'news-sync';
    }

    public function handle(): void
    {
        $wasQueued = Setting::get('news_sync_status') === 'queued';

        Setting::set('news_sync_status', 'running');
        if (!$wasQueued) {
            Setting::set('news_sync_requested_at', now()->toIso8601String());
        }
        Setting::set('news_sync_started_at', now()->toIso8601String());
        Setting::set('news_sync_finished_at', null);
        Setting::set('news_sync_last_output', null);
        Setting::set('news_sync_log', json_encode([
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => $this->source . ' accepted by worker.',
            ],
        ]));
        Setting::set('news_sync_meta', json_encode([
            'progress' => 3,
            'stage' => 'Worker booted',
            'processed_topics' => 0,
            'total_topics' => 0,
            'stats' => [
                'new_articles' => 0,
                'skipped_duplicates' => 0,
                'images_recovered' => 0,
            ],
        ]));

        try {
            Artisan::call('news:fetch');

            Setting::set('news_sync_status', 'completed');
            Setting::set('news_sync_last_output', trim(Artisan::output()));
            Setting::set('news_sync_finished_at', now()->toIso8601String());
            Setting::set('news_sync_process_id', null);
            $meta = json_decode(Setting::get('news_sync_meta', '{}') ?: '{}', true);
            $meta['progress'] = 100;
            $meta['stage'] = 'Completed';
            Setting::set('news_sync_meta', json_encode($meta));
        } catch (Throwable $exception) {
            Setting::set('news_sync_status', 'failed');
            Setting::set('news_sync_last_output', $exception->getMessage());
            Setting::set('news_sync_finished_at', now()->toIso8601String());
            Setting::set('news_sync_process_id', null);
            $log = json_decode(Setting::get('news_sync_log', '[]') ?: '[]', true);
            $log[] = [
                'time' => now()->toIso8601String(),
                'level' => 'error',
                'message' => 'Background sync failed: ' . $exception->getMessage(),
            ];
            Setting::set('news_sync_log', json_encode(array_slice($log, -40)));
            $meta = json_decode(Setting::get('news_sync_meta', '{}') ?: '{}', true);
            $meta['progress'] = max(5, (int) ($meta['progress'] ?? 0));
            $meta['stage'] = 'Failed';
            Setting::set('news_sync_meta', json_encode($meta));

            throw $exception;
        }
    }
}
