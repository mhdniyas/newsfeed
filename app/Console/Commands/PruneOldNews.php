<?php

namespace App\Console\Commands;

use App\Services\NewsRetentionService;
use Illuminate\Console\Command;

class PruneOldNews extends Command
{
    protected $signature = 'news:prune-old
        {--days= : Delete articles older than this many days}
        {--click-threshold= : Keep articles with at least this many clicks in standard mode}
        {--mode= : standard, no_clicks, no_views, viewed_no_clicks}
        {--sort= : oldest, latest, least_clicked, least_viewed, most_clicked, most_viewed, title}
        {--limit= : Delete up to this many rows per run (500-3000)}
        {--scheduled : Skip when automatic deletion is disabled}';

    protected $description = 'Delete old news articles while keeping favorites and high-click stories';

    public function handle(NewsRetentionService $newsRetention): int
    {
        if ($this->option('scheduled') && !$newsRetention->autoDeletionEnabled()) {
            $this->info('Automatic deletion is disabled. Scheduled prune skipped.');

            return self::SUCCESS;
        }

        $result = $newsRetention->prune([
            'days' => $this->option('days') !== null ? (int) $this->option('days') : null,
            'click_threshold' => $this->option('click-threshold') !== null ? (int) $this->option('click-threshold') : null,
            'mode' => $this->option('mode') ?: null,
            'sort' => $this->option('sort') ?: null,
            'batch_limit' => $this->option('limit') !== null ? (int) $this->option('limit') : null,
        ]);

        $this->info(sprintf(
            'Deleted %d article(s) from %d eligible using %s mode. Days: %d, click threshold: %d, limit: %d, protected: %d, favorites protected: %d.',
            $result['deleted_count'],
            $result['eligible_count'],
            $result['mode'],
            $result['days'],
            $result['click_threshold'],
            $result['batch_limit'],
            $result['protected_count'],
            $result['favorite_protected_count']
        ));

        return self::SUCCESS;
    }
}
