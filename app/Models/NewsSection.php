<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'sort_order',
    'is_active',
    'is_default',
    'refresh_interval_minutes',
    'card_limit',
])]
class NewsSection extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function newsTopics(): HasMany
    {
        return $this->hasMany(NewsTopic::class)->orderBy('sort_order')->orderBy('id');
    }

    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    public static function defaultSection(): ?self
    {
        return self::query()
            ->where('is_default', true)
            ->orWhere('slug', 'fifa-2026')
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->first();
    }
}
