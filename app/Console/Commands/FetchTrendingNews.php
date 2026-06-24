<?php

namespace App\Console\Commands;

use App\Services\TrendingNewsService;
use Illuminate\Console\Command;

class FetchTrendingNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch-trends {--limit=500 : Maximum new articles to save in one run} {--sync-news : Also fetch news immediately after refreshing trend keywords}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch trending keywords from Google Trends across priority countries and refresh the Google Trends keyword pool.';

    /**
     * Execute the console command.
     */
    public function handle(TrendingNewsService $trendingNewsService): int
    {
        $this->info('Starting Google Trends keyword refresh...');

        $trends = $trendingNewsService->fetchTrends();
        $countries = collect($trends)
            ->map(fn ($keywords, $countryCode) => "{$countryCode}: " . count($keywords))
            ->implode(', ');

        $this->info("Fetched country trend batches: {$countries}");

        $this->info('Updating country keyword pools...');
        $updatedStats = $trendingNewsService->updateKeywordSpots($trends);

        foreach ($updatedStats as $countryCode => $countryStats) {
            $this->line("{$countryCode}: fetched {$countryStats['fetched']}, active {$countryStats['active']}, stored {$countryStats['stored']}");
        }

        if (!$this->option('sync-news')) {
            $this->info('Keyword refresh complete. The 5-minute trend crawler will pick up these updated keywords automatically.');

            return self::SUCCESS;
        }

        $limit = max(1, min(500, (int) $this->option('limit')));
        $this->info("Fetching news articles for active trend keywords now (max new articles: {$limit})...");

        $savedArticlesCount = $trendingNewsService->syncTrendingNews($limit);

        $this->info("Sync complete. Saved {$savedArticlesCount} new featured trend articles.");

        return self::SUCCESS;
    }
}
