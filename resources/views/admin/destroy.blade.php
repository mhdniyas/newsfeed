@extends('layouts.app')

@section('title', 'Destroy Articles - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-rose-500">Admin Destroy</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Low-performance article cleanup</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">Review the least-clicked stories first, then bulk delete weak inventory without touching the rest of the news stream.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-colors shadow-sm">
                Dashboard
            </a>
            <a href="{{ route('admin.analytics') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 text-xs font-bold transition-colors shadow-sm">
                Analytics
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-8">
        <div class="rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-500 to-rose-600 p-5 text-white shadow-lg shadow-rose-500/10">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-rose-50/80">Eligible</p>
            <p class="mt-3 text-4xl font-black">{{ number_format($destroyStats['eligible_articles']) }}</p>
            <p class="mt-2 text-xs text-rose-50/90">Articles matching the current cleanup filter.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Zero Click</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($destroyStats['zero_click_articles']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Stories nobody has opened yet.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Zero View</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($destroyStats['zero_view_articles']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Cards never seen on the public side.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Clicks In Filter</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ number_format($destroyStats['least_clicked_total']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Combined outbound clicks of this result set.</p>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Destroy queue</h2>
                    <p class="mt-1 text-xs text-slate-500">Least-clicked articles appear first. Use filters, select rows, then bulk delete.</p>
                </div>
                <div class="text-xs text-slate-500">
                    Next auto fetch in <span class="font-semibold text-slate-700 js-fetch-countdown" data-next-fetch="{{ $fetchStats['next_scheduled_at'] ?? '' }}">calculating...</span>
                </div>
            </div>
        </div>

        <div class="p-5 border-b border-slate-200">
            <form action="{{ route('admin.destroy') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                <div class="xl:col-span-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search title or source..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-rose-400 focus:bg-white">
                </div>
                <div>
                    <select name="section" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400">
                        <option value="all">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) $selectedSectionId === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="topic" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400">
                        <option value="all">All Topics</option>
                        @foreach($sections as $section)
                            @foreach($section->newsTopics as $topic)
                                <option value="{{ $topic->id }}" @selected((string) $selectedTopicId === (string) $topic->id)>{{ $section->name }} / {{ $topic->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="sort" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400">
                        <option value="least_clicked" @selected($sort === 'least_clicked')>Least Clicked</option>
                        <option value="least_viewed" @selected($sort === 'least_viewed')>Least Viewed</option>
                        <option value="oldest" @selected($sort === 'oldest')>Oldest First</option>
                        <option value="latest" @selected($sort === 'latest')>Latest First</option>
                    </select>
                </div>
                <div class="flex gap-2 md:col-span-2 xl:col-span-5">
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.destroy') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <form action="{{ route('admin.articles.bulk-delete') }}" method="POST" id="bulk-delete-form" class="p-5">
            @csrf
            <input type="hidden" name="section" value="{{ $selectedSectionId }}">
            <input type="hidden" name="topic" value="{{ $selectedTopicId }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="sort" value="{{ $sort }}">

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="select-all-destroy" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                        Select Page
                    </button>
                    <button type="button" id="clear-all-destroy" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                        Clear
                    </button>
                    <span id="selected-destroy-count" class="text-xs font-semibold text-slate-500">0 selected</span>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50" disabled id="bulk-delete-button" onclick="return confirm('Delete selected articles permanently?');">
                    Bulk Delete Selected
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-100 px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">
                    <input type="checkbox" id="toggle-all-desktop" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    <span>Select all articles on this page</span>
                </div>

                <div class="divide-y divide-slate-200">
                    @forelse($articles as $article)
                        <div class="p-4 sm:p-5">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" name="article_ids[]" value="{{ $article->id }}" class="destroy-checkbox mt-1 h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-rose-700">{{ $article->newsSection?->name ?? 'Unassigned' }}</span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-600">{{ number_format($article->clicks_count) }} clicks</span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-600">{{ number_format($article->views_count) }} views</span>
                                    </div>
                                    <div class="mt-3 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="min-w-0 xl:max-w-3xl">
                                            <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="block text-sm sm:text-base font-bold text-slate-900 transition hover:text-rose-700">{{ $article->title }}</a>
                                            <p class="mt-1 text-xs text-slate-500">{{ $article->source_name }} @if($article->newsTopic) · {{ $article->newsTopic->name }} @endif</p>
                                            <p class="mt-2 text-xs text-slate-400">Published {{ optional($article->published_at)->format('M d, Y H:i') ?? 'Unknown' }}</p>
                                        </div>
                                        <div class="flex shrink-0">
                                            <button type="submit" form="delete-article-{{ $article->id }}" onclick="return confirm('Delete this article permanently?');" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-slate-500">No articles match the current destroy filter.</div>
                    @endforelse
                </div>
            </div>
        </form>

        <div class="px-5 py-4 border-t border-slate-200 bg-slate-50/60">
            {{ $articles->withQueryString()->links() }}
        </div>
    </div>

    @foreach($articles as $article)
        <form id="delete-article-{{ $article->id }}" action="{{ route('admin.articles.delete', $article) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = Array.from(document.querySelectorAll('.destroy-checkbox'));
        const countNode = document.getElementById('selected-destroy-count');
        const bulkButton = document.getElementById('bulk-delete-button');
        const selectAllButton = document.getElementById('select-all-destroy');
        const clearAllButton = document.getElementById('clear-all-destroy');
        const toggleAllDesktop = document.getElementById('toggle-all-desktop');

        const updateSelectionState = () => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
            countNode.textContent = `${selected} selected`;
            bulkButton.disabled = selected === 0;

            if (toggleAllDesktop) {
                toggleAllDesktop.checked = selected > 0 && selected === checkboxes.length;
                toggleAllDesktop.indeterminate = selected > 0 && selected < checkboxes.length;
            }
        };

        selectAllButton?.addEventListener('click', () => {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = true;
            });
            updateSelectionState();
        });

        clearAllButton?.addEventListener('click', () => {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            updateSelectionState();
        });

        toggleAllDesktop?.addEventListener('change', (event) => {
            const isChecked = event.target.checked;
            checkboxes.forEach((checkbox) => {
                checkbox.checked = isChecked;
            });
            updateSelectionState();
        });

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateSelectionState);
        });

        updateSelectionState();
    });
</script>
@endsection
