@extends('layouts.app')

@section('title', 'Executive Analytics - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Dashboard</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Platform Analytics</h1>
            <p class="mt-2 text-sm text-slate-500">Negligible response overhead (&lt; 2ms). Zero database locking.</p>
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
            <a href="{{ route('admin.analytics.index') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-indigo-500 text-indigo-600">
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
            <a href="{{ route('admin.analytics.performance') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                System Performance
            </a>
        </nav>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Page Views</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($summary['total_views']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Total loads across web routes.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Sessions</p>
            <p class="mt-2 text-3xl font-black text-indigo-600">{{ number_format($summary['total_sessions']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Unique visitor browsing sessions.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Bounce Rate</p>
            <p class="mt-2 text-3xl font-black text-amber-600">{{ number_format($summary['bounce_rate'], 1) }}%</p>
            <p class="text-xs text-slate-500 mt-1">Single page visit percentage.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Avg Session Duration</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ gmdate('H:i:s', $summary['avg_duration']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Calculated time on site.</p>
        </div>
    </div>

    {{-- Traffic chart --}}
    <div class="grid grid-cols-1 gap-6 mb-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Traffic Progression</h2>
            <div class="h-80 w-full relative">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Bottom Breakdowns --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {{-- Referrer Host --}}
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 uppercase tracking-wider text-slate-400">Traffic Sources</h3>
            <div class="space-y-4">
                @forelse($referrers as $ref)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800 truncate max-w-[200px]">
                            {{ $ref->referrer_host ?: 'Direct / Bookmarks' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-50 border border-slate-100 text-slate-600 font-bold">
                            {{ number_format($ref->views) }} views
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">No referrer data found.</p>
                @endforelse
            </div>
        </div>

        {{-- Devices --}}
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 uppercase tracking-wider text-slate-400">Device Types</h3>
            <div class="space-y-4">
                @forelse($devices as $dev)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800 capitalize">{{ $dev->device_type ?: 'Unknown' }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold">
                            {{ number_format($dev->views) }} views
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">No device data found.</p>
                @endforelse
            </div>
        </div>

        {{-- Browsers --}}
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 uppercase tracking-wider text-slate-400">Browsers</h3>
            <div class="space-y-4">
                @forelse($browsers as $brow)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">{{ $brow->browser_name ?: 'Other' }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 font-bold">
                            {{ number_format($brow->views) }} views
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">No browser data found.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>

{{-- Chart.js Script --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('trafficChart').getContext('2d');
        
        const dates = {!! json_encode($dailyAggregates->pluck('date')) !!};
        const pageViews = {!! json_encode($dailyAggregates->pluck('total_views')) !!};
        const uniqueVisitors = {!! json_encode($dailyAggregates->pluck('unique_visitors')) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                }),
                datasets: [
                    {
                        label: 'Page Views',
                        data: pageViews,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.03)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#4f46e5'
                    },
                    {
                        label: 'Unique Visitors',
                        data: uniqueVisitors,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.02)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#10b981'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            color: '#475569'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
