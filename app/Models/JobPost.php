<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'company',
    'location',
    'category',
    'description',
    'extracted_body',
    'url',
    'source_name',
    'published_at',
    'is_remote',
    'is_visible',
    'slug',
    'hash',
    'views_count',
    'apply_clicks_count',
])]
class JobPost extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $job): void {
            if (!$job->hash) {
                $job->hash = self::generateHash($job->title, $job->url);
            }
            if (!$job->slug) {
                $job->slug = $job->makeSlug();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_remote' => 'boolean',
            'is_visible' => 'boolean',
            'extracted_body' => 'array',
        ];
    }

    public static function generateHash(string $title, string $url): string
    {
        return md5(trim($title) . '|' . trim($url));
    }

    public function makeSlug(): string
    {
        $base = Str::slug(Str::limit(trim((string) $this->title), 80, ''));
        return ($base !== '' ? $base : 'job') . '-' . substr((string) ($this->hash ?: self::generateHash($this->title, $this->url)), 0, 8);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeRemote(Builder $query): Builder
    {
        return $query->where('is_remote', true);
    }

    public function excerptParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', $this->extracted_body ?? [])));
    }
}
