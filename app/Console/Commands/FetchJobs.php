<?php

namespace App\Console\Commands;

use App\Services\JobFetchService;
use Illuminate\Console\Command;

class FetchJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch daily jobs from Google News RSS search.';

    /**
     * Execute the console command.
     */
    public function handle(JobFetchService $fetchService): int
    {
        $this->info('Starting job listings fetch sync...');

        try {
            $results = $fetchService->sync();
            
            $this->info('Job listings fetch sync completed successfully.');
            $this->line("- New Jobs Added: {$results['new_jobs']}");
            $this->line("- Skipped Duplicates: {$results['skipped_duplicates']}");
            $this->line("- Categories Processed: {$results['categories_processed']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error running jobs fetch: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
