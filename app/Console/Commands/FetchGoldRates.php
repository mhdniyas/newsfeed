<?php

namespace App\Console\Commands;

use App\Services\GoldRateFetchService;
use Illuminate\Console\Command;

class FetchGoldRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold:fetch-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch daily gold rates from IBJA and GoodReturns.';

    /**
     * Execute the console command.
     */
    public function handle(GoldRateFetchService $fetchService): int
    {
        $this->info('Starting gold rates fetch sync...');

        try {
            $results = $fetchService->sync();
            
            $this->info('Gold rates fetch sync completed successfully.');
            
            foreach ($results as $city => $purities) {
                $this->line("- {$city}: " . implode(', ', array_keys($purities)));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error running gold rates fetch: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
