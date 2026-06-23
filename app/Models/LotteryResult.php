<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LotteryResult extends Model
{
    protected $fillable = [
        'lottery_name',
        'lottery_code',
        'draw_number',
        'result_date',
        'slug',
        'status',
        'official_pdf_url',
        'local_pdf_path',
        'source_url',
        'first_prize_ticket',
        'first_prize_amount',
        'second_prize_ticket',
        'second_prize_amount',
        'third_prize_ticket',
        'third_prize_amount',
        'consolation_prizes',
        'other_prizes',
        'raw_text',
        'parsed_at',
        'last_fetch_at',
    ];

    protected function casts(): array
    {
        return [
            'result_date' => 'date',
            'consolation_prizes' => 'array',
            'other_prizes' => 'array',
            'parsed_at' => 'datetime',
            'last_fetch_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $result): void {
            $result->slug = $result->slug ?: $result->makeSlug();
        });
    }

    public function makeSlug(): string
    {
        $name = Str::slug($this->lottery_name ?: 'kerala-lottery');
        $draw = Str::slug($this->draw_number ?: 'result');
        $date = optional($this->result_date)->format('d-m-Y') ?: now()->format('d-m-Y');

        return trim("{$name}-{$draw}-result-{$date}", '-');
    }

    public function hasParsedPrizes(): bool
    {
        return filled($this->first_prize_ticket) || filled($this->second_prize_ticket) || filled($this->third_prize_ticket);
    }
}
