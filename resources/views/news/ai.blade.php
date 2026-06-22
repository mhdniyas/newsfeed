@extends('layouts.app')

@section('title', 'AI News - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <section class="overflow-hidden rounded-[2.2rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.16),_transparent_30%),linear-gradient(135deg,_#020617_0%,_#0f172a_48%,_#082f49_100%)] p-5 sm:p-7 text-white shadow-[0_24px_60px_rgba(2,6,23,0.18)]">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-200">AI Feed</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-black tracking-tight">AI News</h1>
                <p class="mt-3 max-w-2xl text-sm sm:text-base leading-7 text-slate-200">{{ $aiSection?->description ?? 'Artificial intelligence platforms, research, policy, and product launches.' }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:min-w-[360px]">
                <div class="rounded-[1.6rem] border border-white/10 bg-white/10 px-5 py-4 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-sky-100/80">Stories</p>
                    <p class="mt-2 text-3xl font-black text-white">{{ number_format($articles->total()) }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-white/10 bg-white/10 px-5 py-4 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-sky-100/80">Topics</p>
                    <p class="mt-2 text-3xl font-black text-white">{{ number_format($aiTopics->count()) }}</p>
                </div>
            </div>
        </div>
        @if($aiTopics->isNotEmpty())
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach($aiTopics->take(8) as $topic)
                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold text-slate-100">
                        {{ $topic->name }} <span class="text-slate-300">({{ $topic->news_items_count }})</span>
                    </span>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-8">
        @if($articles->isEmpty())
            <div class="rounded-[2rem] border border-dashed border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <h2 class="text-xl font-black text-slate-900">No AI news yet</h2>
                <p class="mt-2 text-sm text-slate-500">Add or sync AI topics in admin and they will appear here.</p>
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
