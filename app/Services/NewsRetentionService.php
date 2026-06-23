<?php

namespace App\Services;

use App\Models\NewsItem;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class NewsRetentionService
{
    public const DEFAULT_DAYS = 3;
    public const DEFAULT_CLICK_THRESHOLD = 50;
    public const TOTAL_DESTROYED_KEY = 'news_destroy_total_count';

    public function retentionDays(): int
    {
        return max(1, (int) Setting::get('news_prune_last_days', (string) self::DEFAULT_DAYS));
    }

    public function clickThreshold(): int
    {
        return max(0, (int) Setting::get('news_prune_last_click_threshold', (string) self::DEFAULT_CLICK_THRESHOLD));
    }

    public function cutoff(?int $days = null): Carbon
    {
        return now()->subDays($days ?? $this->retentionDays());
    }

    public function eligibleQuery(?int $days = null, ?int $clickThreshold = null): Builder
    {
        return NewsItem::query()
            ->where('published_at', '<', $this->cutoff($days))
            ->where('clicks_count', '<', $this->resolveClickThreshold($clickThreshold))
            ->where('is_favorite', false);
    }

    public function protectedQuery(?int $days = null, ?int $clickThreshold = null): Builder
    {
        $cutoff = $this->cutoff($days);
        $threshold = $this->resolveClickThreshold($clickThreshold);

        return NewsItem::query()
            ->where('published_at', '<', $cutoff)
            ->where(function (Builder $query) use ($threshold): void {
                $query->where('clicks_count', '>=', $threshold)
                    ->orWhere('is_favorite', true);
            });
    }

    public function isEligibleForDeletion(NewsItem $article, ?int $days = null, ?int $clickThreshold = null): bool
    {
        if ($article->is_favorite) {
            return false;
        }

        if (!$article->published_at) {
            return false;
        }

        return $article->published_at->lt($this->cutoff($days))
            && (int) $article->clicks_count < $this->resolveClickThreshold($clickThreshold);
    }

    public function prune(?int $days = null, ?int $clickThreshold = null): array
    {
        $resolvedDays = max(1, $days ?? $this->retentionDays());
        $resolvedThreshold = $this->resolveClickThreshold($clickThreshold);
        $cutoff = $this->cutoff($resolvedDays);

        $eligibleQuery = $this->eligibleQuery($resolvedDays, $resolvedThreshold);
        $eligibleCount = (clone $eligibleQuery)->count();
        $protectedCount = $this->protectedQuery($resolvedDays, $resolvedThreshold)->count();
        $favoriteProtectedCount = NewsItem::query()
            ->where('published_at', '<', $cutoff)
            ->where('is_favorite', true)
            ->count();
        $deleted = (clone $eligibleQuery)->delete();
        $this->recordDeletedCount($deleted);

        Setting::set('news_prune_last_run_at', now()->toIso8601String());
        Setting::set('news_prune_last_cutoff_at', $cutoff->toIso8601String());
        Setting::set('news_prune_last_days', (string) $resolvedDays);
        Setting::set('news_prune_last_click_threshold', (string) $resolvedThreshold);
        Setting::set('news_prune_last_eligible_count', (string) $eligibleCount);
        Setting::set('news_prune_last_deleted_count', (string) $deleted);
        Setting::set('news_prune_last_protected_count', (string) $protectedCount);
        Setting::set('news_prune_last_favorite_protected_count', (string) $favoriteProtectedCount);

        return [
            'days' => $resolvedDays,
            'click_threshold' => $resolvedThreshold,
            'cutoff_at' => $cutoff,
            'eligible_count' => $eligibleCount,
            'deleted_count' => $deleted,
            'protected_count' => $protectedCount,
            'favorite_protected_count' => $favoriteProtectedCount,
        ];
    }

    public function recordDeletedCount(int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $current = (int) Setting::get(self::TOTAL_DESTROYED_KEY, '0');
        Setting::set(self::TOTAL_DESTROYED_KEY, (string) ($current + $count));
    }

    public function totalDestroyedCount(): int
    {
        return (int) Setting::get(self::TOTAL_DESTROYED_KEY, '0');
    }

    protected function resolveClickThreshold(?int $clickThreshold = null): int
    {
        return max(0, $clickThreshold ?? $this->clickThreshold());
    }
}
