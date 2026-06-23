@extends('layouts.app')

@section('title', 'Ranking Analytics - World Cup News Explorer')

@php
    $dailyRank = $analyticsSummary['daily_rank'];
    $masterRank = $analyticsSummary['master_rank'];

    $toneClasses = function (string $tone): string {
        return match ($tone) {
            'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
            'yellow' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        };
    };

    $badgeClasses = function (string $tone): string {
        return match ($tone) {
            'rose' => 'bg-rose-50 text-rose-700',
            'sky' => 'bg-sky-50 text-sky-700',
            'emerald' => 'bg-emerald-50 text-emerald-700',
            'violet' => 'bg-violet-50 text-violet-700',
            'yellow' => 'bg-yellow-50 text-yellow-700',
            'amber' => 'bg-amber-50 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
    };
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Ranking Analytics</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Daily rank + master points</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Daily views reset every day. Total performance runs on gamified master points with view blocks, click rewards, and a conversion bonus.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.analytics') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-colors shadow-sm">
                Back To Analytics
            </a>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-950 text-white hover:bg-slate-800 text-xs font-bold transition-colors shadow-sm">
                Dashboard
            </a>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 mb-8">
        <section class="rounded-[2rem] border px-5 py-5 shadow-sm {{ $toneClasses($dailyRank['tone']) }}">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] opacity-80">Daily View Rank</p>
            <h2 class="mt-2 text-4xl font-black">{{ $dailyRank['tier'] }}</h2>
            <p class="mt-2 text-sm opacity-80">{{ $dailyRank['range'] }}</p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-4 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Today&apos;s Views</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['daily_views']) }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-4 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Today&apos;s Clicks</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['daily_clicks']) }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border px-5 py-5 shadow-sm {{ $toneClasses($masterRank['tone']) }}">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] opacity-80">Master Points Rank</p>
            <h2 class="mt-2 text-4xl font-black">{{ $masterRank['tier'] }}</h2>
            <p class="mt-2 text-sm opacity-80">{{ $masterRank['range'] }}</p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-4 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Master Points</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['master_points']) }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-4 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">All-time Views</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['article_views']) }}</p>
                </div>
            </div>
        </section>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm mb-8">
        <h2 class="text-base font-bold text-slate-900">Point Rules</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Daily Reset</p>
                <p class="mt-2 text-2xl font-black text-slate-900">00:00</p>
                <p class="mt-1 text-xs text-slate-500">Daily rank starts from zero every new day using tracked daily metrics.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">View Blocks</p>
                <p class="mt-2 text-2xl font-black text-slate-900">+1000</p>
                <p class="mt-1 text-xs text-slate-500">Each full 1000 all-time views adds 1000 master points.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Click Reward</p>
                <p class="mt-2 text-2xl font-black text-slate-900">+25</p>
                <p class="mt-1 text-xs text-slate-500">Every outbound click adds 25 master points.</p>
            </div>
            <div class="rounded-2xl border px-4 py-4 {{ $analyticsSummary['view_rank_bonus_active'] ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] {{ $analyticsSummary['view_rank_bonus_active'] ? 'text-emerald-700/80' : 'text-slate-400' }}">Conversion Bonus</p>
                <p class="mt-2 text-2xl font-black {{ $analyticsSummary['view_rank_bonus_active'] ? 'text-emerald-700' : 'text-slate-900' }}">+{{ number_format($analyticsSummary['master_points_from_bonus']) }}</p>
                <p class="mt-1 text-xs {{ $analyticsSummary['view_rank_bonus_active'] ? 'text-emerald-700/80' : 'text-slate-500' }}">Daily bonus is active when today&apos;s conversion rate goes above {{ number_format($analyticsSummary['view_rank_bonus_threshold'], 2) }}%.</p>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2 mb-8">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-slate-900">Daily Rank Ladder</h2>
            <p class="mt-1 text-xs text-slate-500">This ladder uses today&apos;s article views only.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach($analyticsSummary['daily_ladder'] as $tier)
                    @php($isActiveTier = $dailyRank['tier'] === $tier['tier'])
                    <div class="rounded-2xl border px-4 py-3 {{ $isActiveTier ? $toneClasses($dailyRank['tone']) : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                        <p class="text-sm font-bold">{{ $tier['tier'] }}</p>
                        <p class="mt-1 text-xs opacity-80">{{ $tier['range'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-slate-900">Master Points Ladder</h2>
            <p class="mt-1 text-xs text-slate-500">This ladder uses all-time master points.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach($analyticsSummary['master_ladder'] as $tier)
                    @php($isActiveTier = $masterRank['tier'] === $tier['tier'] || (str_starts_with($masterRank['tier'], 'Ace') && str_starts_with($tier['tier'], 'Ace')))
                    <div class="rounded-2xl border px-4 py-3 {{ $isActiveTier ? $toneClasses($masterRank['tone']) : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                        <p class="text-sm font-bold">{{ $tier['tier'] }}</p>
                        <p class="mt-1 text-xs opacity-80">{{ $tier['range'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daily Ranking Table</h2>
                    <p class="mt-1 text-xs text-slate-500">Today&apos;s leaderboard. It resets automatically tomorrow.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ number_format($analyticsSummary['daily_views']) }} today</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white text-[11px] uppercase tracking-[0.18em] text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Article</th>
                            <th class="px-4 py-3 text-left">Tier</th>
                            <th class="px-4 py-3 text-right">Views</th>
                            <th class="px-4 py-3 text-right">Clicks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($analyticsSummary['top_daily_ranked'] as $metric)
                            <tr class="align-top">
                                <td class="px-4 py-4 font-black text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-4 min-w-[220px]">
                                    <p class="font-bold text-slate-900 line-clamp-2">{{ $metric->newsItem?->title ?? 'Deleted article' }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $metric->newsItem?->newsTopic?->name }} · {{ $metric->newsItem?->source_name }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] {{ $badgeClasses($metric->daily_rank['tone'] ?? 'slate') }}">{{ $metric->daily_rank['badge'] ?? 'Bronze' }}</span>
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-emerald-600">{{ number_format($metric->views_count) }}</td>
                                <td class="px-4 py-4 text-right font-bold text-amber-600">{{ number_format($metric->clicks_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No daily article metrics recorded yet for today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Master Ranking Table</h2>
                    <p class="mt-1 text-xs text-slate-500">All-time leaderboard based on gamified master points.</p>
                </div>
                <span class="rounded-full bg-sky-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">{{ number_format($analyticsSummary['master_points']) }} points</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white text-[11px] uppercase tracking-[0.18em] text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Article</th>
                            <th class="px-4 py-3 text-left">Tier</th>
                            <th class="px-4 py-3 text-right">Views</th>
                            <th class="px-4 py-3 text-right">Clicks</th>
                            <th class="px-4 py-3 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($analyticsSummary['top_master_ranked'] as $article)
                            <tr class="align-top">
                                <td class="px-4 py-4 font-black text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-4 min-w-[220px]">
                                    <p class="font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                                    <p class="mt-1 text-[11px] font-semibold text-slate-500">
                                        {{ number_format($article->master_points_breakdown['view_points'] ?? 0) }} from views ·
                                        {{ number_format($article->master_points_breakdown['click_points'] ?? 0) }} from clicks
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] {{ $badgeClasses($article->master_rank['tone'] ?? 'slate') }}">{{ $article->master_rank['badge'] ?? 'Bronze' }}</span>
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-emerald-600">{{ number_format($article->views_count) }}</td>
                                <td class="px-4 py-4 text-right font-bold text-amber-600">{{ number_format($article->clicks_count) }}</td>
                                <td class="px-4 py-4 text-right font-black text-sky-700">{{ number_format($article->master_points) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">No ranked articles yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
