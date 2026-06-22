<?php

namespace App\Jobs;

use App\Services\NewsSyncStateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeNewsSyncCycle implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('syncs');
    }

    public function handle(NewsSyncStateService $syncState): void
    {
        $syncState->appendLog('Finalizing sync cycle results.', 'info');
        $syncState->finalizeCycle();
    }
}
