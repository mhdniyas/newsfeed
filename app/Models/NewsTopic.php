<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['news_section_id', 'name', 'keyword', 'language', 'country', 'sort_order', 'is_active'])]
class NewsTopic extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $topic): void {
            if (!$topic->news_section_id) {
                $topic->news_section_id = NewsSection::defaultSection()?->id;
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the news items for the topic.
     */
    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    public function newsSection(): BelongsTo
    {
        return $this->belongsTo(NewsSection::class);
    }
}
