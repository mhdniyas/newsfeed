@extends('layouts.app')

@section('title', ($page['title'] ?? 'Trend Page') . ' - Signalz Online')
@section('meta_description', $page['description'] ?? 'Live trend page built from the latest keyword-linked news stories.')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <section class="rounded-[2rem] border border-sky-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_58%,_#eff6ff_100%)] p-6 sm:p-8 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700/70">{{ ($page['kind'] ?? 'dynamic') === 'fixed' ? 'Seeded Trend Page' : 'Live Trend Page' }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-950">{{ $page['title'] }}</h1>
        <p class="mt-3 max-w-3xl text-sm sm:text-base text-slate-600">{{ $page['description'] }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach(($page['keywords'] ?? []) as $keyword)
                <span class="inline-flex rounded-full border border-sky-200 bg-white px-3 py-1 text-xs font-bold text-sky-700">{{ $keyword }}</span>
            @endforeach
        </div>
    </section>

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Matched Stories</p>
                <h2 class="mt-1 text-2xl font-extrabold text-slate-950">{{ number_format($articles->total()) }} linked articles</h2>
            </div>
        </div>

        @if($articles->isEmpty())
            <div class="rounded-[1.8rem] border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-500 shadow-sm">
                No linked stories are available for this trend page yet.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @include('news.partials.cards')
            </div>

            @if($articles->hasPages())
                <div class="mt-8">
                    {{ $articles->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
