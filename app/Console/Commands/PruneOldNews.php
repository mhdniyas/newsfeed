<?php

namespace App\Console\Commands;

use App\Models\NewsItem;
use App\Models\Setting;
use Illuminate\Console\Command;

class PruneOldNews extends Command
{
    protected $signature = 'news:prune-old
        {--days=8 : Delete articles older than this many days}
        {--click-threshold=500 : Keep articles with more than this many clicks}';

    protected $description = 'Delete old news articles while keeping high-click stories';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $clickThreshold = max(0, (int) $this->option('click-threshold'));
        $cutoff = now()->subDays($days);

        $query = NewsItem::query()
            ->where('published_at', '<', $cutoff)
            ->where('clicks_count', '<=', $clickThreshold);

        $eligibleCount = (clone $query)->count();
        $protectedCount = NewsItem::query()
            ->where('published_at', '<', $cutoff)
            ->where('clicks_count', '>', $clickThreshold)
            ->count();

        $deleted = (clone $query)->delete();

        Setting::set('news_prune_last_run_at', now()->toIso8601String());
        Setting::set('news_prune_last_cutoff_at', $cutoff->toIso8601String());
        Setting::set('news_prune_last_days', (string) $days);
        Setting::set('news_prune_last_click_threshold', (string) $clickThreshold);
        Setting::set('news_prune_last_eligible_count', (string) $eligibleCount);
        Setting::set('news_prune_last_deleted_count', (string) $deleted);
        Setting::set('news_prune_last_protected_count', (string) $protectedCount);

        $this->info(sprintf(
            'Deleted %d article(s) published before %s with %d or fewer clicks.',
            $deleted,
            $cutoff->toDateTimeString(),
            $clickThreshold
        ));

        return self::SUCCESS;
    }
}
