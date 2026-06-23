@extends('layouts.app')

@section('title', ($result?->lottery_name ? $result->lottery_name . ' Result – ' . optional($result->result_date)->format('d F Y') : 'Kerala Lottery Result Today'))
@section('meta_description', 'Official Kerala lottery result page with all prize numbers — 1st through 8th prize, consolation prizes, and ticket number checker.')

@section('styles')
<style>
/* ── Show Page ── */
.lottery-hero      { background: linear-gradient(135deg,#0f172a 0%,#1e3a5f 60%,#0e7490 100%); }
.prize-top         { background: linear-gradient(135deg,#fbbf24 0%,#f59e0b 50%,#d97706 100%); border-radius:1.25rem; }
.prize-2nd         { background: linear-gradient(135deg,#e2e8f0 0%,#cbd5e1 50%,#94a3b8 100%); border-radius:1.25rem; }
.prize-3rd         { background: linear-gradient(135deg,#fcd34d 0%,#fbbf24 50%,#b45309 100%); border-radius:1.25rem; }
.section-card      { border-radius:1.5rem; border:1px solid #e2e8f0; background:#fff; padding:1.5rem; box-shadow:0 1px 6px rgba(0,0,0,.05); }
.num-chip          { display:inline-flex; align-items:center; justify-content:center; border-radius:.5rem; border:1px solid rgba(100,116,139,.25); background:#f8fafc; padding:.25rem .55rem; font-size:.72rem; font-weight:700; letter-spacing:.05em; font-family:monospace; color:#0f172a; transition:background .15s; }
.num-chip:hover    { background:#e2e8f0; }
.num-chip.match    { background:#dcfce7; border-color:#86efac; color:#15803d; box-shadow:0 0 0 2px #bbf7d0; }
.status-pill       { display:inline-flex; align-items:center; gap:.35rem; border-radius:9999px; padding:.3rem .85rem; font-size:.68rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
.status-available   { border:1px solid #bbf7d0; background:#f0fdf4; color:#15803d; }
.status-waiting    { border:1px solid #fde68a; background:#fefce8; color:#92400e; }

@media(max-width:640px) {
    .section-card { border-radius:1rem; padding:1rem; }
    .prize-top,.prize-2nd,.prize-3rd { border-radius:1rem; }
}
</style>
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- ── Hero ── --}}
    <div class="lottery-hero rounded-[1.75rem] sm:rounded-[2rem] overflow-hidden shadow-xl">
        <div class="px-5 sm:px-8 pt-7 pb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[.22em] text-sky-300/80">
                        Kerala Lottery Result {{ $isTodayPage ? '· Today' : '· Official' }}
                    </p>
                    <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                        {{ $result?->lottery_name ?: 'Kerala Lottery Result Today' }}
                    </h1>
                    @if($result)
                        <p class="mt-2 text-sm text-sky-200/70">
                            Draw <span class="font-bold text-sky-100">{{ $result->draw_number }}</span>
                            &nbsp;·&nbsp;
                            {{ optional($result->result_date)->format('d F Y') }}
                        </p>
                    @else
                        <p class="mt-2 text-sm text-sky-200/70">Official result PDF has not been published yet for today.</p>
                    @endif
                </div>

                @php
                    $statusClass = match($result?->status) {
                        'available'     => 'status-available',
                        'pdf_available' => 'status-waiting',
                        default         => 'status-waiting',
                    };
                    $statusLabel = $result ? ($result->status === 'available' ? 'Available' : str_replace('_', ' ', $result->status)) : 'waiting';
                @endphp
                <span class="status-pill {{ $statusClass }} shrink-0 self-start">
                    @if($result?->status === 'available')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    @endif
                    {{ $statusLabel }}
                </span>
            </div>

            {{-- PDF Actions --}}
            @if($result && $result->local_pdf_path)
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('kerala-lottery.pdf.view', $result) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/20 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        View Official PDF
                    </a>
                    <a href="{{ route('kerala-lottery.pdf.download', $result) }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-white/5 border border-white/15 px-4 py-2 text-xs font-bold text-sky-200 transition hover:bg-white/10">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download PDF
                    </a>
                    <a href="{{ route('kerala-lottery.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-white/5 border border-white/15 px-4 py-2 text-xs font-bold text-sky-200 transition hover:bg-white/10">
                        ← All Results
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- ── No Result State ── --}}
    @if(!$result)
        <div class="section-card text-center py-10">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-50 mb-4">
                <svg class="h-7 w-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-base font-bold text-slate-800">Result not published yet</p>
            <p class="mt-1 text-sm text-slate-500">We are waiting for the official Kerala lottery PDF. Check back later.</p>
        </div>

    @elseif($result->hasParsedPrizes() || !empty($result->consolation_prizes) || !empty($result->other_prizes))



        @if($adsense['client'] && $adsense['tab_slot'])
            <div class="my-4">
                @include('news.partials.adsense-block', [
                    'client' => $adsense['client'],
                    'slot' => $adsense['tab_slot'],
                ])
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach([
                ['label' => '1st Prize', 'class' => 'prize-top', 'dark' => true,  'ticket' => $result->first_prize_ticket,  'amount' => $result->first_prize_amount],
                ['label' => '2nd Prize', 'class' => 'prize-2nd', 'dark' => false, 'ticket' => $result->second_prize_ticket, 'amount' => $result->second_prize_amount],
                ['label' => '3rd Prize', 'class' => 'prize-3rd', 'dark' => false, 'ticket' => $result->third_prize_ticket,  'amount' => $result->third_prize_amount],
            ] as $prize)
                <div class="{{ $prize['class'] }} p-5 shadow-md">
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] {{ $prize['dark'] ? 'text-amber-900/70' : 'text-slate-600/80' }} flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.503-1.125 1.125-1.125h.872m5.007 0H9.5m5.007 0c.621 0 1.125-.503 1.125-1.125V6.75m-6.132 5.625c-.621 0-1.125-.503-1.125-1.125V6.75"/></svg>
                        {{ $prize['label'] }}
                    </p>
                    <p class="mt-3 text-2xl sm:text-3xl font-black {{ $prize['dark'] ? 'text-amber-950' : 'text-slate-900' }} font-mono tracking-wide">
                        {{ $prize['ticket'] ?: '—' }}
                    </p>
                    <p class="mt-2 text-sm font-bold {{ $prize['dark'] ? 'text-amber-800' : 'text-slate-600' }}">
                        {{ $prize['amount'] ?: '–' }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- ── Consolation Prizes ── --}}
        @if(!empty($result->consolation_prizes))
            <div class="section-card">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-indigo-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12v.75m0 3v.75m0 3v.75m0 3V18M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 16.5v-9z"/></svg>
                    </span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[.2em] text-indigo-600/70">Consolation Prizes</p>
                        <p class="text-sm font-black text-slate-900">Full Ticket Numbers · {{ count($result->consolation_prizes) }} winners</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                    @foreach($result->consolation_prizes as $ticket)
                        <span class="num-chip w-full text-center justify-center" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;font-size:.78rem;padding:.3rem .7rem;">
                            {{ $ticket }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── 4th–8th Prizes ── --}}
        @if(!empty($result->other_prizes))
            @foreach($result->other_prizes as $prize)
                <div class="section-card">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-slate-400">Ending Numbers Match</p>
                            <p class="text-base font-black text-slate-900">{{ $prize['label'] }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($prize['amount'])
                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                    {{ $prize['amount'] }}
                                </span>
                            @endif
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                                {{ count($prize['numbers']) }} numbers
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-1.5">
                        @foreach($prize['numbers'] as $num)
                            <span class="num-chip w-full text-center justify-center">{{ $num }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

    @else
        {{-- ── PDF Only State ── --}}
        <div class="section-card">
            <div class="flex items-start gap-4 p-1">
                <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-amber-50">
                    <svg class="h-6 w-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-amber-900">Official PDF available</p>
                    <p class="mt-1 text-sm text-amber-700">Prize numbers could not be extracted automatically. Please view or download the official PDF above.</p>
                </div>
            </div>
        </div>
    @endif

    @if($adsense['client'] && $adsense['infeed_slot'])
        <div class="my-4">
            @include('news.partials.adsense-block', [
                'client' => $adsense['client'],
                'slot' => $adsense['infeed_slot'],
            ])
        </div>
    @endif

    {{-- ── Disclaimer ── --}}
    @if($result)
        <p class="text-center text-[11px] text-slate-400 pb-2">
            Prize winners should verify numbers with the Kerala Government Gazette and surrender winning tickets within 90 days.
        </p>
    @endif

</div>
@endsection
