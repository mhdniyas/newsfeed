<?php

namespace App\Jobs;

use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Services\TrendSyncStateService;
use App\Services\TrendingNewsService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunTrendSyncCycle implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 900;

    public int $uniqueFor = 600;

    public function __construct(public string $source = 'Queued trend sync cycle')
    {
        $this->onQueue('syncs');
    }

    public function uniqueId(): string
    {
        return 'trend-sync-cycle';
    }

    public function handle(TrendSyncStateService $syncState, TrendingNewsService $trendingNewsService): void
    {
        $section = NewsSection::query()->where('slug', 'google-trends')->first();
        $countryCount = count(TrendingNewsService::COUNTRY_CONFIG);

        try {
            if (!$section || !$section->is_active) {
                $syncState->startCycle($this->source . ' accepted by worker.', $countryCount, 0, TrendingNewsService::NEWS_ARTICLE_LIMIT, TrendingNewsService::ARTICLES_PER_COUNTRY);
                $syncState->appendLog('Google Trends section is missing or inactive. Nothing to fetch.', 'warning');
                $syncState->finalizeCycle();
                return;
            }

            $syncState->startCycle(
                $this->source . ' accepted by worker.',
                $countryCount,
                0,
                TrendingNewsService::NEWS_ARTICLE_LIMIT,
                TrendingNewsService::ARTICLES_PER_COUNTRY
            );

            $trends = $trendingNewsService->fetchTrends();
            $stats = $trendingNewsService->updateKeywordSpots($trends);
            $totalTopics = (int) NewsTopic::query()
                ->where('news_section_id', $section->id)
                ->where('is_active', true)
                ->count();

            $syncState->updateMeta([
                'stage' => 'Keyword refresh completed',
                'total_topics' => $totalTopics,
            ]);

            $syncState->appendLog('Trend keywords refreshed: ' . collect($stats)
                ->map(fn ($countryStats, $countryCode) => "{$countryCode} {$countryStats['active']} active")
                ->implode(', '), 'success');

            foreach (array_keys(TrendingNewsService::COUNTRY_CONFIG) as $countryCode) {
                if ($syncState->globalLimitReached()) {
                    $syncState->appendLog('Global trend article limit reached. Remaining countries will be picked up in the next run.', 'warning');
                    break;
                }

                $summary = $trendingNewsService->syncCountryTrendingNews($countryCode, TrendingNewsService::ARTICLES_PER_COUNTRY, $syncState);

                $countrySection = $trendingNewsService->countrySectionCard($countryCode);
                $syncState->completeSection($countrySection, $summary);
            }

            $syncState->appendLog('Finalizing trend sync cycle results.', 'info');
            $syncState->finalizeCycle();
        } catch (\Throwable $exception) {
            $syncState->appendLog('Trend sync failed: ' . $exception->getMessage(), 'error');
            $syncState->updateMeta([
                'stage' => 'Failed',
                'summary' => 'Trend sync failed before completing all countries.',
            ]);
            \App\Models\Setting::set('trend_sync_status', 'failed');
            \App\Models\Setting::set('trend_sync_finished_at', now()->toIso8601String());
            \App\Models\Setting::set('trend_sync_last_output', $exception->getMessage());
            \App\Models\Setting::set('trend_sync_process_id', null);

            throw $exception;
        }
    }
}
