<article class="section-card-item rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-3 shadow-sm transition-all duration-200 hover:border-emerald-200 hover:bg-white">
    <a href="{{ route('news.visit', $article) }}" target="_blank" rel="noopener noreferrer" class="flex items-start gap-3">
        <img src="{{ $article->image_url ?: route('media.news-image', $article) }}"
             data-proxy-src="{{ route('media.news-image', $article) }}"
             data-placeholder-src="{{ '/media/fifa-placeholder/' . rawurlencode($article->hash ?: (string) $article->id) . '.svg' }}"
             alt="{{ $article->title }}"
             class="h-20 w-20 rounded-2xl object-cover shrink-0"
             loading="lazy"
             referrerpolicy="no-referrer"
             onerror="if(this.dataset.fallbackStage==='proxy'){this.src=this.dataset.placeholderSrc;this.dataset.fallbackStage='placeholder';return;} this.dataset.fallbackStage='proxy'; this.src=this.dataset.proxySrc;">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                <span>{{ $article->source_name }}</span>
                <span>·</span>
                <span>{{ $article->published_at->diffForHumans() }}</span>
                @if($article->newsSection)
                    <span class="ml-auto px-2 py-0.5 rounded-full bg-slate-950/80 text-white text-[10px] font-bold uppercase tracking-[0.14em]">{{ $article->newsSection->name }}</span>
                @endif
            </div>
            <h3 class="mt-2 text-sm font-extrabold leading-5 text-slate-900 line-clamp-2">{{ $article->title }}</h3>
            <p class="mt-1.5 text-xs leading-5 text-slate-500 line-clamp-2">{{ $article->description ?? 'Open the article for the full brief.' }}</p>
            @if($article->newsTopic)
                <span class="mt-2 inline-block text-[10px] font-semibold text-slate-400">#{{ $article->newsTopic->name }}</span>
            @endif
        </div>
    </a>
</article>
