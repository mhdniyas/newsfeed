@extends('layouts.app')

@section('title', 'Real-Time Analytics - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Dashboard</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                Real-Time Analytics
                <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-rose-500/10">
                    <span class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
                </span>
            </h1>
            <p class="mt-2 text-sm text-slate-500">Live active sessions and page views resolved directly from Redis cache.</p>
        </div>
        <div class="text-xs font-semibold text-slate-400">
            Auto-refreshing every <span class="text-slate-900 font-bold">5s</span> · Last synced: <span id="sync-clock" class="text-slate-900 font-bold">--:--:--</span>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200 mb-8">
        <nav class="-mb-px flex flex-wrap gap-x-8">
            <a href="{{ route('admin.analytics.index') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Executive Dashboard
            </a>
            <a href="{{ route('admin.analytics.realtime') }}" class="border-b-2 py-4 px-1 text-sm font-semibold border-indigo-500 text-indigo-600 flex items-center gap-1.5">
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

    {{-- Realtime Indicators --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-slate-950 rounded-3xl border border-slate-800 p-6 text-white relative overflow-hidden shadow-md">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active Visitors (Last 5m)</p>
            <p id="online-counter" class="mt-4 text-5xl font-black tracking-tight">0</p>
            <p class="text-xs text-slate-400 mt-2">Active fingerprints concurrently browsing.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Active Sessions (Last 30m)</p>
            <p id="active-sessions-counter" class="mt-4 text-5xl font-black text-indigo-600 tracking-tight">0</p>
            <p class="text-xs text-slate-500 mt-2">Distinct active user session tokens.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Live Traffic Pace</p>
            <div class="mt-4 flex items-center gap-3">
                <span id="traffic-pulse-bar" class="text-4xl font-extrabold text-slate-900">Steady</span>
            </div>
            <p class="text-xs text-slate-500 mt-3">Calculated live requests velocity rate.</p>
        </div>
    </div>

    {{-- Live breakdowns grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        {{-- Top Pages --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm flex flex-col">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Top Pages (Right Now)</h2>
            <div class="flex-grow overflow-y-auto max-h-72">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead>
                        <tr class="text-left font-bold text-slate-400">
                            <th class="pb-2">Path</th>
                            <th class="pb-2 text-right">Activity</th>
                        </tr>
                    </thead>
                    <tbody id="top-pages-list" class="divide-y divide-slate-100 font-semibold text-slate-800">
                        <tr>
                            <td colspan="2" class="py-6 text-center text-slate-400">Loading live pages...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Demographics & Technicals --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Top Countries --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Live Demographics</h3>
                <div id="top-countries-list" class="space-y-3 text-xs font-semibold text-slate-700">
                    <p class="text-slate-400 text-center py-6">Resolving country data...</p>
                </div>
            </div>

            {{-- Top Devices --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Browsing Devices</h3>
                <div id="top-devices-list" class="space-y-3 text-xs font-semibold text-slate-700">
                    <p class="text-slate-400 text-center py-6">Resolving device details...</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Live Request Stream Log --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <h2 class="text-sm font-bold text-slate-900">Live Visitor Stream</h2>
            <p class="text-xs text-slate-500 mt-0.5">Real-time incoming event log (showing last 20 requests).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50/40 text-left font-bold text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Time</th>
                        <th class="px-6 py-3">Fingerprint</th>
                        <th class="px-6 py-3">Page</th>
                        <th class="px-6 py-3">Country</th>
                        <th class="px-6 py-3">Device & Browser</th>
                        <th class="px-6 py-3 text-right">Load Time</th>
                    </tr>
                </thead>
                <tbody id="live-stream-list" class="divide-y divide-slate-100 font-semibold text-slate-800">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">Waiting for live request stream...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Polling Script --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const onlineCounter = document.getElementById('online-counter');
        const activeSessionsCounter = document.getElementById('active-sessions-counter');
        const trafficPulseBar = document.getElementById('traffic-pulse-bar');
        const syncClock = document.getElementById('sync-clock');
        const topPagesList = document.getElementById('top-pages-list');
        const topCountriesList = document.getElementById('top-countries-list');
        const topDevicesList = document.getElementById('top-devices-list');
        const liveStreamList = document.getElementById('live-stream-list');

        function fetchRealtime() {
            fetch('{{ route("admin.analytics.realtime.data") }}')
                .then(response => response.json())
                .then(data => {
                    // Update counters
                    onlineCounter.textContent = data.online_visitors.toLocaleString();
                    activeSessionsCounter.textContent = data.active_sessions.toLocaleString();
                    syncClock.textContent = data.synced_at;

                    // Update Traffic Pace status
                    if (data.online_visitors > 100) {
                        trafficPulseBar.textContent = 'High Traffic';
                        trafficPulseBar.className = 'text-4xl font-extrabold text-rose-600';
                    } else if (data.online_visitors > 20) {
                        trafficPulseBar.textContent = 'Active';
                        trafficPulseBar.className = 'text-4xl font-extrabold text-indigo-650';
                    } else {
                        trafficPulseBar.textContent = 'Steady';
                        trafficPulseBar.className = 'text-4xl font-extrabold text-slate-900';
                    }

                    // Update Top Pages
                    let pagesHtml = '';
                    const pages = Object.entries(data.top_pages);
                    if (pages.length === 0) {
                        pagesHtml = '<tr><td colspan="2" class="py-4 text-center text-slate-400">No active pages in cache.</td></tr>';
                    } else {
                        pages.forEach(([path, count]) => {
                            pagesHtml += `
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 font-bold text-slate-900 truncate max-w-sm">${path}</td>
                                    <td class="py-2.5 text-right font-black text-indigo-600">${count}</td>
                                </tr>
                            `;
                        });
                    }
                    topPagesList.innerHTML = pagesHtml;

                    // Update Top Countries
                    let countriesHtml = '';
                    const countries = Object.entries(data.top_countries);
                    if (countries.length === 0) {
                        countriesHtml = '<p class="text-slate-400 text-center py-4">No countries recorded.</p>';
                    } else {
                        countries.forEach(([code, count]) => {
                            countriesHtml += `
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800">${code}</span>
                                    <span class="px-2 py-0.5 rounded-full bg-slate-50 border border-slate-100 text-slate-600 font-bold">${count}</span>
                                </div>
                            `;
                        });
                    }
                    topCountriesList.innerHTML = countriesHtml;

                    // Update Top Devices
                    let devicesHtml = '';
                    const devices = Object.entries(data.top_devices);
                    if (devices.length === 0) {
                        devicesHtml = '<p class="text-slate-400 text-center py-4">No device data.</p>';
                    } else {
                        devices.forEach(([type, count]) => {
                            devicesHtml += `
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 capitalize">${type}</span>
                                    <span class="px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-750 font-bold">${count}</span>
                                </div>
                            `;
                        });
                    }
                    topDevicesList.innerHTML = devicesHtml;

                    // Update Live Request Stream
                    let streamHtml = '';
                    if (data.recent_visits.length === 0) {
                        streamHtml = '<tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">Waiting for live requests...</td></tr>';
                    } else {
                        data.recent_visits.forEach(v => {
                            const isBotClass = v.is_bot ? 'bg-amber-50 text-amber-800 border-amber-100' : 'bg-indigo-50 text-indigo-800 border-indigo-100';
                            const label = v.is_bot ? (v.bot_type || 'Bot') : 'Human';
                            
                            streamHtml += `
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-3.5 text-slate-500 font-medium">${v.time}</td>
                                    <td class="px-6 py-3.5 text-slate-400 font-medium">${v.fingerprint}</td>
                                    <td class="px-6 py-3.5 font-bold text-slate-900">${v.page_path}</td>
                                    <td class="px-6 py-3.5">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-100 font-bold">${v.country_code}</span>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span class="font-bold text-slate-700 capitalize">${v.device_type}</span> · 
                                        <span class="text-slate-500">${v.browser_name} (${v.os_name})</span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] border font-bold ${isBotClass} ml-1.5">${label}</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right font-black text-slate-600">${v.response_time_ms ? v.response_time_ms + 'ms' : 'N/A'}</td>
                                </tr>
                            `;
                        });
                    }
                    liveStreamList.innerHTML = streamHtml;
                })
                .catch(err => console.error("Error fetching live analytics data:", err));
        }

        // Trigger immediately and then poll
        fetchRealtime();
        setInterval(fetchRealtime, 5000);
    });
</script>
@endsection
