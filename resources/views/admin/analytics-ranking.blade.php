@extends('layouts.app')

@section('title', 'Ranking Analytics - World Cup News Explorer')

@php
    $viewRank = $analyticsSummary['view_rank'];
    $rankToneClasses = match ($viewRank['tone']) {
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
        'yellow' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };

    $ladder = [
        ['tier' => 'Bronze', 'range' => '< 1500 views'],
        ['tier' => 'Silver', 'range' => '1500 - 1799 views'],
        ['tier' => 'Gold', 'range' => '1800 - 2199 views'],
        ['tier' => 'Platinum', 'range' => '2200 - 2699 views'],
        ['tier' => 'Diamond', 'range' => '2700 - 3199 views'],
        ['tier' => 'Crown', 'range' => '3200 - 3699 views'],
        ['tier' => 'Ace', 'range' => '3700 - 3899 views'],
        ['tier' => 'Ace Master', 'range' => '3900 - 4049 views'],
        ['tier' => 'Ace Dominator', 'range' => '4050 - 4200+ views'],
        ['tier' => 'Conqueror', 'range' => 'Top 500 after reaching Ace'],
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Ranking Analytics</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Detailed view ranking</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Full rank ladder and ranked article leaderboard based on article views.</p>
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

    <section class="rounded-[2rem] border px-5 py-5 shadow-sm mb-8 {{ $rankToneClasses }}">
        <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] opacity-80">Current Tier</p>
                <h2 class="mt-2 text-4xl font-black">{{ $viewRank['tier'] }}</h2>
                <p class="mt-2 text-sm opacity-80">{{ $viewRank['range'] }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-5 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Base Views</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['article_views']) }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-white/40 bg-white/70 px-5 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Rank Score</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($analyticsSummary['view_rank_score']) }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm mb-8">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Daily Conversion</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($analyticsSummary['conversion']['today']['rate'], 2) }}%</p>
                <p class="mt-1 text-xs text-slate-500">Today&apos;s clicks divided by today&apos;s views.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Bonus Rule</p>
                <p class="mt-2 text-2xl font-black text-slate-900">+100</p>
                <p class="mt-1 text-xs text-slate-500">Awarded when daily conversion is above {{ number_format($analyticsSummary['view_rank_bonus_threshold'], 2) }}%.</p>
            </div>
            <div class="rounded-2xl border px-4 py-4 {{ $analyticsSummary['view_rank_bonus_active'] ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] {{ $analyticsSummary['view_rank_bonus_active'] ? 'text-emerald-700/80' : 'text-slate-400' }}">Active Bonus</p>
                <p class="mt-2 text-2xl font-black {{ $analyticsSummary['view_rank_bonus_active'] ? 'text-emerald-700' : 'text-slate-900' }}">{{ number_format($analyticsSummary['view_rank_bonus']) }}</p>
                <p class="mt-1 text-xs {{ $analyticsSummary['view_rank_bonus_active'] ? 'text-emerald-700/80' : 'text-slate-500' }}">
                    {{ $analyticsSummary['view_rank_bonus_active'] ? 'Daily conversion passed the bonus threshold.' : 'No bonus yet. Increase today\'s conversion above the threshold.' }}
                </p>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm mb-8">
        <h2 class="text-base font-bold text-slate-900">Rank Ladder</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach($ladder as $tier)
                @php($isActiveTier = $viewRank['tier'] === $tier['tier'] || ($tier['tier'] === 'Ace' && str_starts_with($viewRank['tier'], 'Ace')))
                <div class="rounded-2xl border px-4 py-3 {{ $isActiveTier ? $rankToneClasses : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                    <p class="text-sm font-bold">{{ $tier['tier'] }}</p>
                    <p class="mt-1 text-xs opacity-80">{{ $tier['range'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Top Viewed News</h2>
                    <p class="text-xs text-slate-500 mt-1">Ranked by article views.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ number_format($analyticsSummary['article_views']) }} total</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($analyticsSummary['top_viewed'] as $article)
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $loop->iteration }}</p>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em]
                                    @if(($article->view_rank['tone'] ?? null) === 'rose') bg-rose-50 text-rose-700
                                    @elseif(($article->view_rank['tone'] ?? null) === 'sky') bg-sky-50 text-sky-700
                                    @elseif(($article->view_rank['tone'] ?? null) === 'emerald') bg-emerald-50 text-emerald-700
                                    @elseif(($article->view_rank['tone'] ?? null) === 'violet') bg-violet-50 text-violet-700
                                    @elseif(($article->view_rank['tone'] ?? null) === 'yellow') bg-yellow-50 text-yellow-700
                                    @elseif(($article->view_rank['tone'] ?? null) === 'amber') bg-amber-50 text-amber-700
                                    @else bg-slate-100 text-slate-700 @endif">{{ $article->view_rank['badge'] ?? 'Bronze' }}</span>
                            </div>
                            <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                            <p class="text-[11px] text-slate-500 mt-1">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-500">{{ $article->view_rank['range'] ?? '< 1500 views' }}</p>
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
                    <p class="text-xs text-slate-500 mt-1">Outbound traffic leaders.</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">{{ number_format($analyticsSummary['article_clicks']) }} total</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($analyticsSummary['top_clicked'] as $article)
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">#{{ $loop->iteration }}</p>
                            <p class="text-sm font-bold text-slate-900 line-clamp-2">{{ $article->title }}</p>
                            <p class="text-[11px] text-slate-500 mt-1">{{ $article->newsTopic?->name }} · {{ $article->source_name }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-amber-600">{{ number_format($article->clicks_count) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
