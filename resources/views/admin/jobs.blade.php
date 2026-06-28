@extends('layouts.app')

@section('title', 'Admin Jobs Management - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Dashboard</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Jobs Control Panel</h1>
            <p class="mt-2 text-sm text-slate-500">Trigger manual Google News RSS index cycles, monitor visitor views, and manage active job listings.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('admin.jobs.sync') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-950 text-white hover:bg-slate-800 text-xs font-bold transition shadow-md">
                    Trigger Manual Sync
                </button>
            </form>
        </div>
    </div>

    {{-- Success/Error Banners --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Listings</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($jobsStats['total']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Aggregated job posts in database.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Remote Opportunities</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ number_format($jobsStats['remote']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Tagged as work-from-home or remote.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Detail Page Views</p>
            <p class="mt-2 text-3xl font-black text-indigo-600">{{ number_format($jobsStats['views']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Total visits to local detail pages.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Apply Clicks</p>
            <p class="mt-2 text-3xl font-black text-amber-600">{{ number_format($jobsStats['clicks']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Total application link redirections.</p>
        </div>
    </div>

    {{-- Filter & Search Panel --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.jobs.index') }}" class="w-full sm:max-w-md">
            <div class="relative flex items-center bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm focus-within:border-indigo-500 transition">
                <svg class="h-4 w-4 shrink-0 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ $search }}" placeholder="Search by title, company, location or category..." class="w-full bg-transparent border-none outline-none text-xs text-slate-800">
                @if($search)
                    <a href="{{ route('admin.jobs.index') }}" class="text-slate-450 hover:text-slate-700 ml-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table list --}}
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Job Details</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Remote</th>
                        <th class="px-6 py-4 text-center">Views</th>
                        <th class="px-6 py-4 text-center">Clicks</th>
                        <th class="px-6 py-4">Published Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($jobs as $job)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4">
                                <a href="{{ route('jobs.show', $job->slug) }}" target="_blank" class="block font-bold text-slate-900 hover:text-indigo-600 transition">
                                    {{ $job->title }}
                                </a>
                                <span class="text-xs text-slate-400 block mt-0.5">{{ $job->company }} · {{ $job->location }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] font-bold">
                                    {{ $job->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($job->is_remote)
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-bold inline-flex items-center gap-1">
                                        <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                                        Remote
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-600">{{ number_format($job->views_count) }}</td>
                            <td class="px-6 py-4 text-center font-bold text-slate-600">{{ number_format($job->apply_clicks_count) }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500 font-medium">{{ $job->published_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.jobs.delete', $job->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this job post?');" class="inline-block">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-slate-400 mb-2">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-slate-500">No job listings found.</p>
                                <p class="text-xs text-slate-450 mt-1">Try manual sync or search with different keywords.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination footer --}}
        @if($jobs->isNotEmpty())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
