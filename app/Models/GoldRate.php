<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_date',
        'city',
        'state',
        'purity',
        'price_1g',
        'price_8g',
        'price_10g',
        'change_amount',
        'change_percent',
        'source',
        'source_url',
        'is_pending_review',
        'fetched_at',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'price_1g' => 'decimal:2',
        'price_8g' => 'decimal:2',
        'price_10g' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'change_percent' => 'decimal:2',
        'is_pending_review' => 'boolean',
        'fetched_at' => 'datetime',
    ];
}
