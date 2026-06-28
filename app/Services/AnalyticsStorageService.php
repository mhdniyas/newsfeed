<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalyticsStorageService
{
    protected bool $useRedis = true;

    public function __construct()
    {
        // Check if Redis is available and configured
        try {
            $this->useRedis = config('database.redis.client') !== null && Redis::connection() !== null;
        } catch (\Throwable) {
            $this->useRedis = false;
        }
    }

    /**
     * Increment a live counter.
     */
    public function increment(string $key, int $amount = 1, int $ttl = 1800): void
    {
        if ($this->useRedis) {
            try {
                Redis::incrby($key, $amount);
                Redis::expire($key, $ttl);
                return;
            } catch (\Throwable $e) {
                Log::warning("AnalyticsStorageService: Redis connection failed, falling back: " . $e->getMessage());
                $this->useRedis = false;
            }
        }

        // Emulate via Cache
        $val = (int) Cache::get($key, 0);
        Cache::put($key, $val + $amount, now()->addSeconds($ttl));
    }

    /**
     * Add member to active sorted set (with timestamp score).
     */
    public function activeRegister(string $key, string $member, int $timestamp, int $ttl = 1800): void
    {
        if ($this->useRedis) {
            try {
                Redis::zadd($key, $timestamp, $member);
                Redis::expire($key, $ttl);
                return;
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        // Emulate sorted set in Cache
        $data = Cache::get($key, []);
        $data[$member] = $timestamp;
        Cache::put($key, $data, now()->addSeconds($ttl));
    }

    /**
     * Get active count (removing expired members).
     */
    public function getActiveCount(string $key, int $expiryThresholdTime): int
    {
        if ($this->useRedis) {
            try {
                Redis::zremrangebyscore($key, 0, $expiryThresholdTime);
                return (int) Redis::zcard($key);
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        // Emulate sorted set get active count
        $data = Cache::get($key, []);
        $active = [];
        foreach ($data as $member => $time) {
            if ($time > $expiryThresholdTime) {
                $active[$member] = $time;
            }
        }
        Cache::put($key, $active, now()->addMinutes(30));
        return count($active);
    }

    /**
     * Track live top lists (increment by key and member).
     */
    public function recordTopItem(string $key, string $member, float $amount = 1.0, int $ttl = 1800): void
    {
        if ($this->useRedis) {
            try {
                Redis::zincrby($key, $amount, $member);
                Redis::expire($key, $ttl);
                return;
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        // Emulate Zincrby in Cache
        $data = Cache::get($key, []);
        $data[$member] = ($data[$member] ?? 0.0) + $amount;
        arsort($data);
        Cache::put($key, $data, now()->addSeconds($ttl));
    }

    /**
     * Retrieve top items list.
     */
    public function getTopItems(string $key, int $limit = 10): array
    {
        if ($this->useRedis) {
            try {
                $results = Redis::zrevrange($key, 0, $limit - 1, 'WITHSCORES');
                // Return mapped array of member => score
                if (!empty($results) && is_array($results)) {
                    // Some phpredis versions return associative, others indexed
                    return $results;
                }
                return [];
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        // Emulate Zincrby results in Cache
        $data = Cache::get($key, []);
        return array_slice($data, 0, $limit, true);
    }

    /**
     * Push visit detail JSON string to a recent logs list.
     */
    public function pushRecentVisit(string $key, array $visitDetails, int $limit = 50): void
    {
        $json = json_encode($visitDetails);

        if ($this->useRedis) {
            try {
                Redis::lpush($key, $json);
                Redis::ltrim($key, 0, $limit - 1);
                Redis::expire($key, 3600);
                return;
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        // Emulate recent list in Cache
        $list = Cache::get($key, []);
        array_unshift($list, $json);
        $list = array_slice($list, 0, $limit);
        Cache::put($key, $list, now()->addMinutes(60));
    }

    /**
     * Fetch recent visit details.
     */
    public function getRecentVisits(string $key, int $limit = 50): array
    {
        if ($this->useRedis) {
            try {
                $raw = Redis::lrange($key, 0, $limit - 1);
                if (!empty($raw) && is_array($raw)) {
                    return array_map(fn($item) => json_decode($item, true), $raw);
                }
                return [];
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        // Emulate list range
        $list = Cache::get($key, []);
        return array_map(fn($item) => json_decode($item, true), array_slice($list, 0, $limit));
    }

    /**
     * Retrieve simple key value (e.g. counter).
     */
    public function getCount(string $key): int
    {
        if ($this->useRedis) {
            try {
                return (int) Redis::get($key);
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        return (int) Cache::get($key, 0);
    }

    /**
     * Flush/clear keys pattern or specific key.
     */
    public function delete(string $key): void
    {
        if ($this->useRedis) {
            try {
                Redis::del($key);
                return;
            } catch (\Throwable) {
                $this->useRedis = false;
            }
        }

        Cache::forget($key);
    }
}
