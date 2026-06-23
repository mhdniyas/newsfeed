@extends('layouts.app')

@section('title', 'Kerala Lottery Results Today')
@section('meta_description', 'Kerala lottery results today with official PDF view, download links, and parsed top prize numbers when available.')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700/70">Official Kerala Lottery</p>
        <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-950">Kerala Lottery Result Today</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">Official PDF-first results with parsed top prize numbers when extraction succeeds.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('kerala-lottery.today') }}" class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                    Open Today Result
                </a>
            </div>
        </div>
    </section>

    @if($todayResult)
        <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Latest Published</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-950">{{ $todayResult->lottery_name }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Draw {{ $todayResult->draw_number }} · {{ optional($todayResult->result_date)->format('d F Y') }}</p>
                </div>
                <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] {{ $todayResult->status === 'parsed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                    {{ str_replace('_', ' ', $todayResult->status) }}
                </span>
            </div>

            @if($todayResult->hasParsedPrizes())
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach([
                        ['label' => '1st Prize', 'ticket' => $todayResult->first_prize_ticket, 'amount' => $todayResult->first_prize_amount],
                        ['label' => '2nd Prize', 'ticket' => $todayResult->second_prize_ticket, 'amount' => $todayResult->second_prize_amount],
                        ['label' => '3rd Prize', 'ticket' => $todayResult->third_prize_ticket, 'amount' => $todayResult->third_prize_amount],
                    ] as $prize)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $prize['label'] }}</p>
                            <p class="mt-3 text-2xl font-black text-slate-950">{{ $prize['ticket'] ?: 'Waiting' }}</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-700">{{ $prize['amount'] ?: 'Official PDF only' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-5">
                <a href="{{ route('kerala-lottery.show', $todayResult) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Open Full Result Page
                </a>
            </div>
        </section>
    @endif

    <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700/70">Past Results</p>
                <h2 class="mt-1 text-2xl font-extrabold text-slate-950">Recent Kerala lottery draws</h2>
            </div>
        </div>

        @if($results instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results->count() > 0)
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($results as $result)
                    <a href="{{ route('kerala-lottery.show', $result) }}" class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-white hover:shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-slate-950">{{ $result->lottery_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $result->draw_number }} · {{ optional($result->result_date)->format('d M Y') }}</p>
                            </div>
                            <span class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] {{ $result->status === 'parsed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600' }}">
                                {{ str_replace('_', ' ', $result->status) }}
                            </span>
                        </div>
                        <div class="mt-4 space-y-1 text-sm text-slate-600">
                            <p><span class="font-semibold text-slate-900">1st Prize:</span> {{ $result->first_prize_ticket ?: 'Official PDF only' }}</p>
                            <p><span class="font-semibold text-slate-900">2nd Prize:</span> {{ $result->second_prize_ticket ?: 'Official PDF only' }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $results->links() }}
            </div>
        @else
            <div class="mt-5 rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                Kerala lottery results will appear here after the first official PDF is fetched.
            </div>
        @endif
    </section>
</div>
@endsection
