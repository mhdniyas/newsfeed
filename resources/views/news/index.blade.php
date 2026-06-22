@extends('layouts.app')

@section('title', 'Global News Explorer - FIFA 2026, World, Politics, Tech, Crypto, Sports and More')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="rounded-3xl border border-slate-200 bg-white/90 p-3 sm:p-4 shadow-sm mb-8 backdrop-blur">
        <div class="flex flex-wrap gap-2">
            <button type="button" data-tab-trigger="news" class="tab-trigger rounded-2xl px-4 py-3 text-sm font-extrabold bg-emerald-500 text-slate-950">
                News
            </button>
            <button type="button" data-tab-trigger="fixtures" class="tab-trigger rounded-2xl px-4 py-3 text-sm font-bold border border-slate-200 text-slate-600">
                Fixtures
            </button>
            <button type="button" data-tab-trigger="scores" class="tab-trigger rounded-2xl px-4 py-3 text-sm font-bold border border-slate-200 text-slate-600">
                Live Score
            </button>
        </div>
    </div>

    <section id="home" data-tab-panel="news">
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
            <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                <ins class="adsbygoogle block"
                     style="display:block"
                     data-ad-client="{{ $adsense['client'] }}"
                     data-ad-slot="{{ $adsense['tab_slot'] }}"
                     data-ad-format="auto"
                     data-full-width-responsive="true"></ins>
            </div>
        @endif

        @if($showSectionLanding)
            <div class="space-y-8">
                @foreach($homepageSections as $section)
                    @php($leadArticle = $section->latestArticles->first())
                    @php($supportingArticles = $section->latestArticles->slice(1))
                    <section class="rounded-[2rem] border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-5">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700/70">Live Section</p>
                                <h2 class="mt-1 text-2xl font-extrabold text-slate-950">{{ $section->name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $section->description }}</p>
                            </div>
                            <a href="{{ route('news.index', ['section' => $section->slug]) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                                View All
                            </a>
                        </div>
                        @if($leadArticle)
                            <div class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                                <article class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-950 text-white shadow-lg shadow-slate-950/10">
                                    <a href="{{ route('news.visit', $leadArticle) }}" target="_blank" rel="noopener noreferrer" class="block">
                                        <div class="relative h-64 sm:h-72 overflow-hidden">
                                            <img src="{{ $leadArticle->image_url ?: route('media.news-image', $leadArticle) }}"
                                                 data-proxy-src="{{ route('media.news-image', $leadArticle) }}"
                                                 data-placeholder-src="{{ '/media/fifa-placeholder/' . rawurlencode($leadArticle->hash ?: (string) $leadArticle->id) . '.svg' }}"
                                                 alt="{{ $leadArticle->title }}"
                                                 class="h-full w-full object-cover opacity-85"
                                                 loading="lazy"
                                                 referrerpolicy="no-referrer"
                                                 onerror="if(this.dataset.fallbackStage==='proxy'){this.src=this.dataset.placeholderSrc;this.dataset.fallbackStage='placeholder';return;} this.dataset.fallbackStage='proxy'; this.src=this.dataset.proxySrc;">
                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                                            <div class="absolute inset-x-0 bottom-0 p-5">
                                                <span class="inline-flex items-center rounded-full bg-white/12 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white backdrop-blur">{{ $leadArticle->source_name }}</span>
                                                <h3 class="mt-3 text-2xl font-extrabold leading-tight">{{ $leadArticle->title }}</h3>
                                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200 line-clamp-3">{{ $leadArticle->description ?? 'Open the story for full details.' }}</p>
                                            </div>
                                        </div>
                                    </a>
                                </article>

                                <div class="grid gap-3">
                                    @foreach($supportingArticles as $article)
                                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-3 shadow-sm transition-all duration-200 hover:border-emerald-200 hover:bg-white">
                                            <a href="{{ route('news.visit', $article) }}" target="_blank" rel="noopener noreferrer" class="flex items-start gap-3">
                                                <img src="{{ $article->image_url ?: route('media.news-image', $article) }}"
                                                     data-proxy-src="{{ route('media.news-image', $article) }}"
                                                     data-placeholder-src="{{ '/media/fifa-placeholder/' . rawurlencode($article->hash ?: (string) $article->id) . '.svg' }}"
                                                     alt="{{ $article->title }}"
                                                     class="h-20 w-20 rounded-2xl object-cover shrink-0"
                                                     loading="lazy"
                                                     referrerpolicy="no-referrer"
                                                     onerror="if(this.dataset.fallbackStage==='proxy'){this.src=this.dataset.placeholderSrc;this.dataset.fallbackStage='placeholder';return;} this.dataset.fallbackStage='proxy'; this.src=this.dataset.proxySrc;">
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                                        <span>{{ $article->source_name }}</span>
                                                        <span>{{ $article->published_at->diffForHumans() }}</span>
                                                    </div>
                                                    <h3 class="mt-2 text-sm font-extrabold leading-5 text-slate-900 line-clamp-3">{{ $article->title }}</h3>
                                                    <p class="mt-2 text-xs leading-5 text-slate-500 line-clamp-2">{{ $article->description ?? 'Open the article for the full brief.' }}</p>
                                                </div>
                                            </a>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </section>
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
    </section>

    <section id="fixtures" data-tab-panel="fixtures" class="hidden">
        <div id="fixtures-panel-content">
            @include('news.partials.fixtures')
        </div>
        @if($adsense['client'] && $adsense['infeed_slot'])
            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                <ins class="adsbygoogle block"
                     style="display:block"
                     data-ad-client="{{ $adsense['client'] }}"
                     data-ad-slot="{{ $adsense['infeed_slot'] }}"
                     data-ad-format="auto"
                     data-full-width-responsive="true"></ins>
            </div>
        @endif
    </section>

    <section id="live-score" data-tab-panel="scores" class="hidden">
        <div id="scores-panel-content">
            @include('news.partials.scores')
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
        const tabPanels = document.querySelectorAll('[data-tab-panel]');
        const loadMoreBtn = document.getElementById('load-more-btn');
        const loadMoreContainer = document.getElementById('load-more-container');
        const newsGrid = document.getElementById('news-grid');
        const btnText = document.getElementById('btn-text');
        const btnLoading = document.getElementById('btn-loading');
        const fixturesPanelContent = document.getElementById('fixtures-panel-content');
        const scoresPanelContent = document.getElementById('scores-panel-content');

        function localizeKickoffs(scope = document) {
            scope.querySelectorAll('.js-local-kickoff').forEach(node => {
                const iso = node.dataset.kickoff;

                if (!iso) {
                    return;
                }

                const date = new Date(iso);
                node.textContent = new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }).format(date);
            });
        }

        function activateTab(tabName) {
            tabTriggers.forEach(trigger => {
                const active = trigger.dataset.tabTrigger === tabName;
                trigger.classList.toggle('bg-emerald-500', active);
                trigger.classList.toggle('text-slate-950', active);
                trigger.classList.toggle('border', !active);
                trigger.classList.toggle('border-slate-200', !active);
                trigger.classList.toggle('text-slate-600', !active);
                trigger.classList.toggle('font-extrabold', active);
                trigger.classList.toggle('font-bold', !active);
            });

            tabPanels.forEach(panel => {
                panel.classList.toggle('hidden', panel.dataset.tabPanel !== tabName);
            });
        }

        tabTriggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const tabName = trigger.dataset.tabTrigger;
                activateTab(tabName);

                const sectionId = tabName === 'news'
                    ? 'home'
                    : (tabName === 'fixtures' ? 'fixtures' : 'live-score');

                if (window.location.hash !== `#${sectionId}`) {
                    history.replaceState(null, '', `#${sectionId}`);
                }
            });
        });

        const initialHash = window.location.hash.replace('#', '');
        const initialTab = initialHash === 'fixtures'
            ? 'fixtures'
            : (initialHash === 'live-score' ? 'scores' : 'news');

        activateTab(initialTab);

        localizeKickoffs();

        const region = (navigator.language || '').split('-')[1] || '';
        const countryNode = document.getElementById('viewer-country');
        const timeNode = document.getElementById('viewer-time');
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';

        if (countryNode) {
            try {
                const displayNames = new Intl.DisplayNames([navigator.language || 'en'], { type: 'region' });
                countryNode.textContent = region ? `Country ${displayNames.of(region) || region}` : 'Country unavailable';
            } catch (error) {
                countryNode.textContent = region ? `Country ${region}` : 'Country unavailable';
            }
        }

        if (timeNode) {
            timeNode.textContent = `Local ${new Intl.DateTimeFormat(undefined, {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date())}`;
        }

        const sendVisitorHeartbeat = () => {
            fetch(`{{ route('analytics.visitor-context') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    timezone: timezone,
                    country_code: region,
                    page_path: window.location.pathname,
                }),
            }).catch(() => {});
        };

        sendVisitorHeartbeat();
        window.setInterval(sendVisitorHeartbeat, 60000);

        document.addEventListener('click', async (event) => {
            const refreshButton = event.target.closest('[data-scoreboard-refresh]');

            if (!refreshButton) {
                return;
            }

            event.preventDefault();

            const originalText = refreshButton.textContent.trim();
            refreshButton.disabled = true;
            refreshButton.textContent = 'Refreshing...';

            try {
                const response = await fetch(`{{ route('news.scoreboard.refresh') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Refresh failed');
                }

                const data = await response.json();

                if (fixturesPanelContent) {
                    fixturesPanelContent.innerHTML = data.fixtures_html;
                    localizeKickoffs(fixturesPanelContent);
                }

                if (scoresPanelContent) {
                    scoresPanelContent.innerHTML = data.scores_html;
                }
            } catch (error) {
                alert('Could not refresh fixtures and live scores. Check whether Chrome/Chromium is installed on the VPS.');
            } finally {
                refreshButton.disabled = false;
                refreshButton.textContent = originalText;
            }
        });

        if (window.adsbygoogle) {
            document.querySelectorAll('.adsbygoogle').forEach(() => {
                try {
                    (adsbygoogle = window.adsbygoogle || []).push({});
                } catch (error) {}
            });
        }

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
