@extends('layouts.app')

@section('title', 'Admin Analytics - World Cup News Explorer')

@php
    $viewRank = $analyticsSummary['daily_rank'];
    $masterRank = $analyticsSummary['master_rank'];
    $trendsAssessment = $trendsAnalyticsSummary['assessment'];

    $breakdownCards = [
        ['title' => 'Devices', 'items' => $visitorSnapshot['device_breakdown'], 'tone' => 'emerald'],
        ['title' => 'Browsers', 'items' => $visitorSnapshot['browser_breakdown'], 'tone' => 'sky'],
        ['title' => 'Platforms', 'items' => $visitorSnapshot['platform_breakdown'], 'tone' => 'slate'],
        ['title' => 'Countries', 'items' => $visitorSnapshot['country_breakdown'], 'tone' => 'amber'],
    ];

    $chartCards = [
        ['key' => 'live_users', 'tone' => 'emerald'],
        ['key' => 'news_total', 'tone' => 'sky'],
    ];
    $contentChartCards = [
        ['key' => 'hourly_publish', 'tone' => 'amber'],
        ['key' => 'daily_publish', 'tone' => 'sky'],
    ];

    $rankToneClasses = match ($viewRank['tone']) {
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
        'yellow' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };

    $trendsAssessmentClasses = match ($trendsAssessment['tone']) {
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Analytics</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Traffic intelligence</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Live visitors, device mix, IP activity, and article performance in a cleaner admin layout.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-colors shadow-sm">
                Back To Dashboard
            </a>
            <a href="{{ route('admin.destroy') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-bold transition-colors shadow-sm">
                Open Destroy Page
            </a>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-2" id="analytics-tabs">
        <button type="button" data-tab-target="overview" class="analytics-tab inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-950 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
            Overview
        </button>
        <button type="button" data-tab-target="trends" class="analytics-tab inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
            Google Trends
        </button>
        <button type="button" data-tab-target="content" class="analytics-tab inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
            Content
        </button>
    </div>

    <div data-tab-panel="overview">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 mb-4">
        <div class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 text-white shadow-lg shadow-emerald-500/15">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-50/80">Live Now</p>
            <p class="mt-3 text-4xl font-black">{{ number_format($visitorSnapshot['live_now_count']) }}</p>
            <p class="mt-2 text-xs text-emerald-50/90">Visitors active in the last 5 minutes.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Site Views</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($visitStats['page_views_total']) }}</p>
            <p class="mt-1 text-xs text-slate-500">All public page loads across the site.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Public Visits</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($visitStats['total']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Deduped public page visits, not every rapid refresh.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Site Views Today</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($visitStats['page_views_today']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Public page loads recorded today.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Visits Today</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($visitStats['today']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Deduped visits recorded today.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Unique Today</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ number_format($visitStats['unique_today']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Distinct fingerprints seen today.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 mb-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Article Views</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($analyticsSummary['article_views']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Deduped article impressions across public feeds.</p>
        </div>
        <div class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-600">Conversions</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ number_format($analyticsSummary['article_clicks']) }}</p>
            <p class="mt-1 text-xs text-amber-600/80">Outbound article link clicks.</p>
            <p class="mt-1.5 text-sm font-black text-amber-800">{{ $analyticsSummary['conversion']['overall_rate'] }}% <span class="text-xs font-semibold text-amber-600">overall rate</span></p>
        </div>
    </div>

    {{-- Conversion Rate Breakdown --}}
    @php
        $conv = $analyticsSummary['conversion'];
        $periods = [
            ['label' => 'Today',        'key' => 'today', 'tone' => 'emerald'],
            ['label' => 'This Week',    'key' => 'week',  'tone' => 'sky'],
            ['label' => 'This Month',   'key' => 'month', 'tone' => 'violet'],
        ];
    @endphp
    <div class="mb-6 rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/60">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Conversion Rate</p>
                <h2 class="mt-0.5 text-base font-extrabold text-slate-900">Views → Conversions by Period</h2>
            </div>
            <span class="rounded-full border border-amber-200 bg-amber-50 px-4 py-1.5 text-sm font-black text-amber-700">{{ $conv['overall_rate'] }}% all-time</span>
        </div>
        <div class="px-5 pt-4 text-xs text-slate-500">
            Period cards use per-day tracked article metrics, so one old article click no longer inflates the full day or week total.
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
            @foreach($periods as $p)
                @php
                    $d = $conv[$p['key']];
                    $barWidth = min(100, max(4, $d['rate'] * 6));
                    $toneBar   = match($p['tone']) { 'sky' => 'bg-sky-500', 'violet' => 'bg-violet-500', default => 'bg-emerald-500' };
                    $toneBadge = match($p['tone']) { 'sky' => 'bg-sky-50 text-sky-700 border-sky-200', 'violet' => 'bg-violet-50 text-violet-700 border-violet-200', default => 'bg-emerald-50 text-emerald-700 border-emerald-200' };
                    $toneNum   = match($p['tone']) { 'sky' => 'text-sky-700', 'violet' => 'text-violet-700', default => 'text-emerald-700' };
                @endphp
                <div class="px-5 py-5">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $p['label'] }}</p>
                    <p class="mt-2 text-3xl font-black {{ $toneNum }}">{{ $d['rate'] }}<span class="text-lg">%</span></p>
                    <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full {{ $toneBar }} transition-all duration-700" style="width: {{ $barWidth }}%"></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="text-center">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Views</p>
                            <p class="text-sm font-extrabold text-slate-800">{{ number_format($d['views']) }}</p>
                        </div>
                        <span class="text-slate-300 text-lg font-light">→</span>
                        <div class="text-center">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Conversions</p>
                            <p class="text-sm font-extrabold text-amber-600">{{ number_format($d['clicks']) }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $toneBadge }}">{{ $d['rate'] }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <section class="mb-8 rounded-[2rem] border px-5 py-5 shadow-sm {{ $rankToneClasses }}">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] opacity-80">Daily View Rank</p>
                <h2 class="mt-2 text-3xl font-black">{{ $viewRank['tier'] }}</h2>
                <p class="mt-2 text-sm opacity-80">Today&apos;s range: {{ $viewRank['range'] }}. This ladder resets every new day.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:min-w-[520px]">
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-5 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Today&apos;s Views</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['daily_views']) }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-5 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Master Points</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['master_points']) }}</p>
                    <p class="mt-1 text-xs opacity-70">{{ $masterRank['tier'] }}</p>
                </div>
                <a href="{{ route('admin.analytics.ranking') }}" class="rounded-[1.6rem] border border-white/40 bg-slate-950 px-5 py-4 text-white shadow-sm transition hover:bg-slate-800">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/70">Open Detail Page</p>
                    <p class="mt-2 text-base font-black">Signalz XP Dashboard</p>
                    <p class="mt-1 text-xs text-white/75">Open missions, streaks, weekly growth, and lifetime rank.</p>
                </a>
            </div>
        </div>
    </section>

    <section class="mb-8 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Daily Graph Switch</p>
                <h2 class="mt-1 text-xl font-extrabold text-slate-950">Mobile-friendly daily activity charts</h2>
                <p class="mt-1 text-xs text-slate-500">Switch between active users and published stories without stacking extra cards.</p>
            </div>
            <div class="flex flex-wrap gap-2" id="analytics-graph-switch">
                @foreach($chartCards as $chartCard)
                    @php
                        $chart = $analyticsCharts[$chartCard['key']];
                    @endphp
                    <button type="button" data-graph-target="{{ $chartCard['key'] }}" class="analytics-graph-tab inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-100 {{ $loop->first ? 'bg-slate-950 text-white hover:bg-slate-800' : '' }}">
                        {{ $chart['title'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-5 sm:p-6">
            @foreach($chartCards as $chartCard)
                @php
                    $chart = $analyticsCharts[$chartCard['key']];
                    $barTone = match ($chartCard['tone']) {
                        'emerald' => 'bg-emerald-500',
                        'sky' => 'bg-sky-500',
                        'amber' => 'bg-amber-500',
                        default => 'bg-slate-500',
                    };
                    $badgeTone = match ($chartCard['tone']) {
                        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
                        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                    };
                @endphp
                <div data-graph-panel="{{ $chartCard['key'] }}" class="{{ $loop->first ? '' : 'hidden' }}">
                    <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] lg:items-start">
                        <div class="rounded-[1.7rem] border border-slate-200 bg-slate-50 p-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $chart['subtitle'] }}</p>
                            <p class="mt-4 text-4xl font-black text-slate-950">{{ number_format($chart['headline']) }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $chart['headline_label'] }}</p>
                            <div class="mt-4 inline-flex rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] {{ $badgeTone }}">
                                {{ number_format($chart['total']) }} total
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-4 sm:p-5">
                            <div class="flex h-52 items-end gap-2 sm:gap-3">
                                @foreach($chart['points'] as $point)
                                    @php
                                        $height = max(12, (int) round(($point['value'] / $chart['max']) * 100));
                                    @endphp
                                    <div class="flex min-w-0 flex-1 flex-col items-center">
                                        <div class="flex h-36 w-full items-end rounded-[1.25rem] bg-slate-50 px-1.5 pb-1.5">
                                            <div class="w-full rounded-[1rem] {{ $barTone }}" style="height: {{ $height }}%;"></div>
                                        </div>
                                        <p class="mt-3 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $point['label'] }}</p>
                                        <p class="mt-1 text-center text-xs font-extrabold text-slate-700">{{ number_format($point['value']) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 2xl:grid-cols-[1.15fr_0.85fr] gap-6 mb-8">
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-200 bg-slate-50/70">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Live People On Site</h2>
                    <p class="text-xs text-slate-500 mt-1">Updated from visitor heartbeat events.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ number_format($visitorSnapshot['live_now_count']) }} active
                </span>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse($visitorSnapshot['live_now'] as $visitor)
                    <div class="p-4 sm:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-white">{{ $visitor->device_type ?: 'Unknown' }}</span>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-600">{{ $visitor->browser_name ?: 'Unknown' }}</span>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-600">{{ $visitor->os_name ?: 'Unknown' }}</span>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-900 break-all">{{ $visitor->ip_address ?: 'IP unavailable' }}</p>
                                <p class="mt-1 text-xs text-slate-500 break-all">{{ $visitor->page_path ?: '/world-cup-news' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 sm:min-w-[220px]">
                                <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Country</p>
                                    <p class="mt-1 font-bold text-slate-800">{{ $visitor->country_code ?: 'Unknown' }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Timezone</p>
                                    <p class="mt-1 font-bold text-slate-800 truncate">{{ $visitor->timezone ?: 'Unknown' }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Hits</p>
                                    <p class="mt-1 font-bold text-slate-800">{{ number_format($visitor->visit_count) }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Last Seen</p>
                                    <p class="mt-1 font-bold text-slate-800">{{ optional($visitor->last_seen_at)?->diffForHumans() ?? 'Unknown' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-sm text-slate-500">No live visitors in the last 5 minutes.</div>
                @endforelse
            </div>
        </section>

        <section class="space-y-4">
            @foreach($breakdownCards as $card)
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900">{{ $card['title'] }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse(array_slice($card['items'], 0, 6) as $item)
                            <div>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-semibold text-slate-700">{{ $item['label'] }}</span>
                                    <span class="font-bold text-slate-900">{{ number_format($item['total']) }}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full
                                        @if($card['tone'] === 'emerald') bg-emerald-500
                                        @elseif($card['tone'] === 'sky') bg-sky-500
                                        @elseif($card['tone'] === 'amber') bg-amber-500
                                        @else bg-slate-500 @endif"
                                        style="width: {{ max(8, min(100, $visitStats['unique_today'] > 0 ? ($item['total'] / $visitStats['unique_today']) * 100 : 0)) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No visitor data recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </section>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Top Viewed News</h2>
                    <p class="text-xs text-slate-500 mt-1">Highest loaded cards across the site.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ number_format($analyticsSummary['article_views']) }} total</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($analyticsSummary['top_viewed'] as $article)
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $loop->iteration }}</p>
                            <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                            <p class="text-[11px] text-slate-500 mt-1">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-emerald-600">{{ number_format($article->views_count) }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Top Conversions</h2>
                    <p class="text-xs text-slate-500 mt-1">Stories generating the most outbound traffic.</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">{{ number_format($analyticsSummary['article_clicks']) }} total conversions</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($analyticsSummary['top_clicked'] as $article)
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $loop->iteration }}</p>
                            <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                            <p class="text-[11px] text-slate-500 mt-1">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-amber-600">{{ number_format($article->clicks_count) }} conv.</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
    </div>

    <div data-tab-panel="trends" class="hidden">
        @php
            $trendsConv = $trendsAnalyticsSummary['conversion'];
            $trendPeriods = [
                ['label' => 'Today', 'key' => 'today', 'tone' => 'emerald'],
                ['label' => 'This Week', 'key' => 'week', 'tone' => 'sky'],
                ['label' => 'This Month', 'key' => 'month', 'tone' => 'violet'],
            ];
        @endphp

        <div class="grid grid-cols-2 xl:grid-cols-5 gap-3 sm:gap-4 mb-6">
            <div class="col-span-2 rounded-3xl border p-5 shadow-sm {{ $trendsAssessmentClasses }}">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] opacity-80">Trend Conversion</p>
                <p class="mt-3 text-4xl font-black">{{ $trendsAssessment['label'] }}</p>
                <p class="mt-2 text-xs opacity-80">{{ $trendsAssessment['message'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Trend Views</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($trendsAnalyticsSummary['article_views']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Google Trends article impressions.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Trend Conversions</p>
                <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ number_format($trendsAnalyticsSummary['article_clicks']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Outbound clicks from trend articles.</p>
            </div>
            <div class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700/80">Overall Rate</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ number_format($trendsConv['overall_rate'], 2) }}%</p>
                <p class="mt-1 text-xs text-emerald-700/70">Trend views to trend conversions.</p>
            </div>
        </div>

        <div class="mb-6 rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Google Trends Conversion</p>
                    <h2 class="mt-0.5 text-base font-extrabold text-slate-900">Trend views → conversions by period</h2>
                </div>
                <span class="rounded-full border border-amber-200 bg-amber-50 px-4 py-1.5 text-sm font-black text-amber-700">{{ number_format($trendsConv['overall_rate'], 2) }}% all-time</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                @foreach($trendPeriods as $p)
                    @php
                        $d = $trendsConv[$p['key']];
                        $barWidth = min(100, max(4, $d['rate'] * 6));
                        $toneBar   = match($p['tone']) { 'sky' => 'bg-sky-500', 'violet' => 'bg-violet-500', default => 'bg-emerald-500' };
                        $toneBadge = match($p['tone']) { 'sky' => 'bg-sky-50 text-sky-700 border-sky-200', 'violet' => 'bg-violet-50 text-violet-700 border-violet-200', default => 'bg-emerald-50 text-emerald-700 border-emerald-200' };
                        $toneNum   = match($p['tone']) { 'sky' => 'text-sky-700', 'violet' => 'text-violet-700', default => 'text-emerald-700' };
                    @endphp
                    <div class="px-5 py-5">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $p['label'] }}</p>
                        <p class="mt-2 text-3xl font-black {{ $toneNum }}">{{ number_format($d['rate'], 2) }}<span class="text-lg">%</span></p>
                        <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $toneBar }}" style="width: {{ $barWidth }}%"></div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <div class="text-center">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Views</p>
                                <p class="text-sm font-extrabold text-slate-800">{{ number_format($d['views']) }}</p>
                            </div>
                            <span class="text-slate-300 text-lg font-light">→</span>
                            <div class="text-center">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Conversions</p>
                                <p class="text-sm font-extrabold text-amber-600">{{ number_format($d['clicks']) }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $toneBadge }}">{{ number_format($d['rate'], 2) }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Top Viewed Trends</h2>
                        <p class="text-xs text-slate-500 mt-1">Trend stories that got the most impressions.</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ number_format($trendsAnalyticsSummary['article_views']) }} total</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($trendsAnalyticsSummary['top_viewed'] as $article)
                        <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $loop->iteration }}</p>
                                <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                                <p class="text-[11px] text-slate-500 mt-1">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-bold text-emerald-600">{{ number_format($article->views_count) }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            No trend article analytics recorded yet.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Top Trend Conversions</h2>
                        <p class="text-xs text-slate-500 mt-1">Trend stories generating the most outbound clicks.</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">{{ number_format($trendsAnalyticsSummary['article_clicks']) }} total</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($trendsAnalyticsSummary['top_clicked'] as $article)
                        <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $loop->iteration }}</p>
                                <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                                <p class="text-[11px] text-slate-500 mt-1">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-bold text-amber-600">{{ number_format($article->clicks_count) }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            No trend conversions recorded yet.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <div data-tab-panel="content" class="hidden">
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="col-span-2 rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-500 to-cyan-500 p-5 text-white shadow-lg shadow-sky-500/15">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-50/80">Content Inventory</p>
                <p class="mt-3 text-4xl font-black">{{ number_format($contentAnalytics['total_posts']) }}</p>
                <p class="mt-2 text-xs text-sky-50/90">Total internal news posts currently stored on the site.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Today&apos;s Posts</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($contentAnalytics['today_posts']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Stories published since midnight.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Last Hour</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($contentAnalytics['last_hour_posts']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Fresh posts added in the last 60 minutes.</p>
            </div>
            <div class="rounded-3xl border border-emerald-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700/80">Visible</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ number_format($contentAnalytics['visible_posts']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Posts currently visible on the public site.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Hidden</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($contentAnalytics['hidden_posts']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Stored posts that are not public right now.</p>
            </div>
            <div class="rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50 to-orange-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-rose-700/80">Destroyed Total</p>
                <p class="mt-2 text-3xl font-extrabold text-rose-700">{{ number_format($contentAnalytics['destroyed_total']) }}</p>
                <p class="mt-1 text-xs text-rose-700/70">Cumulative deleted posts from prune and manual destroy actions.</p>
            </div>
            <div class="rounded-3xl border border-amber-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-700/80">Destroy Ready</p>
                <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ number_format($contentAnalytics['destroy_ready']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Posts currently eligible for the destroy routine.</p>
            </div>
        </div>

        <section class="mb-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Publishing Graph Switch</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-950">Content flow and publish velocity</h2>
                    <p class="mt-1 text-xs text-slate-500">Switch between last-hour velocity and the 7-day publishing trend.</p>
                </div>
                <div class="flex flex-wrap gap-2" id="content-graph-switch">
                    @foreach($contentChartCards as $chartCard)
                        @php($chart = $contentCharts[$chartCard['key']])
                        <button type="button" data-content-graph-target="{{ $chartCard['key'] }}" class="content-graph-tab inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-100 {{ $loop->first ? 'bg-slate-950 text-white hover:bg-slate-800' : '' }}">
                            {{ $chart['title'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="p-5 sm:p-6">
                @foreach($contentChartCards as $chartCard)
                    @php
                        $chart = $contentCharts[$chartCard['key']];
                        $barTone = $chartCard['tone'] === 'amber' ? 'bg-amber-500' : 'bg-sky-500';
                        $badgeTone = $chartCard['tone'] === 'amber'
                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : 'bg-sky-50 text-sky-700 border-sky-200';
                    @endphp
                    <div data-content-graph-panel="{{ $chartCard['key'] }}" class="{{ $loop->first ? '' : 'hidden' }}">
                        <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] lg:items-start">
                            <div class="rounded-[1.7rem] border border-slate-200 bg-slate-50 p-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $chart['subtitle'] }}</p>
                                <p class="mt-4 text-4xl font-black text-slate-950">{{ number_format($chart['headline']) }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $chart['headline_label'] }}</p>
                                <div class="mt-4 inline-flex rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] {{ $badgeTone }}">
                                    {{ number_format($chart['total']) }} total
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-4 sm:p-5">
                                <div class="flex h-52 items-end gap-2 sm:gap-3">
                                    @foreach($chart['points'] as $point)
                                        @php($height = max(12, (int) round(($point['value'] / $chart['max']) * 100)))
                                        <div class="flex min-w-0 flex-1 flex-col items-center">
                                            <div class="flex h-36 w-full items-end rounded-[1.25rem] bg-slate-50 px-1.5 pb-1.5">
                                                <div class="w-full rounded-[1rem] {{ $barTone }}" style="height: {{ $height }}%;"></div>
                                            </div>
                                            <p class="mt-3 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $point['label'] }}</p>
                                            <p class="mt-1 text-center text-xs font-extrabold text-slate-700">{{ number_format($point['value']) }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Content Health</h2>
                        <p class="text-xs text-slate-500 mt-1">Publishing and cleanup numbers that matter for the feed.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-700">{{ number_format($contentAnalytics['featured_posts']) }} featured</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Favorites</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($contentAnalytics['favorite_posts']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Trend Posts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($contentAnalytics['trend_posts']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Extracted</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($contentAnalytics['extracted_posts']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Protected Old</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($contentAnalytics['destroy_protected']) }}</p>
                    </div>
                    <div class="col-span-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-rose-700/80">Last Destroy Run</p>
                        <p class="mt-2 text-2xl font-black text-rose-700">{{ number_format($contentAnalytics['destroyed_last_run']) }}</p>
                        <p class="mt-1 text-xs text-rose-700/70">Deleted in the most recent automatic or manual prune cycle.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Top Sections By Posts</h2>
                        <p class="text-xs text-slate-500 mt-1">Which news sections hold the biggest inventory.</p>
                    </div>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($contentAnalytics['top_sections'] as $section)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-slate-700">{{ $section->name }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($section->news_items_count) }}</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-sky-500" style="width: {{ max(8, min(100, $contentAnalytics['total_posts'] > 0 ? ($section->news_items_count / $contentAnalytics['total_posts']) * 100 : 0)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No section data recorded yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Latest Posts</h2>
                        <p class="text-xs text-slate-500 mt-1">Newest internal news cards currently on the site.</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                    @forelse($contentAnalytics['latest_posts'] as $article)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $article->newsSection?->name }} · {{ $article->newsTopic?->name }}</p>
                                    <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ optional($article->published_at)->diffForHumans() ?? 'Unknown' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-600 shadow-sm">{{ $article->is_visible ? 'Live' : 'Hidden' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            No posts recorded yet.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabButtons = Array.from(document.querySelectorAll('.analytics-tab'));
        const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));

        if (tabButtons.length === 0 || panels.length === 0) {
            return;
        }

        const activateTab = (target) => {
            tabButtons.forEach((button) => {
                const active = button.dataset.tabTarget === target;
                button.classList.toggle('bg-slate-950', active);
                button.classList.toggle('text-white', active);
                button.classList.toggle('bg-white', !active);
                button.classList.toggle('text-slate-700', !active);
            });

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.tabPanel !== target);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => activateTab(button.dataset.tabTarget));
        });

        activateTab('overview');

        const graphButtons = Array.from(document.querySelectorAll('.analytics-graph-tab'));
        const graphPanels = Array.from(document.querySelectorAll('[data-graph-panel]'));

        const activateGraph = (target) => {
            graphButtons.forEach((button) => {
                const active = button.dataset.graphTarget === target;
                button.classList.toggle('bg-slate-950', active);
                button.classList.toggle('text-white', active);
                button.classList.toggle('hover:bg-slate-800', active);
                button.classList.toggle('bg-white', !active);
                button.classList.toggle('text-slate-700', !active);
            });

            graphPanels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.graphPanel !== target);
            });
        };

        graphButtons.forEach((button) => {
            button.addEventListener('click', () => activateGraph(button.dataset.graphTarget));
        });

        if (graphButtons.length > 0) {
            activateGraph(graphButtons[0].dataset.graphTarget);
        }

        const contentGraphButtons = Array.from(document.querySelectorAll('.content-graph-tab'));
        const contentGraphPanels = Array.from(document.querySelectorAll('[data-content-graph-panel]'));

        const activateContentGraph = (target) => {
            contentGraphButtons.forEach((button) => {
                const active = button.dataset.contentGraphTarget === target;
                button.classList.toggle('bg-slate-950', active);
                button.classList.toggle('text-white', active);
                button.classList.toggle('hover:bg-slate-800', active);
                button.classList.toggle('bg-white', !active);
                button.classList.toggle('text-slate-700', !active);
            });

            contentGraphPanels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.contentGraphPanel !== target);
            });
        };

        contentGraphButtons.forEach((button) => {
            button.addEventListener('click', () => activateContentGraph(button.dataset.contentGraphTarget));
        });

        if (contentGraphButtons.length > 0) {
            activateContentGraph(contentGraphButtons[0].dataset.contentGraphTarget);
        }
    });
</script>
@endsection
