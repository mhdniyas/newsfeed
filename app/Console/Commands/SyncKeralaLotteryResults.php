<?php

namespace App\Console\Commands;

use App\Services\KeralaLotteryService;
use Illuminate\Console\Command;

class SyncKeralaLotteryResults extends Command
{
    protected $signature = 'lottery:sync-kerala-results {--limit=10}';

    protected $description = 'Fetch the latest Kerala lottery results, download the official PDF, and parse top prizes.';

    public function handle(KeralaLotteryService $service): int
    {
        try {
            $stats = $service->syncLatest((int) $this->option('limit'));
            $this->info("Kerala lottery sync completed. Saved {$stats['saved']} results from {$stats['rows']} rows.");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Kerala lottery sync failed: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }
}
