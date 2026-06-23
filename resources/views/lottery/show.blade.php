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
.status-parsed     { border:1px solid #bbf7d0; background:#f0fdf4; color:#15803d; }
.status-waiting    { border:1px solid #fde68a; background:#fefce8; color:#92400e; }

/* ── Number Checker ── */
#checker-box { border-radius:1.5rem; border:2px solid #e0e7ff; background:linear-gradient(135deg,#eef2ff 0%,#fff 100%); padding:1.5rem; }
#checker-input {
    width:100%; border:2px solid #c7d2fe; border-radius:.875rem; padding:.75rem 1rem;
    font-size:1.05rem; font-weight:700; font-family:monospace; letter-spacing:.08em;
    text-transform:uppercase; outline:none; transition:border-color .2s, box-shadow .2s;
    background:#fff; color:#1e1b4b;
}
#checker-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
#checker-input::placeholder { color:#a5b4fc; font-weight:400; letter-spacing:0; }
#checker-btn {
    width:100%; border-radius:.875rem; background:#6366f1; border:none; padding:.85rem;
    font-size:.9rem; font-weight:800; color:#fff; cursor:pointer; transition:background .15s, transform .1s;
    margin-top:.75rem;
}
#checker-btn:hover  { background:#4f46e5; }
#checker-btn:active { transform:scale(.98); }
#checker-result { margin-top:1rem; border-radius:1rem; padding:1rem 1.25rem; display:none; }
#checker-result.win   { background:#f0fdf4; border:1.5px solid #86efac; color:#14532d; }
#checker-result.lose  { background:#fef2f2; border:1.5px solid #fca5a5; color:#7f1d1d; }
#checker-result.info  { background:#f0f9ff; border:1.5px solid #7dd3fc; color:#0c4a6e; }
#checker-result .prize-label { font-size:1.1rem; font-weight:900; }
#checker-result .prize-amount { font-size:.85rem; font-weight:700; margin-top:.2rem; }
#checker-result .prize-note  { font-size:.75rem; margin-top:.4rem; opacity:.75; }

