<?php

namespace App\Jobs;

use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Models\Setting;
use App\Services\AutomaticNewsSyncService;
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
        $allSections = NewsSection::query()
            ->where('is_active', true)
            ->withCount(['newsTopics' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $batchSize = AutomaticNewsSyncService::SECTION_BATCH_SIZE;
        $sectionOffset = max(0, (int) Setting::get('news_sync_section_offset', '0'));
        $sections = $allSections->slice($sectionOffset, $batchSize)->values();

        if ($sections->count() < $batchSize && $allSections->count() > $sections->count()) {
            $sections = $sections
                ->concat($allSections->slice(0, $batchSize - $sections->count()))
                ->values();
        }

        if ($allSections->isNotEmpty()) {
            $nextOffset = ($sectionOffset + $sections->count()) % $allSections->count();
            Setting::set('news_sync_section_offset', (string) $nextOffset);
            Setting::set('news_sync_last_section_batch', json_encode($sections->pluck('id')->all()));
        }

        $totalTopics = (int) NewsTopic::query()
            ->where('is_active', true)
            ->whereIn('news_section_id', $sections->pluck('id'))
            ->count();

        $syncState->startCycle(
            $this->source . ' accepted by worker.',
            $sections->count(),
            $totalTopics,
            AutomaticNewsSyncService::CYCLE_ARTICLE_LIMIT,
            $batchSize,
            $allSections->count()
        );
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
