@extends('layouts.app')

@section('title', 'Content Module Analytics - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Dashboard</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Content Module Analytics</h1>
            <p class="mt-2 text-sm text-slate-500">Breakdown of visitors, clicks, CTRs, and downloads for specialized site features.</p>
        </div>
        <div class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white p-1 shadow-sm text-xs">
            <a href="?period=today" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === 'today' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">Today</a>
            <a href="?period=yesterday" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === 'yesterday' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">Yesterday</a>
            <a href="?period=7d" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === '7d' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">7 Days</a>
            <a href="?period=30d" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === '30d' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">30 Days</a>
            <a href="?period=90d" class="px-3 py-1.5 rounded-lg font-bold transition {{ $period === '90d' ? 'bg-slate-900 text-white' : 'text-slate-650 hover:bg-slate-50' }}">90 Days</a>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200 mb-8">
        <nav class="-mb-px flex flex-wrap gap-x-8">
            <a href="{{ route('admin.analytics.index') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Executive Dashboard
            </a>
            <a href="{{ route('admin.analytics.realtime') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                </span>
                Real-Time Monitor
            </a>
            <a href="{{ route('admin.analytics.modules') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-indigo-500 text-indigo-600">
                Content Modules
            </a>
            <a href="{{ route('admin.analytics.bots') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Crawlers & Bots
            </a>
            <a href="{{ route('admin.analytics.performance') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                System Performance
            </a>
        </nav>
    </div>

    {{-- Modules overview grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        {{-- Kerala Lottery --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <span class="px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">Module</span>
                <h2 class="text-lg font-black text-slate-900 mt-2">Kerala Lottery</h2>
                <p class="text-xs text-slate-500 mt-1">Metrics on PDF result downloads and official link click redirections.</p>
                
                <div class="grid grid-cols-3 gap-2 mt-6">
                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Views</span>
                        <p class="font-extrabold text-sm text-slate-900 mt-1">{{ number_format($lotteryTotals['views']) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">PDFs</span>
                        <p class="font-extrabold text-sm text-slate-900 mt-1">{{ number_format($lotteryTotals['pdf_downloads']) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Clicks</span>
                        <p class="font-extrabold text-sm text-slate-900 mt-1">{{ number_format($lotteryTotals['official_clicks']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 border-t border-slate-100 pt-4">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide mb-3">Popular Result Draws</h4>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($lotteryStats as $row)
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-semibold text-slate-700 truncate max-w-[160px]">{{ $row->lottery_name ?: 'General Index' }} {{ $row->draw_number }}</span>
                            <span class="font-bold text-slate-500">{{ number_format($row->views) }} views</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 text-center py-4">No drawing page views recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Gold Rates --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <span class="px-2 py-0.5 rounded-md bg-amber-50 border border-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider">Module</span>
                <h2 class="text-lg font-black text-slate-900 mt-2">Gold Rates</h2>
                <p class="text-xs text-slate-500 mt-1">Visitor metrics on daily pricing pages and interactive calculator usage.</p>
                
                <div class="grid grid-cols-2 gap-3 mt-6">
                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Total Views</span>
                        <p class="font-extrabold text-sm text-slate-900 mt-1">{{ number_format($goldTotals['views']) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Calculator Usage</span>
                        <p class="font-extrabold text-sm text-slate-900 mt-1">{{ number_format($goldTotals['calculator']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 border-t border-slate-100 pt-4">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide mb-3">Popular Cities</h4>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($goldStats as $row)
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-semibold text-slate-700 capitalize">{{ $row->city }}</span>
                            <span class="font-bold text-slate-500">{{ number_format($row->views) }} views</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 text-center py-4">No city page views recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Job Board --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <span class="px-2 py-0.5 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] font-bold uppercase tracking-wider">Module</span>
                <h2 class="text-lg font-black text-slate-900 mt-2">Job Board</h2>
                <p class="text-xs text-slate-500 mt-1">Listing detail page clicks and apply redirections CTR analytics.</p>
                
                <div class="grid grid-cols-2 gap-3 mt-6">
                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Job Views</span>
                        <p class="font-extrabold text-sm text-slate-900 mt-1">{{ number_format($jobTotals['views']) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Apply CTR</span>
                        <p class="font-extrabold text-sm text-slate-900 mt-1">
                            {{ $jobTotals['views'] > 0 ? round(($jobTotals['clicks'] / $jobTotals['views']) * 100, 1) : 0.0 }}%
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 border-t border-slate-100 pt-4">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide mb-3">Top Job Openings</h4>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($jobStats as $row)
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-semibold text-slate-700 truncate max-w-[160px]">{{ $row->title ?: 'General Index' }}</span>
                            <span class="font-bold text-slate-500">{{ number_format($row->views) }} views</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 text-center py-4">No job page views recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
