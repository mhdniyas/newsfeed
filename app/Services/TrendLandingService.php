<?php

namespace App\Services;

use App\Models\NewsItem;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrendLandingService
{
    protected array $fixedPages = [
        [
            'slug' => 'messi',
            'title' => 'Messi',
            'description' => 'Live keyword coverage and linked stories around Messi and related football headlines.',
            'keywords' => ['messi', 'lionel messi'],
        ],
        [
            'slug' => 'fifa-2026',
            'title' => 'FIFA 2026',
            'description' => 'World Cup 2026 trend stories, qualifying talk, host coverage, and tournament build-up.',
            'keywords' => ['fifa 2026', 'world cup 2026', 'fifa world cup 2026'],
        ],
        [
            'slug' => 'middle-east',
            'title' => 'Middle East',
            'description' => 'Trending links and major headlines connected to the Middle East.',
            'keywords' => ['middle east', 'gaza', 'iran', 'israel', 'qatar', 'saudi'],
        ],
    ];

    public function homepagePages(int $dynamicLimit = 8): Collection
    {
        $dynamicPages = $this->dynamicPages(max(1, $dynamicLimit))
            ->unique('slug')
            ->values();

        if ($dynamicPages->isNotEmpty()) {
            return $dynamicPages;
        }

        return $this->fixedPages()
            ->unique('slug')
            ->values();
    }

    public function fixedPages(): Collection
    {
        return collect($this->fixedPages)->map(fn (array $page) => array_merge($page, [
            'kind' => 'fixed',
        ]));
    }

    public function dynamicPages(int $limit = 8): Collection
    {
        $trendsSectionId = NewsSection::query()->where('slug', 'google-trends')->value('id');

        if (!$trendsSectionId) {
            return collect();
        }

        return NewsTopic::query()
            ->where('news_section_id', $trendsSectionId)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderBy('sort_order')
            ->limit(max(1, $limit))
            ->get()
            ->map(function (NewsTopic $topic) {
                $keyword = trim((string) $topic->keyword);
                $slug = Str::slug($keyword);

                if ($slug === '') {
                    $fallbackBits = array_filter([
                        'trend',
                        strtolower((string) ($topic->country ?: 'global')),
                        (string) $topic->id,
                    ]);

                    $slug = implode('-', $fallbackBits);
                }

                return [
                    'slug' => $slug,
                    'title' => $topic->name,
                    'description' => 'Live Google Trends keyword page built from current active trend topics.',
                    'keywords' => [$keyword],
                    'kind' => 'dynamic',
                    'topic_id' => $topic->id,
                    'country' => $topic->country,
                ];
            });
    }

    public function resolve(string $slug): ?array
    {
        $page = $this->fixedPages()
            ->concat($this->dynamicPages(20))
            ->unique('slug')
            ->values()
            ->firstWhere('slug', $slug);

        return $page ?: null;
    }

    public function articlesFor(array $page, int $limit = 18): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = NewsItem::query()
            ->visible()
            ->with(['newsTopic', 'newsSection'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if (($page['kind'] ?? null) === 'dynamic' && !empty($page['topic_id'])) {
            $query->where('news_topic_id', $page['topic_id']);
        } else {
            $query->where(function (Builder $builder) use ($page): void {
                foreach ((array) ($page['keywords'] ?? []) as $keyword) {
                    $builder->orWhere('title', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%')
                        ->orWhereHas('newsTopic', fn (Builder $topicQuery) => $topicQuery->where('keyword', 'like', '%' . $keyword . '%')->orWhere('name', 'like', '%' . $keyword . '%'));
                }
            });
        }

        return $query->paginate($limit);
    }
}
