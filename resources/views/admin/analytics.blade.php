@extends('layouts.app')

@section('title', 'Admin Analytics - World Cup News Explorer')

@php
    $breakdownCards = [
        ['title' => 'Devices', 'items' => $visitorSnapshot['device_breakdown'], 'tone' => 'emerald'],
        ['title' => 'Browsers', 'items' => $visitorSnapshot['browser_breakdown'], 'tone' => 'sky'],
        ['title' => 'Platforms', 'items' => $visitorSnapshot['platform_breakdown'], 'tone' => 'slate'],
        ['title' => 'Countries', 'items' => $visitorSnapshot['country_breakdown'], 'tone' => 'amber'],
    ];

    $chartCards = [
        ['key' => 'live_users', 'tone' => 'emerald'],
        ['key' => 'news_total', 'tone' => 'sky'],
        ['key' => 'registered_users', 'tone' => 'amber'],
        ['key' => 'returning_visitors', 'tone' => 'slate'],
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Analytics</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Traffic intelligence</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Live visitors, device mix, IP activity, and article performance in a mobile-friendly layout.</p>
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

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-3 sm:gap-4 mb-8">
        <div class="col-span-2 rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 text-white shadow-lg shadow-emerald-500/15 xl:col-span-1">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-50/80">Live Now</p>
            <p class="mt-3 text-4xl font-black">{{ number_format($visitorSnapshot['live_now_count']) }}</p>
            <p class="mt-2 text-xs text-emerald-50/90">Visitors active in the last 5 minutes.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Public Visits</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($visitStats['total']) }}</p>
            <p class="mt-1 text-xs text-slate-500">All page loads on the public hub.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Today</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($visitStats['today']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Total visits recorded today.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Unique Today</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ number_format($visitStats['unique_today']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Distinct fingerprints seen today.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Article Views</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($analyticsSummary['article_views']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Loaded article card impressions.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Article Clicks</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ number_format($analyticsSummary['article_clicks']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Outbound news card clicks.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-4 gap-6 mb-8">
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
                    'emerald' => 'bg-emerald-50 text-emerald-700',
                    'sky' => 'bg-sky-50 text-sky-700',
                    'amber' => 'bg-amber-50 text-amber-700',
                    default => 'bg-slate-100 text-slate-700',
                };
            @endphp

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">{{ $chart['title'] }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $chart['subtitle'] }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] {{ $badgeTone }}">
                        {{ number_format($chart['headline']) }} {{ $chart['headline_label'] }}
                    </span>
                </div>

                <div class="mt-5 flex items-end gap-2 h-36">
                    @foreach($chart['points'] as $point)
                        @php
                            $height = max(10, (int) round(($point['value'] / $chart['max']) * 100));
                        @endphp
                        <div class="flex-1 min-w-0">
                            <div class="flex h-28 items-end">
                                <div class="w-full rounded-t-2xl {{ $barTone }}" style="height: {{ $height }}%;"></div>
                            </div>
                            <p class="mt-2 text-center text-[10px] font-bold text-slate-400">{{ $point['label'] }}</p>
                            <p class="mt-1 text-center text-xs font-bold text-slate-700">{{ number_format($point['value']) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Total</span>
                    <span class="text-lg font-extrabold text-slate-900">{{ number_format($chart['total']) }}</span>
                </div>
            </section>
        @endforeach
    </div>

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

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Top Viewed News</h2>
                    <p class="text-xs text-slate-500 mt-1">Highest loaded cards across the site.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ number_format($analyticsSummary['article_views']) }} total</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($analyticsSummary['top_viewed'] as $index => $article)
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $index + 1 }}</p>
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
                    <h2 class="text-base font-bold text-slate-900">Top Clicked News</h2>
                    <p class="text-xs text-slate-500 mt-1">Stories generating the most outbound traffic.</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">{{ number_format($analyticsSummary['article_clicks']) }} total</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($analyticsSummary['top_clicked'] as $index => $article)
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $index + 1 }}</p>
                            <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                            <p class="text-[11px] text-slate-500 mt-1">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-amber-600">{{ number_format($article->clicks_count) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70">
                <h2 class="text-base font-bold text-slate-900">Recent Visitor Details</h2>
                <p class="text-xs text-slate-500 mt-1">Today’s latest visitor records with IP, device, and route details.</p>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($visitorSnapshot['recent_visitors'] as $visitor)
                    <div class="p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 break-all">{{ $visitor->ip_address ?: 'IP unavailable' }}</p>
                                <p class="mt-1 text-xs text-slate-500 break-all">{{ $visitor->page_path ?: '/world-cup-news' }}</p>
                            </div>
                            <p class="text-xs font-medium text-slate-500">{{ optional($visitor->last_seen_at)?->format('M d, H:i:s') ?? 'Unknown' }}</p>
                        </div>
                        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Device</p>
                                <p class="mt-1 font-bold text-slate-800">{{ $visitor->device_type ?: 'Unknown' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Browser</p>
                                <p class="mt-1 font-bold text-slate-800">{{ $visitor->browser_name ?: 'Unknown' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Platform</p>
                                <p class="mt-1 font-bold text-slate-800">{{ $visitor->os_name ?: 'Unknown' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Region</p>
                                <p class="mt-1 font-bold text-slate-800">{{ $visitor->country_code ?: 'Unknown' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-sm text-slate-500">No visitor records available yet.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-slate-900">Recent Article Activity</h2>
            <p class="text-xs text-slate-500 mt-1">Latest stories receiving views or clicks.</p>
            <div class="mt-4 space-y-3">
                @foreach($analyticsSummary['recent_activity'] as $article)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                            </div>
                            <div class="text-right text-xs">
                                <p class="font-bold text-slate-900">{{ number_format($article->views_count) }} views</p>
                                <p class="mt-1 font-bold text-amber-600">{{ number_format($article->clicks_count) }} clicks</p>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-2xl bg-white px-3 py-2 text-xs text-slate-500">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Last Viewed</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ optional($article->last_viewed_at)?->format('M d, H:i') ?? 'Never' }}</p>
                            </div>
                            <div class="rounded-2xl bg-white px-3 py-2 text-xs text-slate-500">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Last Clicked</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ optional($article->last_clicked_at)?->format('M d, H:i') ?? 'Never' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
