@extends('layouts.app')

@section('title', 'Trend Keywords - Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Google Trends</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900">Country keyword monitor</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">Each 5-minute trend cycle refreshes the live Google Trends keyword pool, then tries to save at least {{ $trendsSnapshot['articles_per_country'] }} articles for each tracked country using the same Google News fetch path as production.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back to Dashboard
            </a>
            <form action="{{ route('admin.trends.refresh') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-400 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm transition hover:bg-emerald-500">
                    Refresh Trend Keywords
                </button>
            </form>
            <form action="{{ route('admin.trends.refresh') }}" method="POST" id="trend-sync-start-form">
                @csrf
                <input type="hidden" name="sync_news" value="1">
                <button type="submit" id="trend-sync-start-button" class="inline-flex items-center rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
                    Refresh Keywords + Fetch News
                </button>
            </form>
            <form action="{{ route('admin.trends.cleanup') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-800 shadow-sm transition hover:bg-amber-100">
                    Clean 24h+ Keywords
                </button>
            </form>
            <form action="{{ route('admin.trends.restart') }}" method="POST" id="trend-sync-restart-form" class="{{ in_array($trendSyncState['status'], ['queued', 'running', 'stalled'], true) ? '' : 'hidden' }}">
                @csrf
                <button type="submit" id="trend-sync-restart-button" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-100">
                    Stop & Resync
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4 mb-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Countries</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendsSnapshot['country_count']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Priority trend markets</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Per Country</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendsSnapshot['keywords_per_country']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Active keyword slots</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Article Target</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendsSnapshot['articles_per_country']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Minimum attempt per country each run</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Keyword Pool</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendsSnapshot['keyword_pool_limit']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Maximum stored trend keywords total</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Last Refresh</p>
            <p class="mt-2 text-sm font-black text-slate-900">{{ $trendsSnapshot['last_synced_at'] ? \Illuminate\Support\Carbon::parse($trendsSnapshot['last_synced_at'])->format('M d, Y H:i') : 'Not yet refreshed' }}</p>
            <p class="mt-1 text-xs text-slate-500">Scheduled every {{ $trendFetchStats['interval_minutes'] }} minutes</p>
        </div>
    </div>

    <div id="trend-sync-monitor"
         data-sync-status-url="{{ route('admin.trends.sync-status') }}"
         data-initial-sync='@json($trendSyncState)'
         class="mb-8 rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Background Sync Monitor</p>
                    <h2 class="mt-1 text-lg font-extrabold text-slate-900">Trend crawler progress</h2>
                    <p class="mt-1 text-xs text-slate-500">Live queue state, country-by-country steps, and fetch output without blocking the page.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span id="trend-sync-status-badge" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-white">
                        <span class="h-2 w-2 rounded-full bg-white/80"></span>
                        <span>Status</span>
                    </span>
                    <span id="trend-sync-progress-label" class="text-sm font-black text-slate-900">0%</span>
                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-600 shadow-sm">
                        <input id="trend-sync-failsafe-toggle" type="checkbox" class="peer sr-only">
                        <span class="relative h-5 w-9 rounded-full bg-slate-200 transition peer-checked:bg-emerald-500">
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-4"></span>
                        </span>
                        <span>Failsafe Auto Trigger</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div class="rounded-2xl bg-slate-100 overflow-hidden">
                <div id="trend-sync-progress-bar" class="h-3 rounded-2xl bg-gradient-to-r from-emerald-500 via-emerald-400 to-amber-400 transition-all duration-500" style="width: 0%"></div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/80">Automatic Trigger</p>
                    <p id="trend-sync-auto-countdown" class="mt-1 text-2xl font-black tabular-nums text-emerald-800">--:--</p>
                    <p id="trend-sync-auto-status" class="mt-1 text-xs font-semibold text-emerald-900/70">Waiting for scheduler.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Next Auto Fetch</p>
                    <p id="trend-sync-auto-next-at" class="mt-1 text-sm font-bold text-slate-900">Calculating...</p>
                    <p id="trend-sync-auto-note" class="mt-1 text-xs text-slate-500">Laravel scheduler watches this every minute.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Fetch Runs</p>
                    <p id="trend-sync-auto-runs" class="mt-1 text-lg font-black text-slate-900">{{ number_format($trendFetchStats['total_runs']) }}</p>
                    <p id="trend-sync-auto-last-at" class="mt-1 text-xs font-semibold text-slate-500">Last refresh pending.</p>
                    <p id="trend-sync-auto-health" class="mt-2 text-xs font-bold text-amber-600">{{ $trendFetchStats['content_health_label'] ?? 'Monitoring' }}</p>
                    <p id="trend-sync-auto-health-note" class="mt-1 text-xs text-slate-500">{{ $trendFetchStats['content_health_message'] ?? 'Watching trend sync state.' }} Current total: {{ number_format($trendFetchStats['news_total'] ?? 0) }}.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-950 px-4 py-3 text-white">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Run Type</p>
                    <p class="mt-1 text-sm font-black">Async queue worker</p>
                    <p id="trend-sync-auto-interval" class="mt-1 text-xs text-slate-300">Every {{ $trendFetchStats['interval_minutes'] }} minutes fetch {{ $trendFetchStats['articles_per_country'] }}+ stories for each of {{ $trendFetchStats['country_count'] }} countries.</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 xl:grid-cols-[1.15fr_0.85fr] gap-5">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Current Stage</p>
                            <p id="trend-sync-stage" class="mt-1 text-sm font-bold text-slate-900">Idle</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Countries</p>
                            <p id="trend-sync-topic-progress" class="mt-1 text-sm font-bold text-slate-900">0 / 0</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">New Articles</p>
                            <p id="trend-sync-new-articles" class="mt-1 text-sm font-bold text-emerald-600">0</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Duplicates</p>
                            <p id="trend-sync-duplicates" class="mt-1 text-sm font-bold text-amber-600">0</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.18)] animate-pulse"></span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/80">Doing Now</p>
                                <p id="trend-sync-current-action" class="mt-1 text-sm font-bold text-emerald-900">Waiting for next sync.</p>
                                <p id="trend-sync-current-detail" class="mt-1 text-xs text-emerald-800/80">No active fetch step at the moment.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Requested</p>
                            <p id="trend-sync-requested-at" class="mt-1 text-xs font-semibold text-slate-700">Not started</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Started</p>
                            <p id="trend-sync-started-at" class="mt-1 text-xs font-semibold text-slate-700">Not started</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Finished</p>
                            <p id="trend-sync-finished-at" class="mt-1 text-xs font-semibold text-slate-700">Waiting</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-950 text-slate-100 overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-white/10">
                            <h3 class="text-sm font-bold">Process Output</h3>
                            <span class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Live log</span>
                        </div>
                        <div id="trend-sync-log" class="max-h-72 overflow-y-auto px-4 py-4 space-y-2 text-xs font-medium"></div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Keyword Flow</h3>
                                <p class="mt-1 text-xs text-slate-500">Google Trends RSS currently exposes up to {{ $trendsSnapshot['keywords_per_country'] }} live keywords per country. The crawler keeps those active, and trend keywords older than 24 hours are automatically destroyed.</p>
                            </div>
                            <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-2 text-[11px] font-bold text-emerald-700">
                                {{ number_format($trendsSnapshot['total_active_keyword_limit']) }} active max
                            </span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white px-4 py-3 border border-slate-200">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Recovered Images</p>
                                <p id="trend-sync-images-recovered" class="mt-1 text-lg font-black text-slate-900">0</p>
                            </div>
                            <div class="rounded-2xl bg-white px-4 py-3 border border-slate-200">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Trend Articles</p>
                                <p id="trend-sync-official-articles" class="mt-1 text-lg font-black text-slate-900">{{ number_format($trendFetchStats['news_total'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Final Summary</h3>
                        <p id="trend-sync-summary" class="mt-2 text-sm text-slate-600 leading-6">Background trend sync is waiting for new work.</p>
                        <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 border border-slate-200">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Last Output</p>
                            <p id="trend-sync-last-output" class="mt-2 text-xs font-medium text-slate-700 whitespace-pre-line break-words">Waiting for next run.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Active Total</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendsSnapshot['total_active_keywords']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $trendsSnapshot['country_count'] }} countries x {{ $trendsSnapshot['keywords_per_country'] }} active keywords each</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Stored Pool Limit</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendsSnapshot['keyword_pool_limit']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Keywords older than 24 hours are automatically removed from the stored pool</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Trend News Cap</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendsSnapshot['news_article_limit']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Maximum new trend articles saved in one full run</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Trend Articles</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($trendFetchStats['news_total'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-500">Saved inside the Google Trends section</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        @foreach($trendsSnapshot['countries'] as $country)
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black text-slate-900">{{ $country['name'] }}</h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-700">{{ $country['code'] }}</span>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ $country['language'] }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Top {{ $trendsSnapshot['keywords_per_country'] }} keywords stay active, and the crawler aims to save {{ $trendsSnapshot['articles_per_country'] }} stories for this country on each run.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Active / Stored</p>
                        <p class="mt-1 text-sm font-black text-slate-900">{{ $country['active'] }} / {{ $country['stored'] }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-sky-700">{{ $country['fetched'] }} fetched now</span>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ $country['active'] }} active</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-700">{{ $country['stored'] }} stored</span>
                </div>

                <div class="mt-5 space-y-2">
                    @forelse($country['keywords'] as $topic)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 truncate">{{ $topic->name }}</p>
                                <p class="mt-1 text-[11px] text-slate-500 truncate">{{ $topic->keyword }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] {{ $topic->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $topic->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            No trend keywords stored for this country yet.
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-900">Trend News Feed</h2>
                <p class="mt-1 text-sm text-slate-500">Recent articles fetched from Google Trends keywords, filterable by country like the main dashboard article monitor.</p>
            </div>
            <form action="{{ route('admin.trends') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <select name="country" onchange="this.form.submit()" class="w-full sm:w-56 bg-slate-50 border border-slate-200 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 outline-none cursor-pointer">
                    <option value="ALL" @selected($selectedCountry === 'ALL')>All Countries</option>
                    @foreach($trendsSnapshot['countries'] as $country)
                        <option value="{{ $country['code'] }}" @selected($selectedCountry === $country['code'])>{{ $country['name'] }} ({{ $country['code'] }})</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/50">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-650 font-bold uppercase tracking-wider">
                        <th class="p-3.5">Article</th>
                        <th class="p-3.5 text-center w-28">Country</th>
                        <th class="p-3.5 text-center w-28">Views</th>
                        <th class="p-3.5 text-center w-28">Clicks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 bg-white">
                    @forelse($trendArticles as $article)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="p-3.5">
                                <div class="flex flex-col gap-1 max-w-[560px]">
                                    <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-800 hover:text-emerald-600 transition-colors line-clamp-2">
                                        {{ $article->title }}
                                    </a>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[10px] text-slate-450">
                                        <span class="font-bold text-slate-650 bg-slate-100 px-1 py-0.2 rounded">{{ $article->source_name }}</span>
                                        <span>•</span>
                                        <span>{{ $article->published_at->format('M d, Y H:i') }}</span>
                                        <span>•</span>
                                        <span class="text-slate-400 italic">{{ $article->newsTopic?->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-700">
                                    {{ $article->newsTopic?->country ?? '--' }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="text-xs font-bold text-slate-800">{{ number_format($article->views_count) }}</span>
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="text-xs font-bold text-amber-600">{{ number_format($article->clicks_count) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400 italic">
                                No trend articles found for this country filter yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trendArticles->hasPages())
            <div class="mt-5">
                {{ $trendArticles->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const monitor = document.getElementById('trend-sync-monitor');

        if (!monitor) {
            return;
        }

        const statusUrl = monitor.dataset.syncStatusUrl;
        let syncState = JSON.parse(monitor.dataset.initialSync || '{}');
        let poller = null;
        let pollDelay = null;
        let lastFailsafeTrigger = null;

        const els = {
            badge: document.getElementById('trend-sync-status-badge'),
            startButton: document.getElementById('trend-sync-start-button'),
            startForm: document.getElementById('trend-sync-start-form'),
            restartForm: document.getElementById('trend-sync-restart-form'),
            progressLabel: document.getElementById('trend-sync-progress-label'),
            progressBar: document.getElementById('trend-sync-progress-bar'),
            stage: document.getElementById('trend-sync-stage'),
            currentAction: document.getElementById('trend-sync-current-action'),
            currentDetail: document.getElementById('trend-sync-current-detail'),
            topicProgress: document.getElementById('trend-sync-topic-progress'),
            newArticles: document.getElementById('trend-sync-new-articles'),
            duplicates: document.getElementById('trend-sync-duplicates'),
            requestedAt: document.getElementById('trend-sync-requested-at'),
            startedAt: document.getElementById('trend-sync-started-at'),
            finishedAt: document.getElementById('trend-sync-finished-at'),
            log: document.getElementById('trend-sync-log'),
            imagesRecovered: document.getElementById('trend-sync-images-recovered'),
            officialArticles: document.getElementById('trend-sync-official-articles'),
            summary: document.getElementById('trend-sync-summary'),
            lastOutput: document.getElementById('trend-sync-last-output'),
            autoCountdown: document.getElementById('trend-sync-auto-countdown'),
            autoStatus: document.getElementById('trend-sync-auto-status'),
            autoNextAt: document.getElementById('trend-sync-auto-next-at'),
            autoNote: document.getElementById('trend-sync-auto-note'),
            autoRuns: document.getElementById('trend-sync-auto-runs'),
            autoLastAt: document.getElementById('trend-sync-auto-last-at'),
            autoInterval: document.getElementById('trend-sync-auto-interval'),
            autoHealth: document.getElementById('trend-sync-auto-health'),
            autoHealthNote: document.getElementById('trend-sync-auto-health-note'),
            failsafeToggle: document.getElementById('trend-sync-failsafe-toggle'),
        };

        const FAILSAFE_STORAGE_KEY = 'admin-trend-sync-failsafe-enabled';
        const FAILSAFE_SLOT_KEY = 'admin-trend-sync-failsafe-last-slot';
        const FAILSAFE_GRACE_SECONDS = 20;

        const formatTime = (iso) => {
            if (!iso) {
                return 'Waiting';
            }

            try {
                return new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }).format(new Date(iso));
            } catch (error) {
                return iso;
            }
        };

        const secondsUntilNextFetch = (state) => {
            const nextFetch = state.fetch_stats?.next_scheduled_at;

            if (!nextFetch) {
                return null;
            }

            const diffMs = new Date(nextFetch).getTime() - Date.now();

            if (Number.isNaN(diffMs)) {
                return null;
            }

            return Math.max(0, Math.floor(diffMs / 1000));
        };

        const formatCountdown = (seconds) => {
            if (seconds === null) {
                return '--:--';
            }

            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;

            return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
        };

        const shouldPoll = (state) => ['queued', 'running'].includes(state.status);
        const failsafeEnabled = () => els.failsafeToggle?.checked === true;
        const activeSlotKey = (state) => state.fetch_stats?.next_scheduled_at || null;

        const restoreFailsafeState = () => {
            if (!els.failsafeToggle) {
                return;
            }

            els.failsafeToggle.checked = localStorage.getItem(FAILSAFE_STORAGE_KEY) === '1';
            lastFailsafeTrigger = localStorage.getItem(FAILSAFE_SLOT_KEY);
        };

        const rememberFailsafeTrigger = (slotKey) => {
            lastFailsafeTrigger = slotKey;
            localStorage.setItem(FAILSAFE_SLOT_KEY, slotKey);
        };

        const maybeTriggerFailsafe = () => {
            if (!failsafeEnabled() || !els.startForm || shouldPoll(syncState)) {
                return;
            }

            const nextFetch = syncState.fetch_stats?.next_scheduled_at;

            if (!nextFetch) {
                return;
            }

            const diffMs = new Date(nextFetch).getTime() - Date.now();

            if (Number.isNaN(diffMs)) {
                return;
            }

            const overdueSeconds = Math.floor(Math.abs(diffMs) / 1000);
            const slotKey = activeSlotKey(syncState);

            if (diffMs > 0 || overdueSeconds < FAILSAFE_GRACE_SECONDS || !slotKey) {
                return;
            }

            if (lastFailsafeTrigger === slotKey) {
                return;
            }

            rememberFailsafeTrigger(slotKey);

            if (els.autoStatus) {
                els.autoStatus.textContent = 'Failsafe triggered. Starting manual trend sync because the scheduler missed this window.';
            }

            els.startForm.requestSubmit();
        };

        const pollingDelayFor = (state) => {
            const seconds = secondsUntilNextFetch(state);

            if (shouldPoll(state) || (seconds !== null && seconds <= 90)) {
                return 2500;
            }

            return 10000;
        };

        function setPollingDelay(delay) {
            if (poller && pollDelay === delay) {
                return;
            }

            if (poller) {
                window.clearInterval(poller);
            }

            pollDelay = delay;
            poller = window.setInterval(() => {
                fetchState().catch(() => {});
            }, delay);
        }

        const updateAutoCountdown = () => {
            const seconds = secondsUntilNextFetch(syncState);

            if (els.autoCountdown) {
                els.autoCountdown.textContent = formatCountdown(seconds);
            }

            if (els.autoStatus) {
                if (shouldPoll(syncState)) {
                    els.autoStatus.textContent = 'Automatic monitor is following the active trend fetch.';
                } else if (seconds === null) {
                    els.autoStatus.textContent = 'Waiting for scheduler data.';
                } else if (seconds === 0) {
                    els.autoStatus.textContent = failsafeEnabled()
                        ? 'Due now. Failsafe will trigger if Laravel does not queue the trend job.'
                        : 'Due now. Waiting for Laravel to queue the trend job.';
                } else if (seconds <= 90) {
                    els.autoStatus.textContent = failsafeEnabled()
                        ? 'Scheduler window is near. Failsafe is armed and polling faster.'
                        : 'Scheduler window is near. Polling faster.';
                } else {
                    els.autoStatus.textContent = failsafeEnabled()
                        ? 'Countdown to the next automatic trend fetch. Failsafe is armed.'
                        : 'Countdown to the next automatic trend fetch.';
                }
            }

            if (els.autoNote) {
                els.autoNote.textContent = failsafeEnabled()
                    ? `If Laravel scheduler misses the slot, this page will auto-submit Refresh Keywords + Fetch News after ${FAILSAFE_GRACE_SECONDS} seconds.`
                    : 'Laravel scheduler watches this every minute.';
            }

            if (seconds !== null && seconds <= 90 && !shouldPoll(syncState)) {
                setPollingDelay(2500);
            }

            maybeTriggerFailsafe();
        };

        const statusTheme = (status) => {
            switch (status) {
                case 'running':
                    return ['bg-emerald-600', 'Sync Running', 'bg-emerald-300'];
                case 'queued':
                    return ['bg-amber-500', 'Queued', 'bg-amber-200'];
                case 'completed':
                    return ['bg-sky-600', 'Completed', 'bg-sky-200'];
                case 'partial_failed':
                    return ['bg-orange-700', 'Partial Failed', 'bg-orange-200'];
                case 'failed':
                    return ['bg-red-600', 'Failed', 'bg-red-200'];
                case 'stopped':
                    return ['bg-rose-600', 'Stopped', 'bg-rose-200'];
                case 'stalled':
                    return ['bg-orange-600', 'Stalled', 'bg-orange-200'];
                default:
                    return ['bg-slate-900', 'Idle', 'bg-white/80'];
            }
        };

        const logTone = (level) => {
            switch (level) {
                case 'error':
                    return 'text-red-300';
                case 'warning':
                    return 'text-amber-300';
                case 'success':
                    return 'text-emerald-300';
                default:
                    return 'text-slate-200';
            }
        };

        const render = (state) => {
            const meta = state.meta || {};
            const stats = meta.stats || {};
            const fetchStats = state.fetch_stats || {};
            const progress = Math.max(0, Math.min(100, Number(meta.progress || 0)));
            const [badgeClass, badgeText, dotClass] = statusTheme(state.status);
            const processedSections = Number(meta.processed_sections || 0);
            const totalSections = Number(meta.total_sections || 0);
            const currentTopic = meta.current_topic || null;
            const currentSection = meta.current_section || null;
            const currentItem = Number(meta.current_item || 0);
            const totalItems = Number(meta.total_items || 0);
            const currentArticle = meta.current_article || null;

            els.badge.className = `inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-white ${badgeClass}`;
            els.badge.innerHTML = `<span class="h-2 w-2 rounded-full ${dotClass}"></span><span>${badgeText}</span>`;

            els.progressLabel.textContent = `${progress}%`;
            els.progressBar.style.width = `${progress}%`;
            els.stage.textContent = meta.stage || 'Idle';
            els.topicProgress.textContent = `${processedSections} / ${totalSections}`;
            els.newArticles.textContent = String(stats.new_articles || 0);
            els.duplicates.textContent = String(stats.skipped_duplicates || 0);
            els.imagesRecovered.textContent = String(stats.images_recovered || 0);
            els.officialArticles.textContent = new Intl.NumberFormat().format(Number(fetchStats.news_total || 0));
            els.requestedAt.textContent = formatTime(state.requested_at);
            els.startedAt.textContent = state.started_at ? formatTime(state.started_at) : 'Not started';
            els.finishedAt.textContent = state.finished_at ? formatTime(state.finished_at) : 'Waiting';
            els.summary.textContent = meta.summary || 'Background trend sync is waiting for new work.';
            els.lastOutput.textContent = state.last_output || 'Waiting for next run.';

            if (els.autoNextAt) {
                els.autoNextAt.textContent = fetchStats.next_scheduled_at ? formatTime(fetchStats.next_scheduled_at) : 'Calculating...';
            }

            if (els.autoRuns) {
                els.autoRuns.textContent = new Intl.NumberFormat().format(Number(fetchStats.total_runs || 0));
            }

            if (els.autoLastAt) {
                els.autoLastAt.textContent = fetchStats.last_success_at
                    ? `Last refresh ${formatTime(fetchStats.last_success_at)}`
                    : 'Last refresh pending.';
            }

            if (els.autoInterval) {
                els.autoInterval.textContent = `Every ${fetchStats.interval_minutes || 5} minutes fetch ${fetchStats.articles_per_country || 10}+ stories for each of ${fetchStats.country_count || 0} countries.`;
            }

            if (els.autoHealth) {
                els.autoHealth.textContent = fetchStats.content_health_label || 'Monitoring';
            }

            if (els.autoHealthNote) {
                const newsTotal = new Intl.NumberFormat().format(Number(fetchStats.news_total || 0));
                const healthMessage = fetchStats.content_health_message || 'Watching the dedicated trends queue.';
                els.autoHealthNote.textContent = `${healthMessage} Current total: ${newsTotal}.`;
            }

            updateAutoCountdown();
            setPollingDelay(pollingDelayFor(state));

            if (els.startButton) {
                els.startButton.disabled = ['queued', 'running'].includes(state.status);
                els.startButton.classList.toggle('opacity-60', els.startButton.disabled);
                els.startButton.classList.toggle('cursor-not-allowed', els.startButton.disabled);
            }

            if (els.restartForm) {
                els.restartForm.classList.toggle('hidden', !['queued', 'running', 'stalled'].includes(state.status));
            }

            if (state.status === 'running') {
                els.currentAction.textContent = currentSection
                    ? `Working on country ${processedSections + 1 > totalSections ? totalSections : processedSections + 1} of ${totalSections}: ${currentSection}`
                    : (meta.stage || 'Running trend sync steps');
                els.currentDetail.textContent = currentItem > 0 && totalItems > 0
                    ? `${currentTopic ? `Keyword ${currentTopic}. ` : ''}Article ${currentItem} of ${totalItems}${currentArticle ? `: ${currentArticle}` : ''}. New articles ${stats.new_articles || 0}, duplicates ${stats.skipped_duplicates || 0}, recovered images ${stats.images_recovered || 0}.`
                    : `${currentTopic ? `Keyword ${currentTopic}. ` : ''}Current progress ${progress}%. New articles ${stats.new_articles || 0}, duplicates ${stats.skipped_duplicates || 0}, recovered images ${stats.images_recovered || 0}.`;
            } else if (state.status === 'queued') {
                els.currentAction.textContent = 'Preparing background trend sync job.';
                els.currentDetail.textContent = 'The response has returned and Laravel is about to start the trend fetch process.';
            } else if (state.status === 'completed') {
                els.currentAction.textContent = 'Latest trend sync completed successfully.';
                els.currentDetail.textContent = meta.summary || 'The trend crawler finished all configured steps.';
            } else if (state.status === 'partial_failed') {
                els.currentAction.textContent = 'Trend sync completed with some country failures.';
                els.currentDetail.textContent = meta.summary || 'One or more country fetches did not complete cleanly.';
            } else if (['failed', 'stalled', 'stopped'].includes(state.status)) {
                els.currentAction.textContent = 'Trend sync needs attention.';
                els.currentDetail.textContent = state.last_output || 'The current trend run did not complete cleanly.';
            } else {
                els.currentAction.textContent = 'Waiting for next sync.';
                els.currentDetail.textContent = 'No active fetch step at the moment.';
            }

            const log = Array.isArray(state.log) ? state.log : [];
            if (log.length === 0) {
                els.log.innerHTML = '<p class="text-slate-400">No process messages yet.</p>';
            } else {
                els.log.innerHTML = log.map((entry) => `
                    <div class="flex gap-3">
                        <span class="shrink-0 text-slate-500">${formatTime(entry.time)}</span>
                        <span class="${logTone(entry.level)}">${entry.message}</span>
                    </div>
                `).join('');
                els.log.scrollTop = els.log.scrollHeight;
            }
        };

        const fetchState = async () => {
            const response = await fetch(statusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error('Failed to load trend sync status');
            }

            syncState = await response.json();
            render(syncState);
        };

        render(syncState);
        restoreFailsafeState();
        updateAutoCountdown();
        window.setInterval(updateAutoCountdown, 1000);

        if (els.failsafeToggle) {
            els.failsafeToggle.addEventListener('change', () => {
                localStorage.setItem(FAILSAFE_STORAGE_KEY, els.failsafeToggle.checked ? '1' : '0');
                updateAutoCountdown();
            });
        }

        if (els.startForm) {
            els.startForm.addEventListener('submit', () => {
                setPollingDelay(2500);
                window.setTimeout(() => {
                    fetchState().catch(() => {});
                }, 500);
            });
        }
    });
</script>
@endsection
