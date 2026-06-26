<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'visitor_id',
    'session_id',
    'url',
    'route_name',
    'page_title',
    'visited_at',
])]
class VisitorPageView extends Model
{
    use HasFactory;

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'visitor_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'session_id', 'session_id');
    }
}
