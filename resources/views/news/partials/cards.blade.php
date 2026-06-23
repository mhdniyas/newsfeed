@foreach($articles as $article)
    <article class="relative flex flex-col justify-between overflow-hidden rounded-[1.6rem] bg-white border @if($article->is_featured) border-amber-400 shadow-lg shadow-amber-400/10 @else border-slate-200/70 shadow-sm shadow-slate-200/50 @endif hover:shadow-xl hover:shadow-slate-300/40 hover:border-emerald-300/60 transition-all duration-300 group">
        
        <!-- Article Thumbnail Image -->
        <div class="h-44 w-full overflow-hidden bg-slate-50 border-b border-slate-100 relative">
            <img src="{{ $article->image_url ?: route('media.news-image', $article) }}"
                 data-proxy-src="{{ route('media.news-image', $article) }}"
                 data-placeholder-src="{{ '/media/fifa-placeholder/' . rawurlencode($article->hash ?: (string) $article->id) . '.svg' }}"
                 alt="{{ $article->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy"
                 referrerpolicy="no-referrer"
                 onerror="if(this.dataset.fallbackStage==='proxy'){this.src=this.dataset.placeholderSrc;this.dataset.fallbackStage='placeholder';return;} this.dataset.fallbackStage='proxy'; this.src=this.dataset.proxySrc;">
            @if($article->is_featured)
                <span class="absolute top-3 left-3 px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-amber-400 text-slate-950 tracking-wider shadow-sm">
                    Featured
                </span>
            @endif
            @if($article->newsSection)
                <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-slate-950/80 text-white tracking-[0.16em] backdrop-blur">
                    {{ $article->newsSection->name }}
                </span>
            @endif
        </div>

        <div class="p-5 flex-grow">
            <!-- Metadata line -->
            <div class="flex items-center justify-between gap-2 mb-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/50">
                    {{ $article->source_name }}
                </span>
                <span class="text-xs text-slate-450 flex items-center space-x-1">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-slate-400 font-medium">{{ $article->published_at->diffForHumans() }}</span>
                </span>
            </div>

            <!-- Title -->
            <h3 class="text-[1.02rem] font-extrabold text-slate-950 leading-snug group-hover:text-emerald-600 transition-colors duration-200 line-clamp-3 mb-2">
                {{ $article->title }}
            </h3>

            <!-- Description -->
            <p class="text-sm text-slate-600 leading-relaxed line-clamp-3">
                {{ $article->description ?? 'No description available. Open article for full details.' }}
            </p>
        </div>

        <!-- Footer actions & Topic tag -->
        <div class="px-5 pb-5 pt-0 mt-auto">
            <div class="border-t border-slate-100 pt-4 flex items-center justify-between gap-2">
                <!-- Topic Name Badge -->
                <span class="text-[11px] font-semibold text-slate-400 hover:text-slate-500 truncate max-w-[150px]">
                    #{{ $article->newsTopic->name }}
                </span>

                <!-- Explore More Button -->
                <a href="{{ route('news.article', ['article' => $article->slug]) }}" class="inline-flex items-center space-x-1 text-xs font-bold text-emerald-600 hover:text-emerald-700 transition-colors duration-200 group/btn">
                    <span>Read Detail</span>
                    <svg class="w-3 h-3 transform group-hover/btn:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </article>
@endforeach
