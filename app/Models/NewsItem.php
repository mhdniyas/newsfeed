<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'news_topic_id',
    'news_section_id',
    'title',
    'source_name',
    'source_domain',
    'source_courtesy',
    'description',
    'extracted_body',
    'extracted_author',
    'url',
    'canonical_url',
    'image_url',
    'extracted_image_url',
    'hash',
    'slug',
    'published_at',
    'is_visible',
    'is_featured',
    'is_favorite',
    'extraction_status',
    'extracted_at',
    'extraction_error',
    'extraction_retry_after',
    'views_count',
    'detail_views_count',
    'clicks_count',
    'last_viewed_at',
    'last_detail_viewed_at',
    'last_clicked_at',
])]
class NewsItem extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            if (!$item->news_section_id && $item->news_topic_id) {
                $item->news_section_id = NewsTopic::query()->whereKey($item->news_topic_id)->value('news_section_id');
            }

            $item->slug = $item->slug ?: $item->makeSlug();
            $item->canonical_url = $item->canonical_url ?: $item->url;
            $item->source_domain = $item->source_domain ?: $item->resolveSourceDomain();
            $item->source_courtesy = $item->source_courtesy ?: $item->resolveSourceCourtesy();
            $item->extraction_status = $item->extraction_status ?: 'pending';

            // Set as featured if it belongs to Google Trends section
            if ($item->news_section_id) {
                $isTrends = cache()->remember("section-is-trends:{$item->news_section_id}", 3600, function () use ($item) {
                    return NewsSection::query()->whereKey($item->news_section_id)->where('slug', 'google-trends')->exists();
                });
                if ($isTrends) {
                    $item->is_featured = true;
                }
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'is_favorite' => 'boolean',
            'extracted_body' => 'array',
            'extracted_at' => 'datetime',
            'extraction_retry_after' => 'datetime',
            'last_viewed_at' => 'datetime',
            'last_detail_viewed_at' => 'datetime',
            'last_clicked_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique hash for the article to prevent duplicates.
     */
    public static function generateHash(string $title, string $url): string
    {
        return md5(trim($title) . '|' . trim($url));
    }

    /**
     * Scope a query to only include visible articles.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope a query to only include featured articles.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the topic that owns the news item.
     */
    public function newsTopic(): BelongsTo
    {
        return $this->belongsTo(NewsTopic::class);
    }

    public function newsSection(): BelongsTo
    {
        return $this->belongsTo(NewsSection::class);
    }

    /**
     * Resolve the image that should be displayed on the public card.
     */
    public function displayImageUrl(): string
    {
        if ($this->extracted_image_url) {
            return $this->extracted_image_url;
        }

        if ($this->image_url) {
            return $this->image_url;
        }

        return '/media/fifa-placeholder/' . rawurlencode($this->hash ?: (string) $this->id) . '.svg';
    }

    public function excerptParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', $this->extracted_body ?? [])));
    }

    public function makeSlug(): string
    {
        $base = Str::slug(Str::limit(trim((string) $this->title), 80, ''));

        return ($base !== '' ? $base : 'news-item') . '-' . substr((string) $this->hash, 0, 8);
    }

    public function resolveSourceDomain(): ?string
    {
        $host = parse_url((string) ($this->canonical_url ?: $this->url), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    public function resolveSourceCourtesy(): ?string
    {
        $domain = $this->resolveSourceDomain();

        return $domain ? preg_replace('/^www\./', '', $domain) : ($this->source_name ?: null);
    }
}
