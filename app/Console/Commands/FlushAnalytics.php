<?php

namespace App\Console\Commands;

use App\Services\AnalyticsStorageService;
use Illuminate\Console\Command;

class FlushAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:flush';

    /**
     * The console command description.
     */
    protected $description = 'Flush Redis temporary counters and prune expired active users';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsStorageService $storage): int
    {
        // Prune active users older than 5 minutes (300 seconds)
        $storage->getActiveCount('analytics:active_users', time() - 300);

        // Prune active sessions older than 30 minutes (1800 seconds)
        $storage->getActiveCount('analytics:active_sessions', time() - 1800);

        $this->info('Analytics buffers flushed and expired active users pruned successfully.');

        return 0;
    }
}
