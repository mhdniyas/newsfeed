@extends('layouts.app')

@section('title', $remoteOnly ? 'Remote Jobs Today – Work from Home Opportunities' : ($selectedCategory ? $selectedCategory . ' Jobs Today' : 'Daily Job Listings & Career Opportunities'))
@section('meta_description', 'Search and browse daily job opportunities across different categories. Filter by remote or work-from-home options and apply directly.')

@section('styles')
<style>
/* ── Jobs Board Hero ── */
.job-hero { 
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 55%, #4f46e5 100%); 
}
.job-card {
    display: flex;
    flex-direction: column;
    justify-content: justify-between;
    border-radius: 1.5rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1.5rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    height: 100%;
}
.job-card:hover {
    border-color: #818cf8;
    box-shadow: 0 12px 30px rgba(99, 102, 241, 0.08);
    transform: translateY(-3px);
}
.company-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 1rem;
    font-weight: 800;
    font-size: 1.1rem;
    color: #fff;
}
.job-search-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 9999px;
    padding: 0.6rem 1.25rem;
    backdrop-filter: blur(8px);
    transition: all 0.2s;
}
.job-search-box:focus-within {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.35);
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.05);
}
.job-search-box input {
    background: transparent;
    border: none;
    outline: none;
    color: #fff;
    font-size: 0.9rem;
    flex: 1;
}
.job-search-box input::placeholder {
    color: rgba(255, 255, 255, 0.45);
}
.job-search-box button {
    background: #4f46e5;
    border: none;
    border-radius: 9999px;
    padding: 0.45rem 1.1rem;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
}
.job-search-box button:hover {
    background: #4338ca;
}
.clear-job-btn {
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: color 0.15s;
}
.clear-job-btn:hover {
    color: #fff;
}
.category-chip {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
    background-color: #fff;
    color: #475569;
    white-space: nowrap;
}
.category-chip:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.category-chip.active {
    background-color: #4f46e5;
    border-color: #4f46e5;
    color: #fff;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
}
</style>
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ── Hero Section with Search & Remote Tagging ── --}}
    <div class="job-hero rounded-[2.5rem] overflow-hidden shadow-xl px-6 sm:px-12 py-10 text-white relative">
        <div class="max-w-2xl">
            <p class="text-[10px] font-bold uppercase tracking-[.25em] text-indigo-300">Google-Indexed Daily Feeds</p>
            <h1 class="mt-2.5 text-3xl sm:text-4xl font-extrabold leading-tight tracking-tight">
                {{ $remoteOnly && $isRemotePage ? 'Remote Work Opportunities' : 'Find Your Next Career Move' }}
            </h1>
            <p class="mt-3 text-sm text-indigo-200/70 leading-relaxed">
                Browse latest job listings crawled and categorized from top recruiters and job boards. Real-time updates daily.
            </p>

            {{-- Search Form --}}
            <form method="GET" action="{{ $isRemotePage ? route('jobs.remote') : route('jobs.index') }}" class="mt-8 space-y-4">
                @if($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                @if($remoteOnly && !$isRemotePage)
                    <input type="hidden" name="remote" value="1">
                @endif
                @if($selectedDate)
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                @endif
                <div class="job-search-box max-w-xl">
                    <svg class="h-4.5 w-4.5 shrink-0 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="q" id="job-search"
                           value="{{ $search }}"
                           placeholder="Search by job title, company, location or skill..."
                           autocomplete="off">
                    <button type="submit">Search</button>
                    @if($search)
                        <a href="{{ $isRemotePage ? route('jobs.remote', array_filter(['category' => $selectedCategory, 'date' => $selectedDate])) : route('jobs.index', array_filter(['category' => $selectedCategory, 'remote' => $remoteOnly ? '1' : null, 'date' => $selectedDate])) }}" class="clear-job-btn inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear
                        </a>
                    @endif
                </div>

                {{-- Date Filter Buttons --}}
                <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                    <span class="text-indigo-200 uppercase tracking-wider mr-1">Date Posted:</span>
                    <a href="{{ $isRemotePage ? route('jobs.remote', array_filter(['category' => $selectedCategory, 'q' => $search])) : route('jobs.index', array_filter(['category' => $selectedCategory, 'q' => $search, 'remote' => $remoteOnly ? '1' : null])) }}" 
                       class="px-3.5 py-1.5 rounded-full border transition {{ !$selectedDate ? 'bg-white text-indigo-950 border-white' : 'bg-white/10 text-white border-white/20 hover:bg-white/15' }}">
                        Any Time
                    </a>
                    <a href="{{ $isRemotePage ? route('jobs.remote', array_filter(['category' => $selectedCategory, 'q' => $search, 'date' => '24h'])) : route('jobs.index', array_filter(['category' => $selectedCategory, 'q' => $search, 'remote' => $remoteOnly ? '1' : null, 'date' => '24h'])) }}" 
                       class="px-3.5 py-1.5 rounded-full border transition {{ $selectedDate === '24h' ? 'bg-white text-indigo-950 border-white' : 'bg-white/10 text-white border-white/20 hover:bg-white/15' }}">
                        24 Hours
                    </a>
                    <a href="{{ $isRemotePage ? route('jobs.remote', array_filter(['category' => $selectedCategory, 'q' => $search, 'date' => '3d'])) : route('jobs.index', array_filter(['category' => $selectedCategory, 'q' => $search, 'remote' => $remoteOnly ? '1' : null, 'date' => '3d'])) }}" 
                       class="px-3.5 py-1.5 rounded-full border transition {{ $selectedDate === '3d' ? 'bg-white text-indigo-950 border-white' : 'bg-white/10 text-white border-white/20 hover:bg-white/15' }}">
                        3 Days
                    </a>
                    <a href="{{ $isRemotePage ? route('jobs.remote', array_filter(['category' => $selectedCategory, 'q' => $search, 'date' => '7d'])) : route('jobs.index', array_filter(['category' => $selectedCategory, 'q' => $search, 'remote' => $remoteOnly ? '1' : null, 'date' => '7d'])) }}" 
                       class="px-3.5 py-1.5 rounded-full border transition {{ $selectedDate === '7d' ? 'bg-white text-indigo-950 border-white' : 'bg-white/10 text-white border-white/20 hover:bg-white/15' }}">
                        7 Days
                    </a>
                </div>
            </form>
        </div>

        {{-- Section Mode Switcher --}}
        <div class="mt-8 flex flex-wrap gap-2.5">
            <a href="{{ route('jobs.index', array_filter(['category' => $selectedCategory, 'q' => $search, 'date' => $selectedDate])) }}" 
               class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-bold transition {{ !$remoteOnly && !$isRemotePage ? 'bg-white text-indigo-950 border-white shadow-sm' : 'bg-white/10 text-white border-white/20 hover:bg-white/15' }}">
                All Jobs
            </a>
            <a href="{{ route('jobs.remote', array_filter(['category' => $selectedCategory, 'q' => $search, 'date' => $selectedDate])) }}" 
               class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-bold transition {{ $remoteOnly || $isRemotePage ? 'bg-white text-indigo-950 border-white shadow-sm' : 'bg-white/10 text-white border-white/20 hover:bg-white/15' }}">
                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                Remote Jobs
            </a>
        </div>
    </div>

    {{-- ── Category Filter Bar ── --}}
    <div class="space-y-3">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Job Categories</h2>
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <a href="{{ $isRemotePage ? route('jobs.remote', array_filter(['q' => $search, 'date' => $selectedDate])) : route('jobs.index', array_filter(['q' => $search, 'remote' => $remoteOnly ? '1' : null, 'date' => $selectedDate])) }}" 
               class="category-chip {{ !$selectedCategory ? 'active' : '' }}">
                All Categories
            </a>
            @foreach($categories as $cat)
                <a href="{{ $isRemotePage ? route('jobs.remote', array_filter(['category' => $cat, 'q' => $search, 'date' => $selectedDate])) : route('jobs.index', array_filter(['category' => $cat, 'q' => $search, 'remote' => $remoteOnly ? '1' : null, 'date' => $selectedDate])) }}" 
                   class="category-chip {{ $selectedCategory === $cat ? 'active' : '' }}">
                    {{ $cat }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── Jobs Grid ── --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-extrabold text-slate-900">
                @if($selectedCategory)
                    Showing {{ $selectedCategory }} Jobs
                @else
                    All Available Openings
                @endif
                @if($remoteOnly || $isRemotePage)
                    <span class="text-indigo-600">(Remote Only)</span>
                @endif
            </h3>
            <span class="text-xs font-semibold text-slate-500">
                {{ $jobs->total() }} matches found
            </span>
        </div>

        @if($jobs->isEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-4">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h4 class="text-lg font-bold text-slate-900">No job listings found</h4>
                <p class="mt-2 text-sm text-slate-500 max-w-sm mx-auto">
                    We couldn't find any job posts matching your criteria. Try adjusting your search query, selecting another category, or toggling remote jobs.
                </p>
                <div class="mt-6">
                    <a href="{{ $isRemotePage ? route('jobs.remote') : route('jobs.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-indigo-700 transition">
                        Reset Filters
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($jobs as $job)
                    @php
                        // Deterministic company avatar colors
                        $colors = [
                            ['from-indigo-500 to-purple-600', 'from-emerald-400 to-teal-600', 'from-amber-400 to-orange-600', 'from-pink-500 to-rose-600', 'from-sky-400 to-blue-600'],
                            ['from-violet-500 to-fuchsia-600', 'from-cyan-400 to-sky-600', 'from-lime-400 to-emerald-600', 'from-yellow-400 to-amber-600', 'from-rose-500 to-pink-600']
                        ];
                        $charIndex = ord(substr($job->company, 0, 1)) ?: 0;
                        $grad = $colors[$charIndex % 2][$charIndex % 5];
                        $initials = strtoupper(substr($job->company ?: 'C', 0, 2));
                    @endphp
                    <a href="{{ route('jobs.show', $job->slug) }}" class="job-card group">
                        <div class="flex items-start justify-between gap-4">
                            <div class="company-avatar bg-gradient-to-br {{ $grad }} shadow-sm">
                                {{ $initials }}
                            </div>
                            <div class="flex flex-col gap-1.5 items-end">
                                <span class="rounded-full bg-slate-100 border border-slate-200/60 px-2 py-0.5 text-[9px] font-bold text-slate-600 uppercase tracking-wider">
                                    {{ $job->category }}
                                </span>
                                @if($job->is_remote)
                                    <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[9px] font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Remote
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 flex-1">
                            <h4 class="text-base font-extrabold text-slate-900 group-hover:text-indigo-600 transition duration-150 line-clamp-2">
                                {{ $job->title }}
                            </h4>
                            <p class="text-sm font-semibold text-slate-700 mt-1">
                                {{ $job->company ?: 'Confidential' }}
                            </p>
                            <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $job->location ?: 'India' }}
                            </p>
                            <p class="text-xs text-slate-500 mt-3.5 line-clamp-3 leading-relaxed">
                                {{ Str::limit($job->description, 130) }}
                            </p>
                        </div>

                        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-semibold">
                            <span class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-slate-350" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.25 2.25 0 00-2.25-2.25H15"/></svg>
                                {{ $job->source_name }}
                            </span>
                            <span>
                                {{ $job->published_at->diffForHumans() }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