@media(max-width:640px) {
    .section-card { border-radius:1rem; padding:1rem; }
    .prize-top,.prize-2nd,.prize-3rd { border-radius:1rem; }
    #checker-box { border-radius:1rem; padding:1rem; }
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
                        'parsed'        => 'status-parsed',
                        'pdf_available' => 'status-waiting',
                        default         => 'status-waiting',
                    };
                    $statusLabel = $result ? str_replace('_', ' ', $result->status) : 'waiting';
                @endphp
                <span class="status-pill {{ $statusClass }} shrink-0 self-start">
                    @if($result?->status === 'parsed')
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

        {{-- ── Check Your Number Widget ── --}}
        @php
            // Build a flat JS-safe data structure for the checker
            $checkerData = [];

            // 1st prize — full ticket
            if ($result->first_prize_ticket) {
                $checkerData[] = ['type' => 'full', 'prize' => '1st Prize', 'amount' => $result->first_prize_amount ?? '', 'value' => strtoupper($result->first_prize_ticket)];
            }
            // 2nd prize — full ticket
            if ($result->second_prize_ticket) {
                $checkerData[] = ['type' => 'full', 'prize' => '2nd Prize', 'amount' => $result->second_prize_amount ?? '', 'value' => strtoupper($result->second_prize_ticket)];
            }
            // 3rd prize — full ticket
            if ($result->third_prize_ticket) {
                $checkerData[] = ['type' => 'full', 'prize' => '3rd Prize', 'amount' => $result->third_prize_amount ?? '', 'value' => strtoupper($result->third_prize_ticket)];
            }
            // Consolation — full tickets
            foreach ($result->consolation_prizes ?? [] as $t) {
                $checkerData[] = ['type' => 'full', 'prize' => 'Consolation Prize', 'amount' => '', 'value' => strtoupper(trim($t))];
            }
            // 4th-8th — ending numbers only
            foreach ($result->other_prizes ?? [] as $tier) {
                foreach ($tier['numbers'] ?? [] as $n) {
                    $checkerData[] = ['type' => 'ending', 'prize' => $tier['label'], 'amount' => $tier['amount'] ?? '', 'value' => strtoupper(trim($n))];
                }
            }
        @endphp

        <div id="checker-box">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 shrink-0">
                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-indigo-600/80">Ticket Checker</p>
                    <p class="text-base font-extrabold text-slate-900">Check Your Lottery Number</p>
                </div>
            </div>
            <p class="text-xs text-slate-500 mb-4">
                Enter your full ticket number (e.g. <span class="font-mono font-bold">BK 304203</span>) to check all prize levels instantly.
                For 4th–8th prizes, only the last 4 digits are checked.
            </p>
            <input type="text" id="checker-input" maxlength="12"
                   placeholder="e.g. BK 304203"
                   autocomplete="off" autocorrect="off" spellcheck="false">
            <button id="checker-btn" type="button">🎯 Check My Number</button>
            <div id="checker-result"></div>
        </div>

        <script>
        (function() {
            const DATA   = @json($checkerData);
            const input  = document.getElementById('checker-input');
            const btn    = document.getElementById('checker-btn');
            const result = document.getElementById('checker-result');

            function normalise(s) {
                return s.replace(/\s+/g, '').toUpperCase();
            }

            function check() {
                const raw   = input.value.trim();
                const clean = normalise(raw);

                if (clean.length < 2) {
                    result.style.display = 'none';
                    return;
                }

                // Extract last 4 digits for ending-number prizes
                const last4 = clean.replace(/[^0-9]/g, '').slice(-4);

                let wins = [];

                for (const entry of DATA) {
                    const val = normalise(entry.value);

                    if (entry.type === 'full') {
                        // Full match ignoring spaces
                        if (val === clean) {
                            wins.push(entry);
                        }
                    } else {
                        // Ending number match — last 4 digits of ticket vs stored 4-digit code
                        if (last4.length === 4 && val === last4) {
                            wins.push(entry);
                        }
                    }
                }

                result.style.display = 'block';

                if (wins.length > 0) {
                    // Show all matching prizes
                    const lines = wins.map(w => {
                        const note = w.type === 'ending'
                            ? `<div class="prize-note">Matching on last 4 digits (${last4}) — verify full ticket with official gazette.</div>`
                            : `<div class="prize-note">Full ticket match — visit Kerala lottery office within 90 days.</div>`;
                        return `<div style="margin-bottom:.5rem">
                            <div class="prize-label">🎉 ${w.prize}</div>
                            ${w.amount ? `<div class="prize-amount">${w.amount}</div>` : ''}
                            ${note}
                        </div>`;
                    }).join('<hr style="border:none;border-top:1px solid rgba(0,0,0,.1);margin:.5rem 0">');

                    result.className = 'win';
                    result.innerHTML = lines;

                    // Highlight matching chips
                    highlightChips(wins, last4, clean);
                } else {
                    result.className = 'lose';
                    result.innerHTML = `
                        <div class="prize-label">😔 No Prize Found</div>
                        <div class="prize-note" style="margin-top:.5rem;opacity:1">
                            The number <strong>${raw}</strong> did not match any prize in this draw.
                            Please double-check your ticket and verify with the official Kerala Lottery PDF.
                        </div>`;
                    clearHighlights();
                }
            }

            function highlightChips(wins, last4, fullClean) {
                clearHighlights();
                document.querySelectorAll('.num-chip').forEach(chip => {
                    const chipVal = normalise(chip.textContent);
                    // Check if any win matches this chip
                    for (const w of wins) {
                        if (normalise(w.value) === chipVal) {
                            chip.classList.add('match');
                        }
                    }
                });
            }

            function clearHighlights() {
                document.querySelectorAll('.num-chip.match').forEach(c => c.classList.remove('match'));
            }

            btn.addEventListener('click', check);
            input.addEventListener('keydown', e => { if (e.key === 'Enter') check(); });
            input.addEventListener('input', () => {
                if (input.value.trim() === '') { result.style.display = 'none'; clearHighlights(); }
            });
        })();
        </script>

        {{-- ── Top 3 Prizes ── --}}
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach([
                ['label' => '1st Prize', 'class' => 'prize-top', 'dark' => true,  'ticket' => $result->first_prize_ticket,  'amount' => $result->first_prize_amount,  'icon' => '🥇'],
                ['label' => '2nd Prize', 'class' => 'prize-2nd', 'dark' => false, 'ticket' => $result->second_prize_ticket, 'amount' => $result->second_prize_amount, 'icon' => '🥈'],
                ['label' => '3rd Prize', 'class' => 'prize-3rd', 'dark' => false, 'ticket' => $result->third_prize_ticket,  'amount' => $result->third_prize_amount,  'icon' => '🥉'],
            ] as $prize)
                <div class="{{ $prize['class'] }} p-5 shadow-md">
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] {{ $prize['dark'] ? 'text-amber-900/70' : 'text-slate-600/80' }}">
                        {{ $prize['icon'] }} {{ $prize['label'] }}
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
                    <span class="text-lg">🎟️</span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[.2em] text-indigo-600/70">Consolation Prizes</p>
                        <p class="text-sm font-black text-slate-900">Full Ticket Numbers · {{ count($result->consolation_prizes) }} winners</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($result->consolation_prizes as $ticket)
                        <span class="num-chip" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;font-size:.78rem;padding:.3rem .7rem;">
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
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($prize['numbers'] as $num)
                            <span class="num-chip">{{ $num }}</span>
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

    {{-- ── Disclaimer ── --}}
    @if($result)
        <p class="text-center text-[11px] text-slate-400 pb-2">
            Prize winners should verify numbers with the Kerala Government Gazette and surrender winning tickets within 90 days.
        </p>
    @endif

</div>
@endsection
