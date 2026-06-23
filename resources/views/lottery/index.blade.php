@extends('layouts.app')

@section('title', $search ? 'Search: "' . $search . '" – Kerala Lottery Results' : 'Kerala Lottery Results Today – Official PDF Results')
@section('meta_description', 'Search Kerala lottery results by name, draw number, or ticket number. View all prize numbers from 1st to 10th prize with consolation prizes.')

@section('styles')
<style>
/* ── Index Page ── */
.idx-hero { background: linear-gradient(135deg,#0f172a 0%,#1e3a5f 55%,#0e7490 100%); }
.idx-card  {
    display: block; border-radius: 1.25rem; border: 1px solid #e2e8f0;
    background: #fff; padding: 1.25rem; transition: all .2s;
    text-decoration: none;
}
.idx-card:hover { border-color: #6ee7b7; box-shadow: 0 4px 20px rgba(16,185,129,.1); transform: translateY(-2px); }
.idx-card .status-pill {
    display: inline-flex; align-items: center; gap: .3rem;
    border-radius: 9999px; padding: .2rem .65rem;
    font-size: .65rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase;
}
.idx-card .parsed  { border: 1px solid #bbf7d0; background: #f0fdf4; color: #15803d; }
.idx-card .waiting { border: 1px solid #fde68a; background: #fefce8; color: #92400e; }
.search-box {
    display: flex; align-items: center; gap: .75rem;
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18);
    border-radius: 9999px; padding: .55rem 1.1rem;
    backdrop-filter: blur(8px); transition: background .2s, border-color .2s;
}
.search-box:focus-within { background: rgba(255,255,255,.14); border-color: rgba(255,255,255,.35); }
.search-box input { background: transparent; border: none; outline: none; color: #fff; font-size: .875rem; flex: 1; }
.search-box input::placeholder { color: rgba(255,255,255,.45); }
.search-box button { background: #0ea5e9; border: none; border-radius: 9999px; padding: .35rem .9rem; color: #fff; font-size: .75rem; font-weight: 700; cursor: pointer; transition: background .15s; }
.search-box button:hover { background: #0284c7; }
.clear-btn { color: rgba(255,255,255,.55); font-size: .75rem; font-weight: 600; text-decoration: none; white-space: nowrap; transition: color .15s; }
.clear-btn:hover { color: #fff; }
</style>
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- ── Hero + Search ── --}}
    <div class="idx-hero rounded-[2rem] overflow-hidden shadow-xl px-5 sm:px-10 pt-8 pb-7">
        <p class="text-[10px] font-bold uppercase tracking-[.22em] text-sky-300/80">Kerala State Lotteries · Official</p>
        <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-white leading-snug">
            Kerala Lottery Results
        </h1>
        <p class="mt-2 text-sm text-sky-200/60 max-w-xl">
            Search by lottery name, draw number, or ticket number — all official results with full prize breakdown.
        </p>

        {{-- Search form --}}
        <form method="GET" action="{{ route('kerala-lottery.index') }}" class="mt-6 max-w-xl">
            <div class="search-box">
                <svg class="h-4 w-4 shrink-0 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" id="lottery-search"
                       value="{{ $search }}"
                       placeholder="e.g. Karunya, KR-758, AB 123456 …"
                       autocomplete="off"
                       autofocus="{{ $search ? 'autofocus' : false }}">
                <button type="submit">Search</button>
                @if($search)
                    <a href="{{ route('kerala-lottery.index') }}" class="clear-btn inline-flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Quick links --}}
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('kerala-lottery.today') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/20 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008z"/></svg>
                Today's Result
            </a>
            <a href="{{ route('kerala-lottery.index') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-white/5 border border-white/15 px-4 py-2 text-xs font-bold text-sky-200 transition hover:bg-white/10">
                All Results
            </a>
        </div>
    </div>

    {{-- ── Today's Featured Card ── --}}
    @if($todayResult && !$search)
        <div class="rounded-[1.75rem] border border-emerald-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 sm:px-7 pt-5 pb-4 border-b border-emerald-100 bg-emerald-50/50">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[.22em] text-emerald-600/80">Today's Result</p>
                        <h2 class="mt-1 text-xl font-extrabold text-slate-950">{{ $todayResult->lottery_name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Draw {{ $todayResult->draw_number }} · {{ optional($todayResult->result_date)->format('d F Y') }}</p>
                    </div>
                    <span class="self-start inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] {{ $todayResult->status === 'parsed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                        {{ str_replace('_', ' ', $todayResult->status) }}
                    </span>
                </div>
            </div>

            @if($todayResult->hasParsedPrizes())
                <div class="px-5 sm:px-7 pt-5 pb-3 grid gap-3 sm:grid-cols-3">
                    @foreach([
                        ['label' => '1st Prize', 'ticket' => $todayResult->first_prize_ticket,  'amount' => $todayResult->first_prize_amount,  'color' => 'amber'],
                        ['label' => '2nd Prize', 'ticket' => $todayResult->second_prize_ticket, 'amount' => $todayResult->second_prize_amount, 'color' => 'slate'],
                        ['label' => '3rd Prize', 'ticket' => $todayResult->third_prize_ticket,  'amount' => $todayResult->third_prize_amount,  'color' => 'slate'],
                    ] as $p)
                        <div class="rounded-xl border border-{{ $p['color'] }}-200 bg-{{ $p['color'] }}-50 px-4 py-3">
                            <p class="text-[10px] font-bold uppercase tracking-[.18em] text-{{ $p['color'] }}-700/70 flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.503-1.125 1.125-1.125h.872m5.007 0H9.5m5.007 0c.621 0 1.125-.503 1.125-1.125V6.75m-6.132 5.625c-.621 0-1.125-.503-1.125-1.125V6.75"/></svg>
                                {{ $p['label'] }}
                            </p>
                            <p class="mt-2 text-xl font-black text-slate-900 font-mono">{{ $p['ticket'] ?: '—' }}</p>
                            <p class="mt-1 text-xs font-semibold text-{{ $p['color'] }}-700">{{ $p['amount'] ?: '' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="px-5 sm:px-7 pb-5 pt-3">
                <a href="{{ route('kerala-lottery.show', $todayResult) }}"
                   class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-500 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-600">
                    View Full Result & Check Your Number →
                </a>
            </div>
        </div>
    @endif

    {{-- ── Results Grid ── --}}
    <div class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 sm:px-7 pt-5 pb-4 border-b border-slate-100 bg-slate-50/60">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.22em] text-sky-600/70">
                        {{ $search ? 'Search Results' : 'Recent Draws' }}
                    </p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-950">
                        @if($search)
                            Results for <span class="text-sky-700">"{{ $search }}"</span>
                        @else
                            Kerala Lottery Recent Results
                        @endif
                    </h2>
                </div>
                @if($results instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    <span class="text-xs text-slate-400 font-semibold">
                        {{ number_format($results->total()) }} result{{ $results->total() !== 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </div>

        <div class="p-5">
            @if($results instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results->count() > 0)
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($results as $result)
                        <a href="{{ route('kerala-lottery.show', $result) }}" class="idx-card">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-950 truncate">{{ $result->lottery_name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $result->draw_number }} · {{ optional($result->result_date)->format('d M Y') }}</p>
                                </div>
                                <span class="status-pill {{ $result->status === 'parsed' ? 'parsed' : 'waiting' }} shrink-0">
                                    {{ $result->status === 'parsed' ? '✓ Parsed' : str_replace('_', ' ', $result->status) }}
                                </span>
                            </div>

                            @if($result->first_prize_ticket)
                                <div class="mt-3 space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-bold uppercase tracking-[.14em] text-amber-700/80 flex items-center gap-0.5">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.503-1.125 1.125-1.125h.872m5.007 0H9.5m5.007 0c.621 0 1.125-.503 1.125-1.125V6.75"/></svg>
                                            1st
                                        </span>
                                        <span class="font-mono text-sm font-black text-slate-900">{{ $result->first_prize_ticket }}</span>
                                    </div>
                                    @if($result->second_prize_ticket)
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[10px] font-bold uppercase tracking-[.14em] text-slate-500 flex items-center gap-0.5">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.503-1.125 1.125-1.125h.872m5.007 0H9.5m5.007 0c.621 0 1.125-.503 1.125-1.125V6.75"/></svg>
                                                2nd
                                            </span>
                                            <span class="font-mono text-xs font-bold text-slate-700">{{ $result->second_prize_ticket }}</span>
                                        </div>
                                    @endif
                                    @if(!empty($result->other_prizes))
                                        <p class="text-[10px] text-slate-400 mt-1">+ {{ count($result->other_prizes) }} more prize tiers · {{ count($result->consolation_prizes ?? []) }} consolation</p>
                                    @endif
                                </div>
                            @else
                                <p class="mt-3 text-xs text-slate-400 italic">Official PDF available</p>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if($results->hasPages())
                    <div class="mt-6">
                        {{ $results->links() }}
                    </div>
                @endif

            @elseif($search)
                {{-- No search results --}}
                <div class="py-14 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mb-4">
                        <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <p class="text-base font-bold text-slate-700">No results found for "{{ $search }}"</p>
                    <p class="mt-1 text-sm text-slate-400">Try the lottery name (e.g. Karunya), draw number (KR-758), or a ticket number.</p>
                    <a href="{{ route('kerala-lottery.index') }}" class="mt-4 inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                        ← Back to all results
                    </a>
                </div>
            @else
                <div class="py-10 text-center text-sm text-slate-400">
                    Kerala lottery results will appear here after the first official PDF is fetched.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
