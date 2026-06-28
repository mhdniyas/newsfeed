@extends('layouts.app')

@section('title', 'Destroy Manager - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-rose-500">Admin Destroy</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Destroy manager</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">Low-performance article cleanup with manual delete filters, zero-view and zero-click modes, and protected favorites. Automatic destroy is disabled.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-colors shadow-sm">
                Dashboard
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
            <p class="mt-2 text-xs text-rose-50/90">Articles matching the current manual destroy rule.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Zero Click</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($destroyStats['zero_click_articles']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Stories with zero outbound clicks in the current queue.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Zero View</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($destroyStats['zero_view_articles']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Cards never seen on the public side in the queue.</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Favorites Protected</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ number_format($destroyStats['favorite_articles']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Favorite posts are skipped from manual deletion.</p>
        </div>
    </div>

    <div class="mb-8 grid gap-6 xl:grid-cols-[1.25fr_0.95fr]">
        <div class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white shadow-sm overflow-hidden">
            <div class="border-b border-amber-200 px-5 py-4">
                <h2 class="text-base font-bold text-slate-900">Manual delete defaults</h2>
                <p class="mt-1 text-xs text-slate-500">Automatic deletion has been removed. Save the manual age window, mode, click threshold, sort order, and delete cycle size from 500 to 3000.</p>
            </div>
            <form action="{{ route('admin.destroy.settings') }}" method="POST" class="px-5 py-4">
                @csrf
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <label class="rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Auto Run</span>
                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold text-slate-900">
                            Disabled permanently
                        </div>
                        <input type="hidden" name="enabled" value="0">
                    </label>
                    <label class="rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Age Window</span>
                        <input type="number" name="days" min="1" max="30" value="{{ $autoDeleteReport['days'] }}" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold text-slate-900 outline-none transition focus:border-rose-400 focus:bg-white">
                        <p class="mt-2 text-xs text-slate-500">Example: delete items with no clicks over 2 days.</p>
                    </label>
                    <label class="rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Click Threshold</span>
                        <input type="number" name="click_threshold" min="0" max="100000" value="{{ $autoDeleteReport['click_threshold'] }}" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold text-slate-900 outline-none transition focus:border-rose-400 focus:bg-white">
                        <p class="mt-2 text-xs text-slate-500">Used in standard mode only.</p>
                    </label>
                    <label class="rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Mode</span>
                        <select name="mode" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold text-slate-900 outline-none transition focus:border-rose-400 focus:bg-white">
                            <option value="standard" @selected($autoDeleteReport['mode'] === 'standard')>Low Clicks</option>
                            <option value="no_clicks" @selected($autoDeleteReport['mode'] === 'no_clicks')>No Clicks</option>
                            <option value="no_views" @selected($autoDeleteReport['mode'] === 'no_views')>No Views</option>
                            <option value="viewed_no_clicks" @selected($autoDeleteReport['mode'] === 'viewed_no_clicks')>Viewed, No Clicks</option>
                        </select>
                    </label>
                    <label class="rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Sort</span>
                        <select name="sort" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold text-slate-900 outline-none transition focus:border-rose-400 focus:bg-white">
                            <option value="oldest" @selected($autoDeleteReport['sort'] === 'oldest')>Created Oldest First</option>
                            <option value="latest" @selected($autoDeleteReport['sort'] === 'latest')>Created Latest First</option>
                            <option value="least_clicked" @selected($autoDeleteReport['sort'] === 'least_clicked')>Least Clicked</option>
                            <option value="least_viewed" @selected($autoDeleteReport['sort'] === 'least_viewed')>Least Viewed</option>
                            <option value="most_clicked" @selected($autoDeleteReport['sort'] === 'most_clicked')>Most Clicked</option>
                            <option value="most_viewed" @selected($autoDeleteReport['sort'] === 'most_viewed')>Most Viewed</option>
                            <option value="title" @selected($autoDeleteReport['sort'] === 'title')>Title</option>
                        </select>
                    </label>
                    <label class="rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Delete Cycle</span>
                        <select name="batch_limit" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold text-slate-900 outline-none transition focus:border-rose-400 focus:bg-white">
                            @foreach([500, 1000, 1500, 2000, 2500, 3000] as $limit)
                                <option value="{{ $limit }}" @selected($autoDeleteReport['batch_limit'] === $limit)>{{ number_format($limit) }} posts / run</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800">
                        Save Manual Defaults
                    </button>
                    <span class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-700">
                        Automatic destroy is disabled
                    </span>
                </div>
            </form>
        </div>

        <div class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white shadow-sm overflow-hidden">
            <div class="border-b border-amber-200 px-5 py-4">
                <h2 class="text-base font-bold text-slate-900">Destroy defaults report</h2>
                <p class="mt-1 text-xs text-slate-500">Saved manual rule snapshot and last run details.</p>
            </div>
            <div class="grid gap-3 px-5 py-4 md:grid-cols-2">
            <div class="rounded-2xl border border-amber-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Eligible Now</p>
                <p class="mt-2 text-3xl font-extrabold text-rose-600">{{ number_format($autoDeleteReport['eligible_now']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Eligible for manual deletion.</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Protected Now</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ number_format($autoDeleteReport['protected_now']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Older than {{ $autoDeleteReport['days'] }} days and kept because of clicks or favorites.</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Favorite Protected</p>
                <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ number_format($autoDeleteReport['favorite_protected_now']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Older posts explicitly protected by the favorite flag.</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Saved Mode</p>
                <p class="mt-2 text-sm font-extrabold text-slate-900">{{ str_replace('_', ' ', $autoDeleteReport['mode']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Sort: {{ str_replace('_', ' ', $autoDeleteReport['sort']) }} · Limit: {{ number_format($autoDeleteReport['batch_limit']) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Last Run</p>
                <p class="mt-2 text-sm font-bold text-slate-900">
                    @if($autoDeleteReport['last_run_at'])
                        {{ \Illuminate\Support\Carbon::parse($autoDeleteReport['last_run_at'])->format('M d, Y H:i') }}
                    @else
                        Not run yet
                    @endif
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Deleted {{ number_format($autoDeleteReport['last_deleted_count']) }} from {{ number_format($autoDeleteReport['last_eligible_count']) }} eligible.
                    Protected: {{ number_format($autoDeleteReport['last_protected_count']) }}.
                    Favorites: {{ number_format($autoDeleteReport['last_favorite_protected_count']) }}.
                </p>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 md:col-span-2">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-rose-600">Destroy Process</p>
                <p class="mt-2 text-lg font-extrabold text-slate-950">Run manual cleanup now</p>
                <p class="mt-1 text-xs text-slate-500">Execute the saved automatic rule immediately using the live days, mode, threshold, sort, and delete-cycle settings.</p>
                <form action="{{ route('admin.destroy.run') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="section" value="{{ $selectedSectionId }}">
                    <input type="hidden" name="topic" value="{{ $selectedTopicId }}">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <input type="hidden" name="sort" value="{{ $autoDeleteReport['sort'] }}">
                    <input type="hidden" name="mode" value="{{ $autoDeleteReport['mode'] }}">
                    <input type="hidden" name="days" value="{{ $autoDeleteReport['days'] }}">
                    <input type="hidden" name="click_threshold" value="{{ $autoDeleteReport['click_threshold'] }}">
                    <input type="hidden" name="batch_limit" value="{{ $autoDeleteReport['batch_limit'] }}">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700" onclick="return confirm('Run the destroy process now?');">
                        Destroy Now
                    </button>
                </form>
            </div>
        </div>
    </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Destroy queue</h2>
                    <p class="mt-1 text-xs text-slate-500">This queue shows the current manual delete mode. Use favorite to protect a post from all cleanup actions, or run bulk deletion on the selected set.</p>
                </div>
                <div class="text-xs text-slate-500">
                    Next auto fetch in <span class="font-semibold text-slate-700 js-fetch-countdown" data-next-fetch="{{ $fetchStats['next_scheduled_at'] ?? '' }}">calculating...</span>
                </div>
            </div>
        </div>

        <div class="p-5 border-b border-slate-200">
            <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('admin.destroy', ['mode' => 'standard', 'days' => $destroyDays, 'click_threshold' => $destroyClickThreshold, 'batch_limit' => $destroyBatchLimit, 'sort' => 'oldest']) }}" class="rounded-2xl border px-4 py-4 transition {{ $destroyMode === 'standard' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $destroyMode === 'standard' ? 'text-white/70' : 'text-slate-400' }}">Manual Mode</p>
                    <p class="mt-2 text-base font-black">Low Clicks</p>
                    <p class="mt-1 text-xs {{ $destroyMode === 'standard' ? 'text-white/75' : 'text-slate-500' }}">Delete older posts below the click threshold.</p>
                </a>
                <a href="{{ route('admin.destroy', ['mode' => 'no_clicks', 'days' => $destroyDays, 'click_threshold' => $destroyClickThreshold, 'batch_limit' => $destroyBatchLimit, 'sort' => 'oldest']) }}" class="rounded-2xl border px-4 py-4 transition {{ $destroyMode === 'no_clicks' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $destroyMode === 'no_clicks' ? 'text-white/70' : 'text-slate-400' }}">Manual Mode</p>
                    <p class="mt-2 text-base font-black">No Clicks</p>
                    <p class="mt-1 text-xs {{ $destroyMode === 'no_clicks' ? 'text-white/75' : 'text-slate-500' }}">Destroy all older posts with zero clicks.</p>
                </a>
                <a href="{{ route('admin.destroy', ['mode' => 'no_views', 'days' => $destroyDays, 'click_threshold' => $destroyClickThreshold, 'batch_limit' => $destroyBatchLimit, 'sort' => 'oldest']) }}" class="rounded-2xl border px-4 py-4 transition {{ $destroyMode === 'no_views' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $destroyMode === 'no_views' ? 'text-white/70' : 'text-slate-400' }}">Manual Mode</p>
                    <p class="mt-2 text-base font-black">No Views</p>
                    <p class="mt-1 text-xs {{ $destroyMode === 'no_views' ? 'text-white/75' : 'text-slate-500' }}">Destroy all older posts with zero views.</p>
                </a>
                <a href="{{ route('admin.destroy', ['mode' => 'viewed_no_clicks', 'days' => $destroyDays, 'click_threshold' => $destroyClickThreshold, 'batch_limit' => $destroyBatchLimit, 'sort' => 'oldest']) }}" class="rounded-2xl border px-4 py-4 transition {{ $destroyMode === 'viewed_no_clicks' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $destroyMode === 'viewed_no_clicks' ? 'text-white/70' : 'text-slate-400' }}">Manual Mode</p>
                    <p class="mt-2 text-base font-black">Viewed, No Clicks</p>
                    <p class="mt-1 text-xs {{ $destroyMode === 'viewed_no_clicks' ? 'text-white/75' : 'text-slate-500' }}">Keep unseen posts aside and target viewed cards with no clicks.</p>
                </a>
            </div>

            <form action="{{ route('admin.destroy') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-8 gap-3">
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
                        <option value="oldest" @selected($sort === 'oldest')>Created Oldest First</option>
                        <option value="latest" @selected($sort === 'latest')>Created Latest First</option>
                        <option value="least_clicked" @selected($sort === 'least_clicked')>Least Clicked</option>
                        <option value="least_viewed" @selected($sort === 'least_viewed')>Least Viewed</option>
                        <option value="most_clicked" @selected($sort === 'most_clicked')>Most Clicked</option>
                        <option value="most_viewed" @selected($sort === 'most_viewed')>Most Viewed</option>
                        <option value="title" @selected($sort === 'title')>Title</option>
                    </select>
                </div>
                <div>
                    <select name="mode" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400">
                        <option value="standard" @selected($destroyMode === 'standard')>Low Clicks</option>
                        <option value="no_clicks" @selected($destroyMode === 'no_clicks')>No Clicks</option>
                        <option value="no_views" @selected($destroyMode === 'no_views')>No Views</option>
                        <option value="viewed_no_clicks" @selected($destroyMode === 'viewed_no_clicks')>Viewed, No Clicks</option>
                    </select>
                </div>
                <div>
                    <input type="number" name="days" min="1" max="30" value="{{ $destroyDays }}" placeholder="Days" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400">
                </div>
                <div>
                    <input type="number" name="click_threshold" min="0" max="100000" value="{{ $destroyClickThreshold }}" placeholder="Click threshold" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400">
                </div>
                <div>
                    <select name="batch_limit" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400">
                        @foreach([500, 1000, 1500, 2000, 2500, 3000] as $limit)
                            <option value="{{ $limit }}" @selected($destroyBatchLimit === $limit)>{{ number_format($limit) }} / run</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 md:col-span-2 xl:col-span-8">
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
            <input type="hidden" name="mode" value="{{ $destroyMode }}">
            <input type="hidden" name="days" value="{{ $destroyDays }}">
            <input type="hidden" name="click_threshold" value="{{ $destroyClickThreshold }}">
            <input type="hidden" name="batch_limit" value="{{ $destroyBatchLimit }}">

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
                                        @if($article->is_favorite)
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-amber-700">Favorite</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="min-w-0 xl:max-w-3xl">
                                            <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="block text-sm sm:text-base font-bold text-slate-900 transition hover:text-rose-700">{{ $article->title }}</a>
                                            <p class="mt-1 text-xs text-slate-500">{{ $article->source_name }} @if($article->newsTopic) · {{ $article->newsTopic->name }} @endif</p>
                                            <p class="mt-2 text-xs text-slate-400">Published {{ optional($article->published_at)->format('M d, Y H:i') ?? 'Unknown' }}</p>
                                            <p class="mt-2 text-xs text-slate-500">Manual rule: {{ str_replace('_', ' ', $destroyMode) }} · {{ $destroyDays }} days · threshold {{ number_format($destroyClickThreshold) }} · batch {{ number_format($destroyBatchLimit) }}</p>
                                        </div>
                                        <div class="flex shrink-0 gap-2">
                                            <button type="submit" form="favorite-article-{{ $article->id }}" class="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100">
                                                {{ $article->is_favorite ? 'Unfavorite' : 'Favorite' }}
                                            </button>
                                            <button type="submit" form="delete-article-{{ $article->id }}" onclick="return confirm('Delete this article permanently?');" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-slate-500">No articles currently match the retention cleanup rule for this filter.</div>
                    @endforelse
                </div>
            </div>
        </form>

        <div class="px-5 py-4 border-t border-slate-200 bg-slate-50/60">
            {{ $articles->withQueryString()->links() }}
        </div>
    </div>

    @foreach($articles as $article)
        <form id="favorite-article-{{ $article->id }}" action="{{ route('admin.articles.toggle-favorite', $article) }}" method="POST" class="hidden">
            @csrf
        </form>
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
