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
    public const DEFAULT_BATCH_LIMIT = 1000;
    public const MIN_BATCH_LIMIT = 500;
    public const MAX_BATCH_LIMIT = 3000;
    public const DEFAULT_MODE = 'standard';
    public const DEFAULT_SORT = 'oldest';
    public const TOTAL_DESTROYED_KEY = 'news_destroy_total_count';
    public const AUTO_ENABLED_KEY = 'news_prune_enabled';
    public const MODES = [
        'standard',
        'no_clicks',
        'no_views',
        'viewed_no_clicks',
    ];
    public const SORTS = [
        'oldest',
        'latest',
        'least_clicked',
        'least_viewed',
        'most_clicked',
        'most_viewed',
        'title',
    ];

    public function autoDeletionEnabled(): bool
    {
        return filter_var(Setting::get(self::AUTO_ENABLED_KEY, '0'), FILTER_VALIDATE_BOOL);
    }

    public function retentionDays(): int
    {
        return max(1, (int) Setting::get('news_prune_last_days', (string) self::DEFAULT_DAYS));
    }

    public function clickThreshold(): int
    {
        return max(0, (int) Setting::get('news_prune_last_click_threshold', (string) self::DEFAULT_CLICK_THRESHOLD));
    }

    public function batchLimit(): int
    {
        return $this->clampBatchLimit((int) Setting::get('news_prune_batch_limit', (string) self::DEFAULT_BATCH_LIMIT));
    }

    public function mode(): string
    {
        $mode = (string) Setting::get('news_prune_mode', self::DEFAULT_MODE);

        return in_array($mode, self::MODES, true) ? $mode : self::DEFAULT_MODE;
    }

    public function sort(): string
    {
        $sort = (string) Setting::get('news_prune_sort', self::DEFAULT_SORT);

        return in_array($sort, self::SORTS, true) ? $sort : self::DEFAULT_SORT;
    }

    public function cutoff(?int $days = null): Carbon
    {
        return now()->subDays($days ?? $this->retentionDays());
    }

    public function settings(array $overrides = []): array
    {
        $days = array_key_exists('days', $overrides) && $overrides['days'] !== null
            ? max(1, (int) $overrides['days'])
            : $this->retentionDays();
        $clickThreshold = array_key_exists('click_threshold', $overrides) && $overrides['click_threshold'] !== null
            ? max(0, (int) $overrides['click_threshold'])
            : $this->clickThreshold();
        $batchLimit = array_key_exists('batch_limit', $overrides) && $overrides['batch_limit'] !== null
            ? $this->clampBatchLimit((int) $overrides['batch_limit'])
            : $this->batchLimit();
        $mode = array_key_exists('mode', $overrides) && is_string($overrides['mode']) && in_array($overrides['mode'], self::MODES, true)
            ? $overrides['mode']
            : $this->mode();
        $sort = array_key_exists('sort', $overrides) && is_string($overrides['sort']) && in_array($overrides['sort'], self::SORTS, true)
            ? $overrides['sort']
            : $this->sort();
        $enabled = array_key_exists('enabled', $overrides)
            ? (bool) $overrides['enabled']
            : $this->autoDeletionEnabled();

        return [
            'enabled' => $enabled,
            'days' => $days,
            'click_threshold' => $clickThreshold,
            'batch_limit' => $batchLimit,
            'mode' => $mode,
            'sort' => $sort,
            'cutoff_at' => $this->cutoff($days),
        ];
    }

    public function saveSettings(array $settings): array
    {
        $resolved = $this->settings($settings);

        Setting::set(self::AUTO_ENABLED_KEY, $resolved['enabled'] ? '1' : '0');
        Setting::set('news_prune_last_days', (string) $resolved['days']);
        Setting::set('news_prune_last_click_threshold', (string) $resolved['click_threshold']);
        Setting::set('news_prune_batch_limit', (string) $resolved['batch_limit']);
        Setting::set('news_prune_mode', $resolved['mode']);
        Setting::set('news_prune_sort', $resolved['sort']);

        return $resolved;
    }

    public function eligibleQuery(array $settings = []): Builder
    {
        $resolved = $this->settings($settings);
        $query = NewsItem::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<', $resolved['cutoff_at'])
            ->where('is_favorite', false);

        return match ($resolved['mode']) {
            'no_clicks' => $query->where('clicks_count', '<=', 0),
            'no_views' => $query->where('views_count', '<=', 0),
            'viewed_no_clicks' => $query->where('views_count', '>', 0)->where('clicks_count', '<=', 0),
            default => $query->where('clicks_count', '<', $resolved['click_threshold']),
        };
    }

    public function protectedQuery(array $settings = []): Builder
    {
        $resolved = $this->settings($settings);

        return NewsItem::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<', $resolved['cutoff_at'])
            ->where(function (Builder $query) use ($resolved): void {
                $query->where('is_favorite', true);

                if ($resolved['mode'] === 'standard') {
                    $query->orWhere('clicks_count', '>=', $resolved['click_threshold']);
                } elseif ($resolved['mode'] === 'no_clicks') {
                    $query->orWhere('clicks_count', '>', 0);
                } elseif ($resolved['mode'] === 'no_views') {
                    $query->orWhere('views_count', '>', 0);
                } elseif ($resolved['mode'] === 'viewed_no_clicks') {
                    $query->orWhere('views_count', '<=', 0)
                        ->orWhere('clicks_count', '>', 0);
                }
            });
    }

    public function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'latest' => $query->orderByDesc('published_at')->orderByDesc('id'),
            'least_clicked' => $query->orderBy('clicks_count')->orderBy('views_count')->orderBy('published_at'),
            'least_viewed' => $query->orderBy('views_count')->orderBy('clicks_count')->orderBy('published_at'),
            'most_clicked' => $query->orderByDesc('clicks_count')->orderByDesc('published_at'),
            'most_viewed' => $query->orderByDesc('views_count')->orderByDesc('published_at'),
            'title' => $query->orderBy('title'),
            default => $query->orderBy('published_at')->orderBy('id'),
        };
    }

    public function isEligibleForDeletion(NewsItem $article, array $settings = []): bool
    {
        $resolved = $this->settings($settings);

        if ($article->is_favorite || !$article->published_at || $article->published_at->gte($resolved['cutoff_at'])) {
            return false;
        }

        return match ($resolved['mode']) {
            'no_clicks' => (int) $article->clicks_count <= 0,
            'no_views' => (int) $article->views_count <= 0,
            'viewed_no_clicks' => (int) $article->views_count > 0 && (int) $article->clicks_count <= 0,
            default => (int) $article->clicks_count < $resolved['click_threshold'],
        };
    }

    public function prune(array $settings = []): array
    {
        $resolved = $this->settings($settings);
        $eligibleQuery = $this->applySort($this->eligibleQuery($resolved), $resolved['sort']);
        $eligibleTotal = (clone $eligibleQuery)->count();
        $protectedCount = $this->protectedQuery($resolved)->count();
        $favoriteProtectedCount = NewsItem::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<', $resolved['cutoff_at'])
            ->where('is_favorite', true)
            ->count();
        $idsToDelete = (clone $eligibleQuery)
            ->limit($resolved['batch_limit'])
            ->pluck('id');
        $deleted = $idsToDelete->isEmpty()
            ? 0
            : NewsItem::query()->whereIn('id', $idsToDelete)->delete();

        $this->recordDeletedCount($deleted);

        Setting::set('news_prune_last_run_at', now()->toIso8601String());
        Setting::set('news_prune_last_cutoff_at', $resolved['cutoff_at']->toIso8601String());
        Setting::set('news_prune_last_days', (string) $resolved['days']);
        Setting::set('news_prune_last_click_threshold', (string) $resolved['click_threshold']);
        Setting::set('news_prune_batch_limit', (string) $resolved['batch_limit']);
        Setting::set('news_prune_mode', $resolved['mode']);
        Setting::set('news_prune_sort', $resolved['sort']);
        Setting::set('news_prune_last_eligible_count', (string) $eligibleTotal);
        Setting::set('news_prune_last_deleted_count', (string) $deleted);
        Setting::set('news_prune_last_protected_count', (string) $protectedCount);
        Setting::set('news_prune_last_favorite_protected_count', (string) $favoriteProtectedCount);

        return [
            'enabled' => $resolved['enabled'],
            'days' => $resolved['days'],
            'click_threshold' => $resolved['click_threshold'],
            'batch_limit' => $resolved['batch_limit'],
            'mode' => $resolved['mode'],
            'sort' => $resolved['sort'],
            'cutoff_at' => $resolved['cutoff_at'],
            'eligible_count' => $eligibleTotal,
            'delete_batch_count' => $idsToDelete->count(),
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

    protected function clampBatchLimit(int $batchLimit): int
    {
        return max(self::MIN_BATCH_LIMIT, min(self::MAX_BATCH_LIMIT, $batchLimit));
    }
}
