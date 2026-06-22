<?php

namespace App\Jobs;

use App\Models\NewsSection;
use App\Services\NewsFetchService;
use App\Services\NewsSyncStateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchNewsSection implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(public int $sectionId)
    {
        $this->onQueue('syncs');
    }

    public function handle(NewsFetchService $fetchService, NewsSyncStateService $syncState): void
    {
        $section = NewsSection::query()->find($this->sectionId);

        if (!$section || !$section->is_active) {
            return;
        }

        try {
            $summary = $fetchService->fetchSection($section, 60);
            $isLast = $syncState->completeSection($section, $summary);

            if ($isLast && $syncState->finalizerShouldDispatch()) {
                FinalizeNewsSyncCycle::dispatch();
            }
        } catch (\Throwable $exception) {
            $isLast = $syncState->failSection($section, $exception->getMessage());

            if ($isLast && $syncState->finalizerShouldDispatch()) {
                FinalizeNewsSyncCycle::dispatch();
            }

            throw $exception;
        }
    }
}
