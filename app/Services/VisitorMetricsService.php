<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\VisitorAnalytic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitorMetricsService
{
    protected int $liveWindowMinutes = 5;

    public function recordPublicVisit(Request $request): array
    {
        $todayKey = 'visits_public_' . now()->toDateString();
        $total = ((int) Setting::get('visits_public_total', '0')) + 1;
        $today = ((int) Setting::get($todayKey, '0')) + 1;

        Setting::set('visits_public_total', (string) $total);
        Setting::set($todayKey, (string) $today);
        Setting::set('visits_public_last_seen_at', now()->toIso8601String());

        $fingerprint = $this->fingerprint($request);
        $todayDate = now()->toDateString();

        $visitor = VisitorAnalytic::where('fingerprint', $fingerprint)
            ->whereDate('visit_date', $todayDate)
            ->first();

        if (!$visitor) {
            $visitor = new VisitorAnalytic([
                'fingerprint' => $fingerprint,
                'visit_date' => $todayDate,
                'visit_count' => 0,
            ]);
        }

        $visitor->fill(array_merge(
            $this->parseRequestContext($request),
            [
                'ip_address' => $request->ip(),
                'last_seen_at' => now(),
                'visit_count' => ((int) $visitor->visit_count) + 1,
            ]
        ));
        $visitor->save();

        return $this->getPublicStats();
    }

    public function getPublicStats(): array
    {
        return [
            'total' => (int) Setting::get('visits_public_total', '0'),
            'today' => (int) Setting::get('visits_public_' . now()->toDateString(), '0'),
            'unique_today' => VisitorAnalytic::whereDate('visit_date', now()->toDateString())->count(),
            'unique_total' => VisitorAnalytic::distinct('fingerprint')->count('fingerprint'),
            'live_now' => $this->liveVisitorsQuery()->count(),
            'last_seen_at' => Setting::get('visits_public_last_seen_at'),
        ];
    }

    public function updateClientContext(Request $request): void
    {
        $request->validate([
            'timezone' => 'nullable|string|max:64',
            'country_code' => 'nullable|string|max:8',
            'page_path' => 'nullable|string|max:255',
        ]);

        $visitor = VisitorAnalytic::query()
            ->where('fingerprint', $this->fingerprint($request))
            ->whereDate('visit_date', now()->toDateString())
            ->first();

        if (!$visitor) {
            $visitor = new VisitorAnalytic([
                'fingerprint' => $this->fingerprint($request),
                'visit_date' => now()->toDateString(),
                'visit_count' => 1,
            ]);
        }

        $visitor->fill(array_merge(
            $this->parseRequestContext($request),
            [
                'ip_address' => $request->ip(),
                'timezone' => $request->string('timezone')->toString() ?: null,
                'country_code' => strtoupper($request->string('country_code')->toString()) ?: null,
                'page_path' => $request->string('page_path')->toString() ?: null,
                'last_seen_at' => now(),
            ]
        ));

        $visitor->save();
    }

    public function articleAnalyticsSummary(): array
    {
        return [
            'article_views' => (int) DB::table('news_items')->sum('views_count'),
            'article_clicks' => (int) DB::table('news_items')->sum('clicks_count'),
        ];
    }

    public function adminAnalyticsSnapshot(): array
    {
        $today = now()->toDateString();
        $recentVisitors = VisitorAnalytic::query()
            ->whereDate('visit_date', $today)
            ->orderByDesc('last_seen_at')
            ->take(20)
            ->get();

        return [
            'live_now' => $this->liveVisitorsQuery()
                ->orderByDesc('last_seen_at')
                ->take(20)
                ->get(),
            'live_now_count' => $this->liveVisitorsQuery()->count(),
            'recent_visitors' => $recentVisitors,
            'device_breakdown' => $this->breakdownForToday('device_type', ['Desktop', 'Mobile', 'Tablet', 'Bot', 'Other']),
            'browser_breakdown' => $this->breakdownForToday('browser_name'),
            'platform_breakdown' => $this->breakdownForToday('os_name'),
            'country_breakdown' => $this->breakdownForToday('country_code'),
        ];
    }

    protected function fingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip() ?? 'unknown',
            substr((string) $request->userAgent(), 0, 255),
        ]));
    }

    protected function liveVisitorsQuery()
    {
        return VisitorAnalytic::query()
            ->whereDate('visit_date', now()->toDateString())
            ->where('last_seen_at', '>=', now()->subMinutes($this->liveWindowMinutes));
    }

    protected function breakdownForToday(string $column, array $preferredOrder = []): array
    {
        $rows = VisitorAnalytic::query()
            ->selectRaw("COALESCE(NULLIF({$column}, ''), 'Unknown') as label, COUNT(*) as total")
            ->whereDate('visit_date', now()->toDateString())
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'total' => (int) $row->total,
            ])
            ->all();

        if ($preferredOrder === []) {
            return $rows;
        }

        $ordered = [];
        foreach ($preferredOrder as $label) {
            foreach ($rows as $index => $row) {
                if ($row['label'] === $label) {
                    $ordered[] = $row;
                    unset($rows[$index]);
                }
            }
        }

        return array_values([...$ordered, ...$rows]);
    }

    protected function parseRequestContext(Request $request): array
    {
        $userAgent = substr((string) $request->userAgent(), 0, 1000);

        return [
            'device_type' => $this->detectDeviceType($userAgent),
            'browser_name' => $this->detectBrowserName($userAgent),
            'os_name' => $this->detectOsName($userAgent),
            'user_agent' => $userAgent ?: null,
        ];
    }

    protected function detectDeviceType(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        if ($agent === '') {
            return 'Unknown';
        }

        if (str_contains($agent, 'bot') || str_contains($agent, 'crawl') || str_contains($agent, 'spider')) {
            return 'Bot';
        }

        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'Tablet';
        }

        if (str_contains($agent, 'mobile') || str_contains($agent, 'iphone') || str_contains($agent, 'android')) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    protected function detectBrowserName(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'edg/') => 'Edge',
            str_contains($agent, 'opr/') || str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'chrome/') && !str_contains($agent, 'edg/') => 'Chrome',
            str_contains($agent, 'firefox/') => 'Firefox',
            str_contains($agent, 'safari/') && !str_contains($agent, 'chrome/') => 'Safari',
            str_contains($agent, 'samsungbrowser/') => 'Samsung Internet',
            str_contains($agent, 'curl/') => 'cURL',
            $agent === '' => 'Unknown',
            default => 'Other',
        };
    }

    protected function detectOsName(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'iphone') || str_contains($agent, 'ipad') || str_contains($agent, 'ios') => 'iOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'mac os x') || str_contains($agent, 'macintosh') => 'macOS',
            str_contains($agent, 'linux') => 'Linux',
            str_contains($agent, 'cros') => 'ChromeOS',
            $agent === '' => 'Unknown',
            default => 'Other',
        };
    }
}
