<?php

namespace App\Jobs;

use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use App\Services\NewsSyncStateService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunNewsSyncCycle implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 900;

    public int $uniqueFor = 600;

    public function __construct(public string $source = 'Queued sync cycle')
    {
        $this->onQueue('syncs');
    }

    public function uniqueId(): string
    {
        return 'news-sync-cycle';
    }

    public function handle(NewsSyncStateService $syncState): void
    {
        $sections = NewsSection::query()
            ->where('is_active', true)
            ->withCount(['newsTopics' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $totalTopics = (int) NewsTopic::query()
            ->where('is_active', true)
            ->whereIn('news_section_id', $sections->pluck('id'))
            ->count();

        $syncState->startCycle($this->source . ' accepted by worker.', $sections->count(), $totalTopics, 60);
        Setting::set('news_sync_process_id', Setting::get('news_sync_process_id'));

        if ($sections->isEmpty()) {
            $syncState->appendLog('No active sections found. Nothing to fetch.', 'warning');
            $syncState->finalizeCycle();
            return;
        }

        foreach ($sections as $section) {
            FetchNewsSection::dispatch($section->id);
        }
    }
}
