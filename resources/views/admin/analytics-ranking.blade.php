@extends('layouts.app')

@section('title', 'Signalz Admin XP - World Cup News Explorer')

@php
    $today = $xpDashboard['today'];
    $week = $xpDashboard['week'];
    $lifetime = $xpDashboard['lifetime'];
    $streak = $xpDashboard['streak'];
    $badges = $xpDashboard['badges'];
    $weeklyChart = $xpDashboard['charts']['weekly_xp'];
    $nextBadge = $badges['next'];

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

    $targetPercent = $today['target_xp'] > 0 ? min(100, round(($today['total_xp'] / $today['target_xp']) * 100)) : 0;
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Signalz Admin XP</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Private motivation dashboard</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">Real site traffic turns into admin XP. This page tracks page visits, article views, source clicks, daily targets, streaks, weekly growth, and lifetime progress. Ad clicks stay disabled.</p>
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

    <section class="mb-8 overflow-hidden rounded-[2rem] border border-emerald-200 bg-gradient-to-br from-emerald-500 via-emerald-500 to-teal-500 text-white shadow-[0_28px_80px_rgba(16,185,129,0.22)]">
        <div class="grid gap-6 px-5 py-6 lg:grid-cols-[1.15fr_0.85fr] sm:px-6">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-white/75">Today&apos;s Signalz XP</p>
                <div class="mt-3 flex flex-wrap items-end gap-3">
                    <p class="text-5xl font-black sm:text-6xl">{{ number_format($today['total_xp']) }}</p>
                    <span class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em]">{{ $today['target_completed'] ? 'Target Completed' : 'Target In Progress' }}</span>
                </div>
                <p class="mt-3 max-w-xl text-sm text-white/85">Page visits, article views, and source clicks are converted into XP automatically. Mission and streak bonuses are added on top.</p>
                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/15">
                    <div class="h-full rounded-full bg-white" style="width: {{ max(4, $targetPercent) }}%;"></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-4 text-xs font-semibold text-white/80">
                    <span>Target: {{ number_format($today['target_xp']) }} XP</span>
                    <span>Base: {{ number_format($today['base_xp']) }} XP</span>
                    <span>Bonus: {{ number_format($today['mission_bonus_xp'] + $today['streak_bonus_xp']) }} XP</span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-[1.7rem] border border-white/20 bg-white/10 px-4 py-4 backdrop-blur-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/70">Page Visits</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($today['page_visits']) }}</p>
                    <p class="mt-1 text-xs text-white/75">{{ number_format($xpDashboard['settings']['page_visit_xp']) }} XP each</p>
                </div>
                <div class="rounded-[1.7rem] border border-white/20 bg-white/10 px-4 py-4 backdrop-blur-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/70">Article Views</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($today['article_views']) }}</p>
                    <p class="mt-1 text-xs text-white/75">{{ number_format($xpDashboard['settings']['article_view_xp']) }} XP each</p>
                </div>
                <div class="rounded-[1.7rem] border border-white/20 bg-white/10 px-4 py-4 backdrop-blur-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/70">Source Clicks</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($today['source_clicks']) }}</p>
                    <p class="mt-1 text-xs text-white/75">{{ number_format($xpDashboard['settings']['source_click_xp']) }} XP each</p>
                </div>
                <div class="rounded-[1.7rem] border border-white/20 bg-white/10 px-4 py-4 backdrop-blur-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/70">Mission Bonus</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($today['mission_bonus_xp']) }}</p>
                    <p class="mt-1 text-xs text-white/75">Streak bonus: {{ number_format($today['streak_bonus_xp']) }} XP</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-3 mb-8">
        <section class="rounded-[2rem] border p-5 shadow-sm {{ $toneClasses($week['rank']['tone']) }}">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] opacity-80">This Week</p>
            <h2 class="mt-2 text-3xl font-black">{{ $week['rank']['name'] }}</h2>
            <p class="mt-2 text-sm opacity-80">Weekly Growth League compares this week against your previous week.</p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-[1.5rem] border border-white/35 bg-white/70 px-4 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Week XP</p>
                    <p class="mt-2 text-2xl font-black">{{ number_format($week['current']['total_xp']) }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-white/35 bg-white/70 px-4 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Growth</p>
                    <p class="mt-2 text-2xl font-black">{{ $week['growth_percentage'] >= 0 ? '+' : '' }}{{ number_format($week['growth_percentage'], 1) }}%</p>
                </div>
            </div>
            <p class="mt-3 text-xs opacity-75">Last week: {{ number_format($week['previous']['total_xp']) }} XP</p>
        </section>

        <section class="rounded-[2rem] border p-5 shadow-sm {{ $toneClasses($lifetime['rank']['tone']) }}">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] opacity-80">Lifetime</p>
            <h2 class="mt-2 text-3xl font-black">{{ $lifetime['rank']['name'] }}</h2>
            <p class="mt-2 text-sm opacity-80">Lifetime XP never resets. This is the long-term build score for Signalz.</p>
            <div class="mt-5 rounded-[1.5rem] border border-white/35 bg-white/70 px-4 py-4 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] opacity-70">Total XP</p>
                <p class="mt-2 text-3xl font-black">{{ number_format($lifetime['total_xp']) }}</p>
                @if($lifetime['next_rank'])
                    <p class="mt-2 text-xs opacity-80">Next rank: {{ $lifetime['next_rank']['name'] }} in {{ number_format($lifetime['needed_for_next_rank']) }} XP</p>
                @else
                    <p class="mt-2 text-xs opacity-80">Top rank unlocked.</p>
                @endif
            </div>
        </section>

        <section class="rounded-[2rem] border border-amber-200 bg-amber-50 p-5 text-amber-900 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-700/80">Growth Streak</p>
            <h2 class="mt-2 text-3xl font-black">{{ number_format($streak['current_days']) }}-Day Streak</h2>
            <p class="mt-2 text-sm text-amber-800/80">A streak day counts when total daily XP reaches at least {{ number_format($streak['minimum_xp']) }}.</p>
            <div class="mt-5 rounded-[1.5rem] border border-amber-200 bg-white/70 px-4 py-4 shadow-sm">
                @if($streak['next_reward'])
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700/80">Next Reward</p>
                    <p class="mt-2 text-2xl font-black">{{ $streak['next_reward']['label'] }}</p>
                    <p class="mt-1 text-xs text-amber-800/80">{{ $streak['next_reward']['remaining_days'] }} more day(s) for +{{ number_format($streak['next_reward']['reward_xp']) }} XP</p>
                @else
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700/80">Latest Bonus</p>
                    <p class="mt-2 text-2xl font-black">+{{ number_format($streak['latest_bonus_xp']) }}</p>
                    <p class="mt-1 text-xs text-amber-800/80">All listed streak milestones are already unlocked.</p>
                @endif
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr] mb-8">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Daily Missions</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-950">Today&apos;s mission board</h2>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">{{ number_format($today['mission_bonus_xp']) }} bonus xp</span>
            </div>
            <div class="mt-5 space-y-3">
                @foreach($xpDashboard['missions'] as $mission)
                    @php($progress = $mission['target'] > 0 ? min(100, round(($mission['current'] / $mission['target']) * 100)) : 0)
                    <div class="rounded-[1.7rem] border {{ $mission['completed'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-slate-50' }} px-4 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-bold {{ $mission['completed'] ? 'text-emerald-800' : 'text-slate-900' }}">{{ $mission['completed'] ? '✅' : '⬜' }} {{ $mission['label'] }}</p>
                                <p class="mt-1 text-xs {{ $mission['completed'] ? 'text-emerald-700/80' : 'text-slate-500' }}">{{ number_format($mission['current']) }}/{{ number_format($mission['target']) }} {{ $mission['unit'] }} · reward {{ number_format($mission['reward_xp']) }} XP</p>
                            </div>
                            <span class="shrink-0 rounded-full {{ $mission['completed'] ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200' }} px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em]">{{ $progress }}%</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/80">
                            <div class="h-full rounded-full {{ $mission['completed'] ? 'bg-emerald-500' : 'bg-slate-300' }}" style="width: {{ max(4, $progress) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Point Rules</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-950">Safe XP sources only</h2>
                </div>
            </div>
            <div class="mt-5 space-y-3">
                @foreach($xpDashboard['point_rules'] as $rule)
                    <div class="flex items-center justify-between gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $rule['label'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $rule['note'] }}</p>
                        </div>
                        <span class="rounded-full {{ $rule['points'] > 0 ? 'bg-slate-950 text-white' : 'bg-rose-50 text-rose-700 border border-rose-200' }} px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em]">{{ number_format($rule['points']) }} xp</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 rounded-[1.7rem] border border-rose-200 bg-rose-50 px-4 py-4">
                <p class="text-sm font-bold text-rose-800">Ad click rewards are disabled.</p>
                <p class="mt-1 text-xs text-rose-700/80">The dashboard does not score AdSense clicks, does not create ad-click missions, and should never be used to encourage self-clicking or invalid traffic.</p>
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr] mb-8">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Weekly XP</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-950">Last 7 days progress</h2>
                </div>
                <span class="rounded-full bg-sky-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">{{ number_format($weeklyChart['total']) }} xp</span>
            </div>
            <div class="mt-5 grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start">
                <div class="rounded-[1.7rem] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Current Day</p>
                    <p class="mt-4 text-4xl font-black text-slate-950">{{ number_format($weeklyChart['headline']) }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $weeklyChart['headline_label'] }}</p>
                </div>
                <div class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="flex h-52 items-end gap-2 sm:gap-3">
                        @foreach($weeklyChart['points'] as $point)
                            @php($height = max(12, (int) round(($point['value'] / $weeklyChart['max']) * 100)))
                            <div class="flex min-w-0 flex-1 flex-col items-center">
                                <div class="flex h-36 w-full items-end rounded-[1.25rem] bg-slate-50 px-1.5 pb-1.5">
                                    <div class="w-full rounded-[1rem] bg-sky-500" style="height: {{ $height }}%;"></div>
                                </div>
                                <p class="mt-3 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $point['label'] }}</p>
                                <p class="mt-1 text-center text-xs font-extrabold text-slate-700">{{ number_format($point['value']) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Badges</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-950">Motivation unlocks</h2>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">{{ count($badges['earned']) }} earned</span>
            </div>

            @if($nextBadge)
                <div class="mt-5 rounded-[1.7rem] border border-violet-200 bg-violet-50 px-4 py-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-violet-700/80">Next Badge</p>
                    <p class="mt-2 text-2xl font-black text-violet-900">{{ $nextBadge['name'] }}</p>
                    <p class="mt-1 text-xs text-violet-800/80">{{ $nextBadge['description'] }}</p>
                    <p class="mt-2 text-xs font-semibold text-violet-800">{{ number_format($nextBadge['progress']) }}/{{ number_format($nextBadge['target']) }} {{ $nextBadge['unit'] }}</p>
                </div>
            @endif

            <div class="mt-5 space-y-3">
                @forelse($badges['recent'] as $badge)
                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">{{ $badge['name'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $badge['description'] }}</p>
                    </div>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                        No badges unlocked yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Countries</p>
                    <h2 class="mt-1 text-lg font-extrabold text-slate-950">Top country traffic today</h2>
                </div>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($xpDashboard['insights']['countries'] as $item)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-slate-700">{{ $item['label'] }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item['total']) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(8, min(100, $today['page_visits'] > 0 ? ($item['total'] / $today['page_visits']) * 100 : 0)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No country traffic recorded yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Categories</p>
                    <h2 class="mt-1 text-lg font-extrabold text-slate-950">Top section traffic today</h2>
                </div>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($xpDashboard['insights']['categories'] as $item)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-slate-700">{{ $item['label'] }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item['total']) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-sky-500" style="width: {{ max(8, min(100, $today['article_views'] > 0 ? ($item['total'] / $today['article_views']) * 100 : 0)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No category traffic recorded yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Keywords</p>
                    <h2 class="mt-1 text-lg font-extrabold text-slate-950">Top topic traffic today</h2>
                </div>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($xpDashboard['insights']['keywords'] as $item)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-slate-700">{{ $item['label'] }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item['total']) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-violet-500" style="width: {{ max(8, min(100, $today['article_views'] > 0 ? ($item['total'] / $today['article_views']) * 100 : 0)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No topic traffic recorded yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
