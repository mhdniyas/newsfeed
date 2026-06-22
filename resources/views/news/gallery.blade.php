@extends('layouts.app')

@section('title', 'News Gallery - FIFA World Cup 2026 Recovered Media')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <section class="overflow-hidden rounded-[2.2rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.14),_transparent_30%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_58%,_#ecfeff_100%)] p-5 sm:p-7 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Recovered Media</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-black tracking-tight text-slate-950">News Gallery</h1>
                <p class="mt-3 max-w-2xl text-sm sm:text-base leading-7 text-slate-600">Fallback image extraction and FIFA official feed results.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:min-w-[340px]">
                <div class="rounded-[1.6rem] border border-emerald-200 bg-white/90 px-5 py-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/70">Recovered Images</p>
                    <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($galleryStats['recovered_images']) }}</p>
                </div>
                <a href="{{ route('news.index') }}" class="rounded-[1.6rem] border border-slate-200 bg-white/90 px-5 py-4 shadow-sm transition hover:border-emerald-200 hover:bg-white">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Back To News</p>
                    <p class="mt-2 text-base font-black text-slate-950">Open News Feed</p>
                    <p class="mt-1 text-xs text-slate-500">Return to the homepage sections and latest stories.</p>
                </a>
            </div>
        </div>
    </section>

    <section class="mt-8">
        @if($articles->isEmpty())
            <div class="rounded-[2rem] border border-dashed border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <h2 class="text-xl font-black text-slate-900">No gallery images yet</h2>
                <p class="mt-2 text-sm text-slate-500">Run a sync and recovered images will appear here.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($articles as $article)
                    <article class="group overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg hover:shadow-slate-200/70">
                        <a href="{{ route('news.visit', $article) }}" target="_blank" rel="noopener noreferrer" class="block">
                            <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                <img src="{{ route('media.news-image', $article) }}"
                                     alt="{{ $article->title }}"
                                     class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                     loading="lazy"
                                     referrerpolicy="no-referrer">
                                <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-950/85 to-transparent"></div>
                                <div class="absolute bottom-3 left-3 inline-flex rounded-full border border-white/15 bg-black/45 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-white backdrop-blur">
                                    {{ $article->source_name }}
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                    <span>{{ $article->newsSection?->name ?? 'News' }}</span>
                                    <span>{{ $article->published_at?->diffForHumans() }}</span>
                                </div>
                                <h2 class="mt-2 text-base font-black leading-6 text-slate-950 line-clamp-3">{{ $article->title }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-500 line-clamp-2">{{ $article->description ?? 'Open the article to read the full story.' }}</p>
                            </div>
                        </a>
                    </article>
                @endforeach
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
