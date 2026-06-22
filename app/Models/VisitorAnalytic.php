<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'fingerprint',
    'visit_date',
    'ip_address',
    'country_code',
    'timezone',
    'device_type',
    'browser_name',
    'os_name',
    'page_path',
    'user_agent',
    'visit_count',
    'last_seen_at',
])]
class VisitorAnalytic extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'last_seen_at' => 'datetime',
        ];
    }
}
