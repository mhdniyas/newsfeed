@extends('layouts.app')

@section('title', 'Technical Performance - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Dashboard</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">System Performance</h1>
            <p class="mt-2 text-sm text-slate-500">Monitor request response speeds, query latencies, 404/500 HTTP failures, and host disk usages.</p>
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
            <a href="{{ route('admin.analytics.modules') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Content Modules
            </a>
            <a href="{{ route('admin.analytics.bots') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Crawlers & Bots
            </a>
            <a href="{{ route('admin.analytics.performance') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-indigo-500 text-indigo-600">
                System Performance
            </a>
        </nav>
    </div>

    {{-- Tech stats summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Avg Page Execution</p>
            <p class="mt-2 text-3xl font-black text-indigo-600">{{ $diagnostics['avg_response_time'] }}ms</p>
            <p class="text-xs text-slate-500 mt-1">Server response processing latency.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Disk Used</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ $diagnostics['disk_usage'] }}%</p>
            <p class="text-xs text-slate-500 mt-1">Host disk space allocation.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">PHP Version</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ $diagnostics['php_version'] }}</p>
            <p class="text-xs text-slate-500 mt-1">Active PHP binary release.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Laravel Version</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ $diagnostics['laravel_version'] }}</p>
            <p class="text-xs text-slate-500 mt-1">Skeleton framework version.</p>
        </div>
    </div>

    {{-- Performance details split --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        {{-- Slowest Pages --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Slowest Route Responses</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead>
                        <tr class="text-left font-bold text-slate-450 uppercase tracking-wider">
                            <th class="pb-2">Path</th>
                            <th class="pb-2 text-center">Avg Response</th>
                            <th class="pb-2 text-right">Hits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                        @forelse($slowPages as $row)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 text-slate-900 truncate max-w-xs font-bold">{{ $row->page_path }}</td>
                                <td class="py-2.5 text-center font-black text-rose-600">{{ round($row->avg_time) }}ms</td>
                                <td class="py-2.5 text-right font-medium text-slate-500">{{ number_format($row->hits) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-400">No page execution stats logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Route Exception Logs --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Application Errors (4xx/5xx)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead>
                        <tr class="text-left font-bold text-slate-455 uppercase tracking-wider">
                            <th class="pb-2">Path</th>
                            <th class="pb-2 text-center">HTTP Status</th>
                            <th class="pb-2 text-right">Occurrences</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                        @forelse($errors as $err)
                            @php
                                $badgeColor = $err->error_type === '404' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-rose-50 text-rose-700 border-rose-100';
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 text-slate-900 truncate max-w-xs font-medium">{{ $err->page_path }}</td>
                                <td class="py-2.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full border text-[10px] font-black {{ $badgeColor }}">
                                        {{ $err->error_type }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-right font-black text-slate-700">{{ number_format($err->count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-400">No routing failures or errors logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
