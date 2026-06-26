<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'visitor_id',
    'first_ip',
    'last_ip',
    'first_user_agent',
    'last_user_agent',
    'country',
    'city',
    'device_type',
    'browser',
    'platform',
    'first_seen_at',
    'last_seen_at',
    'timezone',
])]
class Visitor extends Model
{
    use HasFactory;

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class, 'visitor_id', 'visitor_id');
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(VisitorPageView::class, 'visitor_id', 'visitor_id');
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(VisitorDailyStat::class, 'visitor_id', 'visitor_id');
    }
}
