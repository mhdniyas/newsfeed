<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'news_item_id',
    'metric_date',
    'views_count',
    'clicks_count',
])]
class NewsItemDailyMetric extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
        ];
    }

    public function newsItem(): BelongsTo
    {
        return $this->belongsTo(NewsItem::class);
    }
}
