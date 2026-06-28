@extends('layouts.app')

@section('title', 'Crawler & Bot Analytics - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Dashboard</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Crawler & Bot Analytics</h1>
            <p class="mt-2 text-sm text-slate-500">Monitor incoming request volumes, crawl velocities, and user agents for search engines and AI scrapers.</p>
        </div>
        <div class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white p-1 shadow-sm text-xs">
            <a href="?period=today" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === 'today' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">Today</a>
            <a href="?period=yesterday" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === 'yesterday' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">Yesterday</a>
            <a href="?period=7d" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === '7d' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">7 Days</a>
            <a href="?period=30d" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === '30d' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">30 Days</a>
            <a href="?period=90d" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === '90d' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">90 Days</a>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200 mb-8">
        <nav class="-mb-px flex flex-wrap gap-x-8">
            <a href="{{ route('admin.analytics.index') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Executive Dashboard
            </a>
            <a href="{{ route('admin.analytics.realtime') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                </span>
                Real-Time Monitor
            </a>
            <a href="{{ route('admin.analytics.modules') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Content Modules
            </a>
            <a href="{{ route('admin.analytics.bots') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-indigo-500 text-indigo-600">
                Crawlers & Bots
            </a>
            <a href="{{ route('admin.analytics.performance') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                System Performance
            </a>
        </nav>
    </div>

    {{-- Bots Table --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <h2 class="text-sm font-bold text-slate-900">Crawler & Bot Logs</h2>
            <p class="text-xs text-slate-500 mt-0.5">Summary of requests grouped by recognized crawler user agent configurations.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50/40 text-left font-bold text-slate-455 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Crawler Type</th>
                        <th class="px-6 py-4">Classification</th>
                        <th class="px-6 py-4">Total Hits</th>
                        <th class="px-6 py-4 text-right">Last Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($bots as $bot)
                        @php
                            // Dynamic classifications
                            $type = $bot->bot_type ?: 'Unknown Bot';
                            $aiBots = ['GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'Meta-AI', 'Applebot'];
                            $searchBots = ['Googlebot', 'Bingbot', 'Yandex', 'DuckDuckBot'];
                            $monitorBots = ['UptimeRobot', 'Better Stack', 'Pingdom'];

                            if (in_array($type, $aiBots)) {
                                $classLabel = 'AI Crawler / Scraper';
                                $classColor = 'bg-rose-50 border-rose-100 text-rose-700';
                            } elseif (in_array($type, $searchBots)) {
                                $classLabel = 'Search Engine Indexer';
                                $classColor = 'bg-emerald-50 border-emerald-100 text-emerald-700';
                            } elseif (in_array($type, $monitorBots)) {
                                $classLabel = 'Uptime Monitoring Bot';
                                $classColor = 'bg-sky-50 border-sky-100 text-sky-700';
                            } else {
                                $classLabel = 'Script / HTTP Client';
                                $classColor = 'bg-amber-50 border-amber-100 text-amber-700';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-900 text-sm">{{ $type }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full border text-[10px] font-bold inline-flex items-center {{ $classColor }}">
                                    {{ $classLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-black text-slate-700">
                                {{ number_format($bot->hits) }}
                            </td>
                            <td class="px-6 py-4 text-right text-slate-500 font-medium">
                                {{ $bot->last_seen ? Carbon::parse($bot->last_seen)->diffForHumans() : 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-slate-400 mb-2">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-slate-500">No bot traffic recorded for this period.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
