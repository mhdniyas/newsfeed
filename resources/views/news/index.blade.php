@extends('layouts.app')

@section('title', 'Global News Explorer - FIFA 2026, World, Politics, Tech, Crypto, Sports and More')

@section('content')
@php
    $desktopPromoLeft = $homepagePromo['desktop']['left'] ?? null;
    $desktopPromoRight = $homepagePromo['desktop']['right'] ?? null;
    $mobilePromos = $homepagePromo['mobile'] ?? [];
    $heroPromo = $homepagePromo['hero'] ?? null;
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @unless($schemaReady)
        <div class="mb-8 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
            <p class="text-sm font-bold text-amber-900">News data is not ready on this server yet.</p>
            <p class="mt-1 text-xs text-amber-800/80">Run `php artisan migrate` and then start a sync from admin to populate the public homepage.</p>
        </div>
    @endunless

    <section id="home">
        <!-- Search and Filters Section -->
        <div class="bg-white border border-slate-200/80 rounded-[1.75rem] p-4 mb-8 shadow-sm">
            <form action="{{ route('news.index') }}" method="GET" class="flex flex-col gap-4">
                <div class="relative flex items-center">
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Search headlines, journalists, topics..." 
                           class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl px-4 py-3 pl-11 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all duration-200">
                    <div class="absolute left-4 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    @if($search)
                        <a href="{{ route('news.index', ['topic' => $selectedTopicId]) }}" class="absolute right-4 text-xs text-slate-450 hover:text-slate-700">
                            Clear
                        </a>
                    @endif
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-450 block mb-2 uppercase tracking-wider">Filter Sections</span>
                    <div class="flex items-center space-x-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-none snap-x select-none">
                        <a href="{{ route('news.index', ['search' => $search]) }}" 
                           class="snap-start shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 shadow-sm
                           @if(!$selectedSection && (!$selectedTopicId || $selectedTopicId === 'all')) bg-slate-950 text-white shadow-slate-950/10 @else bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50 hover:text-slate-900 @endif">
                            All Sections
                        </a>

                        @if($featuredCount > 0)
                            <a href="{{ route('news.index', ['topic' => 'featured', 'search' => $search]) }}" 
                               class="snap-start shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold flex items-center space-x-1 transition-all duration-200 shadow-sm
                               @if($selectedTopicId === 'featured') bg-amber-500 text-slate-950 shadow-amber-500/15 @else bg-amber-50/60 text-amber-700 border border-amber-200/80 hover:bg-amber-100/50 @endif">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span>Featured Updates ({{ $featuredCount }})</span>
                            </a>
                        @endif

                        @foreach($sections as $section)
                            @if($section->news_items_count > 0)
                                <a href="{{ route('news.index', ['section' => $section->slug, 'search' => $search]) }}" 
                                   class="snap-start shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 shadow-sm
                                   @if($selectedSection && $selectedSection->id === $section->id) bg-emerald-500 text-slate-950 shadow-emerald-500/10 @else bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50 hover:text-slate-900 @endif">
                                    {{ $section->name }} <span class="opacity-60 font-medium ml-0.5">({{ $section->news_items_count }})</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if($selectedSection && $topics->isNotEmpty())
                    <div>
                        <span class="text-xs font-semibold text-slate-450 block mb-2 uppercase tracking-wider">Filter Topics In {{ $selectedSection->name }}</span>
                        <div class="flex items-center space-x-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-none snap-x select-none">
                            <a href="{{ route('news.index', ['section' => $selectedSection->slug, 'search' => $search]) }}"
                               class="snap-start shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all duration-200 shadow-sm
                               @if(!$selectedTopicId || $selectedTopicId === 'all') bg-emerald-600 text-slate-950 shadow-emerald-500/10 @else bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50 hover:text-slate-900 @endif">
                                All {{ $selectedSection->name }}
                            </a>
                            @foreach($topics as $topic)
                                @if($topic->news_items_count > 0)
                                    <a href="{{ route('news.index', ['section' => $selectedSection->slug, 'topic' => $topic->id, 'search' => $search]) }}"
                                       class="snap-start shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all duration-200 shadow-sm
                                       @if((string)$selectedTopicId === (string)$topic->id) bg-emerald-600 text-slate-950 shadow-emerald-500/10 @else bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50 hover:text-slate-900 @endif">
                                        {{ $topic->name }} <span class="opacity-60 font-medium ml-0.5">({{ $topic->news_items_count }})</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($selectedTopicId)
                    <input type="hidden" name="topic" value="{{ $selectedTopicId }}">
                @endif
                @if($selectedSection)
                    <input type="hidden" name="section" value="{{ $selectedSection->slug }}">
                @endif
            </form>
        </div>

        @if($adsense['client'] && $adsense['tab_slot'])
            <div class="mb-8">
                @include('news.partials.adsense-block', [
                    'client' => $adsense['client'],
                    'slot' => $adsense['tab_slot'],
                    'label' => 'Advertisement',
                    'variant' => 'hero',
                ])
            </div>
        @endif

        @if(isset($trendPages) && $trendPages->isNotEmpty())
            <section class="mb-8 rounded-[1.9rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700/70">Trending Pages</p>
                        <h2 class="mt-1 text-2xl font-extrabold text-slate-950">Most searched topics right now</h2>
                        <p class="mt-1 text-sm text-slate-500">Open dynamic trend pages built from live keywords and source-linked stories.</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($trendPages as $trendPage)
                        <a href="{{ route('news.trend-page', $trendPage['slug']) }}" class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100">
                            {{ $trendPage['title'] }}
                        </a>
                    @endforeach
                </div>
                <p class="mt-4 text-xs font-medium text-slate-500">Story cards below open internal article pages first, then the original source link is available inside each page.</p>
            </section>
        @endif

        <section class="mb-8 rounded-[1.9rem] border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-slate-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700/70">New Section</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-slate-950">Kerala Lottery Results</h2>
                    <p class="mt-1 text-sm text-slate-500">Open today&apos;s Kerala lottery result page with official PDF view and top prize numbers when parsing succeeds.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('kerala-lottery.today') }}" class="inline-flex items-center rounded-full border border-slate-950 bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800">
                        Today Result
                    </a>
                    <a href="{{ route('kerala-lottery.index') }}" class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-4 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">
                        All Kerala Lottery Results
                    </a>
                </div>
            </div>
        </section>

        <section class="mb-8 rounded-[1.9rem] border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-yellow-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-700/70">Daily Prices</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-slate-950">Gold Rates Today</h2>
                    <p class="mt-1 text-sm text-slate-500">Check India, Mumbai, Delhi, Chennai, and Kerala gold prices with 24K, 22K, and 18K comparisons.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('news.gold-rate.index') }}" class="inline-flex items-center rounded-full border border-slate-950 bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800">
                        Open Gold Rates
                    </a>
                    <a href="{{ route('news.gold-rate', 'kerala') }}" class="inline-flex items-center rounded-full border border-amber-200 bg-white px-4 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-50">
                        Kerala Gold Rate
                    </a>
                </div>
            </div>
        </section>

        @if(!empty($mobilePromos))
            <div class="mb-8 space-y-4 xl:hidden">
                @foreach($mobilePromos as $mobilePromo)
                    @include('news.partials.promo-card', ['card' => $mobilePromo, 'variant' => 'mobile'])
                @endforeach
            </div>
        @endif

        <div class="grid gap-8 xl:grid-cols-[260px_minmax(0,1fr)_260px]">
            <aside class="hidden xl:block">
                <div class="sticky top-32 space-y-4">
                    @if(!empty($desktopPromoLeft['enabled']))
                        @include('news.partials.promo-card', ['card' => $desktopPromoLeft, 'variant' => 'sidebar'])
                    @endif
                </div>
            </aside>

            <div class="min-w-0">
        @if($showSectionLanding)
            <div class="space-y-8" id="section-landing">
                @foreach($homepageSections as $sectionIndex => $section)
                    @php($leadArticle = $section->latestArticles->first())
                    @php($supportingArticles = $section->latestArticles->slice(1))
                    <section
                        class="rounded-[2rem] border border-slate-200 bg-white p-4 sm:p-5 shadow-sm"
                        id="section-block-{{ $section->id }}"
                        data-section-id="{{ $section->id }}"
                        data-next-offset="5"
                        data-more-url="{{ route('news.section.more', $section) }}"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-5">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700/70">Live Section</p>
                                <h2 class="mt-1 text-2xl font-extrabold text-slate-950">{{ $section->name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $section->description }}</p>
                            </div>
                            <a href="{{ route('news.index', ['section' => $section->slug]) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 shrink-0">
                                View All
                            </a>
                        </div>

                        @if($leadArticle)
                            {{-- Hero lead --}}
                            <article class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-950 text-white shadow-lg shadow-slate-950/10 mb-4">
                                <a href="{{ route('news.article', ['article' => $leadArticle->slug]) }}" class="block">
                                    <div class="relative h-56 sm:h-64 overflow-hidden">
                                        <img src="{{ $leadArticle->image_url ?: route('media.news-image', $leadArticle) }}"
                                             data-proxy-src="{{ route('media.news-image', $leadArticle) }}"
                                             data-placeholder-src="{{ '/media/fifa-placeholder/' . rawurlencode($leadArticle->hash ?: (string) $leadArticle->id) . '.svg' }}"
                                             alt="{{ $leadArticle->title }}"
                                             class="h-full w-full object-cover opacity-85"
                                             loading="lazy" referrerpolicy="no-referrer"
                                             onerror="if(this.dataset.fallbackStage==='proxy'){this.src=this.dataset.placeholderSrc;this.dataset.fallbackStage='placeholder';return;} this.dataset.fallbackStage='proxy'; this.src=this.dataset.proxySrc;}">
                                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                                        <div class="absolute inset-x-0 bottom-0 p-5">
                                            <span class="inline-flex items-center rounded-full bg-white/12 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white backdrop-blur">{{ $leadArticle->source_name }}</span>
                                            <h3 class="mt-3 text-xl font-extrabold leading-tight">{{ $leadArticle->title }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-200 line-clamp-2">{{ $leadArticle->description ?? 'Open the story for full details.' }}</p>
                                        </div>
                                    </div>
                                </a>
                            </article>

                            {{-- Supporting articles list --}}
                            <div class="section-more-list grid gap-3 sm:grid-cols-2">
                                @foreach($supportingArticles as $article)
                                    @include('news.partials.section-card', ['article' => $article])
                                @endforeach
                            </div>
                        @endif

                        @if($section->news_items_count > 5)
                            {{-- Load More button --}}
                            <div class="section-load-more-wrap mt-5 flex items-center justify-center gap-4">
                                <button type="button"
                                    class="section-load-more-btn inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition-all duration-200 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 active:scale-95"
                                    data-section-block="section-block-{{ $section->id }}">
                                    <svg class="w-3.5 h-3.5 btn-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                    <span class="btn-label">Load More from {{ $section->name }}</span>
                                    <svg class="hidden w-3.5 h-3.5 animate-spin btn-spinner" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </button>
                                <span class="section-count-badge text-[11px] text-slate-400 font-medium">
                                    Showing <span class="section-shown font-bold text-slate-600">5</span> of <span class="section-total font-bold text-slate-600">{{ $section->news_items_count }}</span>
                                </span>
                            </div>
                        @endif
                    </section>

                    @if($sectionIndex === 0 && !empty($heroPromo['enabled']))
                        @include('news.partials.promo-card', ['card' => $heroPromo, 'variant' => 'hero'])
                    @endif
                @endforeach
            </div>
        @else
            <div id="news-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @if($articles->isEmpty())
                    <div class="col-span-full py-16 text-center border border-dashed border-slate-200 rounded-3xl bg-white shadow-sm">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100 text-slate-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 012 2v8a2 2 0 01-2 2h-3m-6 0a1 1 0 001-1V7a1 1 0 00-1-1H3" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-700">No news articles found</h3>
                        <p class="text-slate-450 text-sm mt-1 max-w-sm mx-auto">
                            Try changing your section filter, clearing search, or running a manual fetch in the admin panel.
                        </p>
                    </div>
                @else
                    @include('news.partials.cards')
                @endif
            </div>

            @if($articles->hasMorePages())
                <div class="flex justify-center mt-12" id="load-more-container">
                    <button id="load-more-btn" 
                            data-page="1" 
                            data-topic="{{ $selectedTopicId }}" 
                            data-section="{{ $selectedSection?->slug }}"
                            data-search="{{ $search }}" 
                            class="relative overflow-hidden inline-flex items-center justify-center px-6 py-3 text-sm font-bold rounded-xl bg-white text-emerald-600 border border-slate-200 hover:border-emerald-500/40 hover:bg-slate-50 active:scale-98 transition-all duration-200 shadow-sm group cursor-pointer">
                        <span class="flex items-center space-x-2" id="btn-text">
                            <span>Load More Stories</span>
                            <svg class="w-4 h-4 animate-bounce mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 13l-7 7-7-7m14-6l-7 7-7-7" />
                            </svg>
                        </span>
                        <span class="hidden flex items-center space-x-2" id="btn-loading">
                            <svg class="animate-spin h-4 w-4 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Fetching updates...</span>
                        </span>
                    </button>
                </div>
            @endif
        @endif
            </div>

            <aside class="hidden xl:block">
                <div class="sticky top-32 space-y-4">
                    @if(!empty($desktopPromoRight['enabled']))
                        @include('news.partials.promo-card', ['card' => $desktopPromoRight, 'variant' => 'sidebar'])
                    @endif
                </div>
            </aside>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loadMoreBtn = document.getElementById('load-more-btn');
        const loadMoreContainer = document.getElementById('load-more-container');
        const newsGrid = document.getElementById('news-grid');
        const btnText = document.getElementById('btn-text');
        const btnLoading = document.getElementById('btn-loading');

        if (window.adsbygoogle) {
            document.querySelectorAll('.adsbygoogle').forEach(() => {
                try {
                    (adsbygoogle = window.adsbygoogle || []).push({});
                } catch (error) {}
            });
        }

        // ── Per-section Load More ──────────────────────────────────────────
        document.querySelectorAll('.section-load-more-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const blockId = btn.dataset.sectionBlock;
                const block   = document.getElementById(blockId);
                if (!block) return;

                const url        = block.dataset.moreUrl;
                const offset     = parseInt(block.dataset.nextOffset, 10);
                const list       = block.querySelector('.section-more-list');
                const wrap       = block.querySelector('.section-load-more-wrap');
                const badge      = block.querySelector('.section-count-badge');
                const shownEl    = block.querySelector('.section-shown');
                const totalEl    = block.querySelector('.section-total');
                const chevron    = btn.querySelector('.btn-chevron');
                const spinner    = btn.querySelector('.btn-spinner');

                btn.disabled = true;
                chevron.classList.add('hidden');
                spinner.classList.remove('hidden');

                fetch(url + '?offset=' + offset, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : Promise.reject(r))
                .then(data => {
                    if (data.html) {
                        list.insertAdjacentHTML('beforeend', data.html);
                    }
                    block.dataset.nextOffset = data.nextOffset;
                    const shown = Math.min(data.nextOffset, data.total);
                    if (shownEl) shownEl.textContent = shown;
                    if (totalEl) totalEl.textContent = data.total;
                    badge.classList.remove('hidden');

                    if (!data.hasMore) {
                        wrap.remove();
                    } else {
                        btn.disabled = false;
                        chevron.classList.remove('hidden');
                        spinner.classList.add('hidden');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    chevron.classList.remove('hidden');
                    spinner.classList.add('hidden');
                });
            });
        });

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                let currentPage = parseInt(loadMoreBtn.getAttribute('data-page'));
                let nextPage = currentPage + 1;
                let topic = loadMoreBtn.getAttribute('data-topic') || '';
                let section = loadMoreBtn.getAttribute('data-section') || '';
                let search = loadMoreBtn.getAttribute('data-search') || '';

                loadMoreBtn.disabled = true;
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');

                let url = `{{ route('news.index') }}?page=${nextPage}&topic=${topic}&section=${section}&search=${search}&ajax=1`;

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    newsGrid.insertAdjacentHTML('beforeend', data.html);
                    loadMoreBtn.setAttribute('data-page', nextPage);
                    loadMoreBtn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnLoading.classList.add('hidden');
                    if (!data.hasMorePages) {
                        loadMoreContainer.remove();
                    }
                })
                .catch(error => {
                    console.error('Error loading more articles:', error);
                    loadMoreBtn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnLoading.classList.add('hidden');
                    alert('Could not load more stories. Please try again.');
                });
            });
        }
    });
</script>
@endsection
