<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'visitor_id',
    'visit_date',
    'page_views',
    'first_visit_at',
    'last_visit_at',
])]
class VisitorDailyStat extends Model
{
    use HasFactory;

    protected $casts = [
        'visit_date' => 'date',
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'visitor_id');
    }
}
