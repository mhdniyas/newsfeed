@extends('layouts.app')

@section('title', $article->title . ' - Signalz Online')
@section('meta_description', \Illuminate\Support\Str::limit($article->description ?: implode(' ', $article->excerptParagraphs()), 155))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="relative h-64 sm:h-80 overflow-hidden bg-slate-100">
            <img src="{{ $article->displayImageUrl() }}"
                 data-proxy-src="{{ route('media.news-image', $article) }}"
                 data-placeholder-src="{{ '/media/fifa-placeholder/' . rawurlencode($article->hash ?: (string) $article->id) . '.svg' }}"
                 alt="{{ $article->title }}"
                 class="h-full w-full object-cover"
                 loading="lazy"
                 referrerpolicy="no-referrer"
                 onerror="if(this.dataset.fallbackStage==='proxy'){this.src=this.dataset.placeholderSrc;this.dataset.fallbackStage='placeholder';return;} this.dataset.fallbackStage='proxy'; this.src=this.dataset.proxySrc;">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/25 to-transparent"></div>
            <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                <div class="flex flex-wrap gap-2">
                    @if($article->newsSection)
                        <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white backdrop-blur">
                            {{ $article->newsSection->name }}
                        </span>
                    @endif
                    @if($article->newsTopic)
                        <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white backdrop-blur">
                            {{ $article->newsTopic->name }}
                        </span>
                    @endif
                </div>
                <h1 class="mt-4 max-w-4xl text-3xl sm:text-4xl font-extrabold leading-tight text-white">{{ $article->title }}</h1>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <div class="flex flex-col gap-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1 text-sm text-slate-600">
                    <p><span class="font-bold text-slate-900">Source:</span> {{ $article->source_courtesy ?: $article->source_name }}</p>
                    @if($article->extracted_author)
                        <p><span class="font-bold text-slate-900">Author:</span> {{ $article->extracted_author }}</p>
                    @endif
                    <p><span class="font-bold text-slate-900">Published:</span> {{ optional($article->published_at)->format('M d, Y H:i') ?? 'Unknown' }}</p>
                </div>
                <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                    Read Original Source
                </a>
            </div>

            @if($article->description)
                <div class="mt-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm leading-7 text-emerald-950">
                    {{ $article->description }}
                </div>
            @endif

            <div class="mt-8 space-y-5">
                @forelse($article->excerptParagraphs() as $paragraph)
                    <p class="text-base leading-8 text-slate-700">{{ $paragraph }}</p>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                        Detailed source content is still being fetched from the original article. Use the source link above for the live story.
                    </div>
                @endforelse
            </div>

            @if($article->canonical_url)
                <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-white px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Courtesy Link</p>
                    <a href="{{ $article->canonical_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 block break-all text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                        {{ $article->canonical_url }}
                    </a>
                </div>
            @endif
        </div>
    </article>

    @if($relatedArticles->isNotEmpty())
        <section class="mt-8">
            <div class="mb-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Related Stories</p>
                <h2 class="mt-1 text-2xl font-extrabold text-slate-950">More from this topic and section</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($relatedArticles as $article)
                    @include('news.partials.section-card', ['article' => $article])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
