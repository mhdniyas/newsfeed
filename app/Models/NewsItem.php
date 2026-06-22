<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'news_topic_id',
    'news_section_id',
    'title',
    'source_name',
    'description',
    'url',
    'image_url',
    'hash',
    'published_at',
    'is_visible',
    'is_featured',
    'views_count',
    'clicks_count',
    'last_viewed_at',
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
            'last_viewed_at' => 'datetime',
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
        if ($this->image_url) {
            return $this->image_url;
        }

        return '/media/fifa-placeholder/' . rawurlencode($this->hash ?: (string) $this->id) . '.svg';
    }
}
