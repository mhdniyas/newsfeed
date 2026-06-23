@extends('layouts.app')

@section('title', ($result?->lottery_name ? $result->lottery_name . ' Result' : 'Kerala Lottery Result Today'))
@section('meta_description', 'Official Kerala lottery result page with prize numbers when parsed and PDF view/download fallback.')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700/70">Kerala Lottery Result {{ $isTodayPage ? 'Today' : 'Detail' }}</p>
                <h1 class="mt-2 text-3xl font-extrabold text-slate-950">
                    {{ $result?->lottery_name ?: 'Kerala Lottery Result Today' }}
                </h1>
                @if($result)
                    <p class="mt-2 text-sm text-slate-500">Lottery: {{ $result->lottery_name }} · Draw: {{ $result->draw_number }} · Date: {{ optional($result->result_date)->format('d F Y') }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-500">Official result PDF has not been published yet for today.</p>
                @endif
            </div>
            <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] {{ $result && $result->status === 'parsed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                {{ $result ? str_replace('_', ' ', $result->status) : 'waiting' }}
            </span>
        </div>

        @if(!$result)
            <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                We are waiting for the official Kerala lottery PDF to be published. Check back later.
            </div>
        @elseif($result->hasParsedPrizes())
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach([
                    ['label' => '1st Prize', 'ticket' => $result->first_prize_ticket, 'amount' => $result->first_prize_amount],
                    ['label' => '2nd Prize', 'ticket' => $result->second_prize_ticket, 'amount' => $result->second_prize_amount],
                    ['label' => '3rd Prize', 'ticket' => $result->third_prize_ticket, 'amount' => $result->third_prize_amount],
                ] as $prize)
                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $prize['label'] }}</p>
                        <p class="mt-3 text-3xl font-black text-slate-950">{{ $prize['ticket'] ?: 'Not found' }}</p>
                        <p class="mt-2 text-sm font-semibold text-emerald-700">{{ $prize['amount'] ?: 'Official prize amount in PDF' }}</p>
                    </div>
                @endforeach
            </div>

            @if(!empty($result->consolation_prizes))
                <div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-white p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Consolation Prizes</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($result->consolation_prizes as $ticket)
                            <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700">{{ $ticket }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="mt-6 rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                <p class="font-bold text-amber-900">Official result PDF is available.</p>
                <p class="mt-2">We could not extract prize numbers automatically. Please view or download the official PDF below.</p>
            </div>
        @endif

        @if($result && $result->local_pdf_path)
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('kerala-lottery.pdf.view', $result) }}" target="_blank" class="inline-flex items-center rounded-full border border-slate-950 bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800">
                    View Official PDF
                </a>
                <a href="{{ route('kerala-lottery.pdf.download', $result) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                    Download PDF
                </a>
            </div>
        @endif
    </section>
</div>
@endsection
