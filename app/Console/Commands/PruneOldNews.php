<?php

namespace App\Console\Commands;

use App\Services\NewsRetentionService;
use Illuminate\Console\Command;

class PruneOldNews extends Command
{
    protected $signature = 'news:prune-old
        {--days=3 : Delete articles older than this many days}
        {--click-threshold=50 : Keep articles with at least this many clicks}';

    protected $description = 'Delete old news articles while keeping favorites and high-click stories';

    public function handle(NewsRetentionService $newsRetention): int
    {
        $days = max(1, (int) $this->option('days'));
        $clickThreshold = max(0, (int) $this->option('click-threshold'));
        $result = $newsRetention->prune($days, $clickThreshold);

        $this->info(sprintf(
            'Deleted %d article(s) older than %d days with fewer than %d clicks. Protected: %d, favorites protected: %d.',
            $result['deleted_count'],
            $result['days'],
            $result['click_threshold'],
            $result['protected_count'],
            $result['favorite_protected_count']
        ));

        return self::SUCCESS;
    }
}
