<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'visitor_id',
    'session_id',
    'started_at',
    'ended_at',
    'duration_seconds',
    'page_views',
    'referrer',
    'landing_page',
    'exit_page',
])]
class VisitorSession extends Model
{
    use HasFactory;

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'visitor_id');
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(VisitorPageView::class, 'session_id', 'session_id');
    }
}
