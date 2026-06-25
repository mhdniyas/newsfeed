<?php

namespace App\Console\Commands;

use App\Models\NewsSection;
use App\Models\NewsTopic;
use App\Services\NewsFetchService;
use App\Services\NewsSyncStateService;
use Illuminate\Console\Command;

class FetchWorldCupNews extends Command
{
    protected $signature = 'news:fetch {--section= : Specific section ID to fetch} {--topic= : Specific topic ID to fetch} {--limit=500 : Maximum new articles to save in one run}';

    protected $description = 'Fetch the latest news across active sections and topics';

    public function __construct(
        protected NewsFetchService $newsFetchService,
        protected NewsSyncStateService $syncState
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $limit = max(1, min(500, (int) $this->option('limit')));
        $sectionId = $this->option('section');
        $topicId = $this->option('topic');

        $sections = NewsSection::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'google-trends')
            ->when($sectionId, fn ($query) => $query->whereKey($sectionId))
            ->when($topicId, fn ($query) => $query->whereHas('newsTopics', fn ($topicQuery) => $topicQuery->whereKey($topicId)))
            ->with(['newsTopics' => function ($query) use ($topicId) {
                $query->where('is_active', true)
                    ->when($topicId, fn ($topicQuery) => $topicQuery->whereKey($topicId))
                    ->orderBy('sort_order')
                    ->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $totalTopics = $sections->sum(fn (NewsSection $section) => $section->newsTopics->count());
        $this->syncState->startCycle('CLI news fetch started.', $sections->count(), $totalTopics, $limit);
        $this->info('Starting news fetch..');

        if ($sections->isEmpty()) {
            $this->warn('No active sections found.');
            $this->line($this->syncState->finalizeCycle());
            return self::SUCCESS;
        }

        foreach ($sections as $section) {
            if ($this->syncState->globalLimitReached()) {
                $this->syncState->appendLog("Reached the {$limit}-article cycle limit. Stopping remaining sections.", 'warning');
                break;
            }

            $this->info("Fetching section: {$section->name}");
            $summary = $this->newsFetchService->fetchSection($section, $limit);
            $this->syncState->completeSection($section, $summary);
        }

        $summary = $this->syncState->finalizeCycle();
        $this->info($summary);

        return self::SUCCESS;
    }
}
