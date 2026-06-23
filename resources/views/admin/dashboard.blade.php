@extends('layouts.app')

@section('title', 'Admin Dashboard - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Dashboard Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Admin Dashboard</h1>
            <p class="text-xs text-slate-500 mt-1">Manage homepage sections, nested fetch keywords, visibility, and the 2-minute multi-category sync cycle.</p>
        </div>
        
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.analytics') }}" class="flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold shadow-sm hover:bg-amber-100 transition-colors">
                <span>Open Analytics</span>
            </a>
            <a href="{{ route('admin.promotions') }}" class="flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 text-xs font-bold shadow-sm hover:bg-sky-100 transition-colors">
                <span>Promotions</span>
            </a>
            <a href="{{ route('admin.trends') }}" class="flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-violet-50 border border-violet-200 text-violet-800 text-xs font-bold shadow-sm hover:bg-violet-100 transition-colors">
                <span>Trend Keywords</span>
            </a>
            <a href="{{ route('admin.destroy') }}" class="flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold shadow-sm hover:bg-rose-100 transition-colors">
                <span>Open Destroy Page</span>
            </a>
            <form action="{{ route('admin.fetch-news') }}" method="POST" id="sync-start-form">
                @csrf
                <button type="submit" id="sync-start-button" class="flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-emerald-400 hover:bg-emerald-500 text-slate-950 text-xs font-bold shadow-md shadow-emerald-500/10 active:scale-98 transition-all duration-150 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17" />
                    </svg>
                    <span>Sync & Fetch News Now</span>
                </button>
            </form>
            <form action="{{ route('admin.fetch-news.restart') }}" method="POST" id="sync-restart-form" class="{{ in_array($syncState['status'], ['queued', 'running', 'stalled'], true) ? '' : 'hidden' }}">
                @csrf
                <button type="submit" id="sync-restart-button" class="flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-xs font-bold shadow-sm transition-colors cursor-pointer">
                    <span>Stop & Resync</span>
                </button>
            </form>
            
            <a href="{{ route('admin.logout') }}" class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-650 hover:text-slate-900 hover:bg-slate-50 text-xs font-bold transition-colors shadow-sm">
                Log Out
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="mb-8 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-start space-x-2.5">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div id="sync-monitor"
         data-sync-status-url="{{ route('admin.sync-status') }}"
         data-initial-sync='@json($syncState)'
         class="mb-8 rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Background Sync Monitor</p>
                    <h2 class="mt-1 text-lg font-extrabold text-slate-900">News crawler progress</h2>
                    <p class="mt-1 text-xs text-slate-500">Live queue state, installer-style steps, and fetch output without blocking the dashboard.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span id="sync-status-badge" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-white">
                        <span class="h-2 w-2 rounded-full bg-white/80"></span>
                        <span>Status</span>
                    </span>
                    <span id="sync-progress-label" class="text-sm font-black text-slate-900">0%</span>
                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-600 shadow-sm">
                        <input id="sync-failsafe-toggle" type="checkbox" class="peer sr-only">
                        <span class="relative h-5 w-9 rounded-full bg-slate-200 transition peer-checked:bg-emerald-500">
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-4"></span>
                        </span>
                        <span>Failsafe Auto Trigger</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div class="rounded-2xl bg-slate-100 overflow-hidden">
                <div id="sync-progress-bar" class="h-3 rounded-2xl bg-gradient-to-r from-emerald-500 via-emerald-400 to-amber-400 transition-all duration-500" style="width: 0%"></div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/80">Automatic Trigger</p>
                    <p id="sync-auto-countdown" class="mt-1 text-2xl font-black tabular-nums text-emerald-800">--:--</p>
                    <p id="sync-auto-status" class="mt-1 text-xs font-semibold text-emerald-900/70">Waiting for scheduler.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Next Auto Fetch</p>
                    <p id="sync-auto-next-at" class="mt-1 text-sm font-bold text-slate-900">Calculating...</p>
                    <p id="sync-auto-note" class="mt-1 text-xs text-slate-500">Laravel scheduler watches this every minute, and web fallback can recover missed slots.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Fetch Runs</p>
                    <p id="sync-auto-runs" class="mt-1 text-lg font-black text-slate-900">{{ number_format($fetchStats['total_runs']) }}</p>
                    <p id="sync-auto-last-at" class="mt-1 text-xs font-semibold text-slate-500">Last refresh pending.</p>
                    <p id="sync-auto-health" class="mt-2 text-xs font-bold {{ ($fetchStats['content_health'] ?? null) === 'stale' ? 'text-rose-600' : (($fetchStats['content_health'] ?? null) === 'healthy' ? 'text-emerald-600' : 'text-amber-600') }}">{{ $fetchStats['content_health_label'] ?? 'Monitoring' }}</p>
                    <p id="sync-auto-health-note" class="mt-1 text-xs text-slate-500">{{ $fetchStats['content_health_message'] ?? 'Waiting for the first 15-minute comparison window.' }} Current total: {{ number_format($fetchStats['news_total'] ?? 0) }}.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-950 px-4 py-3 text-white">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Run Type</p>
                    <p class="mt-1 text-sm font-black">Async queue worker</p>
                    <p id="sync-auto-interval" class="mt-1 text-xs text-slate-300">Every {{ $fetchStats['interval_minutes'] }} minutes fetch {{ $fetchStats['section_batch_size'] ?? 12 }} of {{ $fetchStats['section_count'] }} active sections. Full rotation in {{ $fetchStats['cycles_to_cover_all_sections'] ?? 1 }} runs.</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 xl:grid-cols-[1.15fr_0.85fr] gap-5">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Current Stage</p>
                            <p id="sync-stage" class="mt-1 text-sm font-bold text-slate-900">Idle</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Sections</p>
                            <p id="sync-topic-progress" class="mt-1 text-sm font-bold text-slate-900">0 / 0</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">New Articles</p>
                            <p id="sync-new-articles" class="mt-1 text-sm font-bold text-emerald-600">0</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Duplicates</p>
                            <p id="sync-duplicates" class="mt-1 text-sm font-bold text-amber-600">0</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.18)] animate-pulse"></span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/80">Doing Now</p>
                                <p id="sync-current-action" class="mt-1 text-sm font-bold text-emerald-900">Waiting for next sync.</p>
                                <p id="sync-current-detail" class="mt-1 text-xs text-emerald-800/80">No active fetch step at the moment.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Requested</p>
                            <p id="sync-requested-at" class="mt-1 text-xs font-semibold text-slate-700">Not started</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Started</p>
                            <p id="sync-started-at" class="mt-1 text-xs font-semibold text-slate-700">Not started</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Finished</p>
                            <p id="sync-finished-at" class="mt-1 text-xs font-semibold text-slate-700">Waiting</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-950 text-slate-100 overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-white/10">
                            <h3 class="text-sm font-bold">Process Output</h3>
                            <span class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Live log</span>
                        </div>
                        <div id="sync-log" class="max-h-72 overflow-y-auto px-4 py-4 space-y-2 text-xs font-medium"></div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Recovered Media</h3>
                                <p class="mt-1 text-xs text-slate-500">Fallback image extraction and FIFA official feed results.</p>
                            </div>
                            <a href="{{ route('news.gallery') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-3 py-2 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-50">
                                News Gallery
                            </a>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white px-4 py-3 border border-slate-200">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Recovered Images</p>
                                <p id="sync-images-recovered" class="mt-1 text-lg font-black text-slate-900">0</p>
                            </div>
                            <div class="rounded-2xl bg-white px-4 py-3 border border-slate-200">
                                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Official FIFA</p>
                                <p id="sync-official-articles" class="mt-1 text-lg font-black text-slate-900">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Final Summary</h3>
                        <p id="sync-summary" class="mt-2 text-sm text-slate-600 leading-6">No sync has run yet in this session.</p>
                        <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 border border-slate-200">
                            <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Last Output</p>
                            <p id="sync-last-output" class="mt-2 text-xs font-medium text-slate-700 whitespace-pre-line break-words">Waiting for next run.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Kerala Lottery Admin Panel ── --}}
    @if(\Illuminate\Support\Facades\Schema::hasTable('lottery_results'))
    <div class="mb-8 rounded-3xl border border-emerald-200/80 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-emerald-100 bg-emerald-50/60">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600/80">Kerala State Lotteries</p>
                    <h2 class="mt-1 text-lg font-extrabold text-slate-900">Lottery Results Admin</h2>
                    <p class="mt-1 text-xs text-slate-500">Sync latest PDFs from keralalotteries.com or re-parse existing ones.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('admin.lottery.sync') }}" method="POST">
                        @csrf
                        <input type="hidden" name="limit" value="10">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-600">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17"/></svg>
                            Sync Latest (10)
                        </button>
                    </form>
                    <form action="{{ route('admin.lottery.reparse') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 shadow-sm transition hover:bg-indigo-100">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Re-parse Failed
                        </button>
                    </form>
                    <form action="{{ route('admin.lottery.reparse') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="all">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-100">
                            Re-parse All
                        </button>
                    </form>
                    <a href="{{ route('kerala-lottery.index') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        View Public Page ↗
                    </a>
                </div>
            </div>
        </div>
        <div class="p-5">
            @php
                $lotteryStats = [
                    'total'       => \App\Models\LotteryResult::count(),
                    'available'   => \App\Models\LotteryResult::where('status', 'available')->count(),
                    'failed'      => \App\Models\LotteryResult::where('status', 'parse_failed')->count(),
                    'waiting'     => \App\Models\LotteryResult::whereIn('status', ['waiting', 'pdf_available'])->count(),
                    'latest'      => \App\Models\LotteryResult::latest('result_date')->first(),
                ];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Total Results</p>
                    <p class="mt-1 text-2xl font-black text-slate-900">{{ $lotteryStats['total'] }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/80">Available ✓</p>
                    <p class="mt-1 text-2xl font-black text-emerald-700">{{ $lotteryStats['available'] }}</p>
                </div>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-red-700/80">Parse Failed</p>
                    <p class="mt-1 text-2xl font-black text-red-700">{{ $lotteryStats['failed'] }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700/80">Waiting</p>
                    <p class="mt-1 text-2xl font-black text-amber-700">{{ $lotteryStats['waiting'] }}</p>
                </div>
            </div>
            @if($lotteryStats['latest'])
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <span class="font-bold text-slate-800">Latest:</span>
                    {{ $lotteryStats['latest']->lottery_name }} · {{ $lotteryStats['latest']->draw_number }} ·
                    {{ optional($lotteryStats['latest']->result_date)->format('d M Y') }} ·
                    <span class="{{ $lotteryStats['latest']->status === 'available' ? 'text-emerald-600 font-bold' : 'text-red-600 font-bold' }}">
                        {{ $lotteryStats['latest']->status === 'available' ? 'Available' : str_replace('_', ' ', $lotteryStats['latest']->status) }}
                    </span>
                </div>
            @endif
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        
        <!-- Left Side: Section and Topic Management -->
        <div class="space-y-8 lg:col-span-1">
            
            <!-- Create Section Form -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-1.5">
                    <svg class="w-4 h-4 text-emerald-650" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Add New Section</span>
                </h3>
                
                <form action="{{ route('admin.sections.store') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Section Name</label>
                        <input type="text" name="name" required placeholder="e.g. Technology" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-455 mb-1.5 uppercase tracking-wider">Description</label>
                        <textarea name="description" rows="3" placeholder="What appears on the landing page for this section." class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-550 transition-colors cursor-pointer shadow-sm">
                        Save Section
                    </button>
                </form>
            </div>

            <!-- Create Topic Form -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-1.5">
                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-6H5m14 12H5m14 6H5" />
                    </svg>
                    <span>Add Topic Keyword</span>
                </h3>
                
                <form action="{{ route('admin.topics.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Section</label>
                        <select name="news_section_id" required class="w-full bg-slate-50 border border-slate-200 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 outline-none cursor-pointer">
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Topic Name</label>
                        <input type="text" name="name" required placeholder="e.g. OpenAI & LLMs" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-455 mb-1.5 uppercase tracking-wider">Search Keyword</label>
                        <input type="text" name="keyword" required placeholder="e.g. OpenAI news" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Language (HL)</label>
                            <input type="text" name="language" value="en" required maxlength="2" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 outline-none text-center">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Country (GL)</label>
                            <input type="text" name="country" value="US" required maxlength="2" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 outline-none text-center">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-slate-950 bg-sky-400 hover:bg-sky-500 transition-colors cursor-pointer shadow-sm">
                        Save Topic
                    </button>
                </form>
            </div>

            <!-- Admin Profile Details Form -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-1.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Admin Profile Details</span>
                </h3>
                
                <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Admin Name</label>
                        <input type="text" name="name" value="{{ $adminName }}" required placeholder="e.g. Administrator" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 outline-none transition-all">
                    </div>
                    
                    <div class="border-t border-slate-100 pt-3">
                        <span class="text-[10px] font-bold text-slate-400 block mb-2 uppercase tracking-wider">Change Passcode (Optional)</span>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-450 mb-1">Current Passcode</label>
                                <input type="password" name="current_passcode" placeholder="Enter current passcode" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none">
                                @error('current_passcode')
                                    <p class="mt-1 text-[10px] text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-450 mb-1">New Passcode</label>
                                <input type="password" name="new_passcode" placeholder="Enter new passcode" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none">
                                @error('new_passcode')
                                    <p class="mt-1 text-[10px] text-red-650 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-450 mb-1">Confirm New Passcode</label>
                                <input type="password" name="new_passcode_confirmation" placeholder="Confirm new passcode" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-slate-950 bg-amber-400 hover:bg-amber-500 transition-colors cursor-pointer shadow-sm">
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Section List -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 mb-4">News Sections ({{ $sections->count() }})</h3>
                <div class="space-y-3.5 max-h-[400px] overflow-y-auto pr-1">
                    @foreach($sections as $section)
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-150 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-xs font-bold text-slate-800 truncate">{{ $section->name }}</h4>
                                        @if($section->is_default)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.16em] text-amber-700">Default</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $section->description }}</p>
                                    <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase bg-slate-200/70 px-1 py-0.2 rounded border border-slate-300/40">
                                            {{ $section->news_topics_count }} topics
                                        </span>
                                        <span class="text-[9px] text-slate-450 font-medium">
                                            {{ $section->news_items_count }} articles
                                        </span>
                                        <span class="text-[9px] text-slate-450 font-medium">
                                            / {{ $section->card_limit }} cards
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-1 shrink-0">
                                    <form action="{{ route('admin.sections.toggle', $section) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg border cursor-pointer @if($section->is_active) border-emerald-500/20 text-emerald-600 bg-emerald-500/10 hover:bg-emerald-500/20 @else border-slate-200 text-slate-400 bg-slate-100 hover:text-slate-650 @endif transition-colors" title="{{ $section->is_active ? 'Deactivate Section' : 'Activate Section' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.07 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                                            </svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.sections.default', $section) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg border border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors cursor-pointer" title="Set Default Section">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.sections.delete', $section) }}" method="POST" onsubmit="return confirm('Delete this section and all its topics? Articles already fetched will remain unless they are attached by cascade.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg border border-red-500/10 text-red-600 bg-red-50 hover:bg-red-100/65 transition-colors cursor-pointer" title="Delete Section">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="space-y-2">
                                @foreach($section->newsTopics as $topic)
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-bold text-slate-800 truncate">{{ $topic->name }}</p>
                                            <p class="text-[10px] text-slate-400 truncate">{{ $topic->keyword }} · {{ $topic->language }}-{{ $topic->country }}</p>
                                        </div>
                                        <div class="flex items-center space-x-1 shrink-0">
                                            <form action="{{ route('admin.topics.toggle', $topic) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="p-1.5 rounded-lg border cursor-pointer @if($topic->is_active) border-emerald-500/20 text-emerald-600 bg-emerald-500/10 hover:bg-emerald-500/20 @else border-slate-200 text-slate-400 bg-slate-100 hover:text-slate-650 @endif transition-colors" title="{{ $topic->is_active ? 'Deactivate Topic' : 'Activate Topic' }}">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.07 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                                                    </svg>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.topics.delete', $topic) }}" method="POST" onsubmit="return confirm('Delete this topic keyword?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg border border-red-500/10 text-red-600 bg-red-50 hover:bg-red-100/65 transition-colors cursor-pointer" title="Delete Topic">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
        </div>

        <!-- Right Side: Article List & Management -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
                
                <h3 class="text-base font-bold text-slate-900 mb-4">Articles Management</h3>

                <!-- Filter Form -->
                <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-col sm:flex-row gap-3 mb-5">
                    <div class="flex-grow relative">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by title or source..." class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-2 pl-9 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all">
                        <div class="absolute left-3 top-2.5 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    <div class="sm:w-48">
                        <select name="section" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-slate-600 outline-none cursor-pointer">
                            <option value="all">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected((string)$selectedSectionId === (string)$section->id)>
                                    {{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:w-48">
                        <select name="topic" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-slate-600 outline-none cursor-pointer">
                            <option value="all">All Topics</option>
                            @foreach($sections as $section)
                                @foreach($section->newsTopics as $topic)
                                    <option value="{{ $topic->id }}" @selected((string)$selectedTopicId === (string)$topic->id)>
                                        {{ $section->name }} / {{ $topic->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:w-48">
                        <select name="sort" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-slate-600 outline-none cursor-pointer">
                            <option value="latest" @selected($sort === 'latest')>Latest</option>
                            <option value="most_viewed" @selected($sort === 'most_viewed')>Most Viewed</option>
                            <option value="most_clicked" @selected($sort === 'most_clicked')>Most Clicked</option>
                            <option value="title" @selected($sort === 'title')>Title A-Z</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-950 text-xs font-semibold rounded-xl transition-colors cursor-pointer shadow-sm">
                        Filter
                    </button>
                    
                    @if($search || ($selectedSectionId && $selectedSectionId !== 'all') || ($selectedTopicId && $selectedTopicId !== 'all') || $sort !== 'latest')
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 text-xs text-slate-400 hover:text-slate-600 self-center">
                            Clear
                        </a>
                    @endif
                </form>

                <!-- Article List Table -->
                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-slate-50/50">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-slate-650 font-bold uppercase tracking-wider">
                                <th class="p-3.5">Article Details</th>
                                <th class="p-3.5 text-center w-24">Views</th>
                                <th class="p-3.5 text-center w-24">Clicks</th>
                                <th class="p-3.5 text-center w-24">Visible</th>
                                <th class="p-3.5 text-center w-24">Featured</th>
                                <th class="p-3.5 text-center w-16">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 bg-white">
                            @forelse($articles as $article)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5">
                                        <div class="flex flex-col gap-1 max-w-[400px]">
                                            <a href="{{ $article->url }}" target="_blank" class="font-bold text-slate-800 hover:text-emerald-600 transition-colors line-clamp-2">
                                                {{ $article->title }}
                                            </a>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[10px] text-slate-450">
                                                <span class="font-bold text-slate-650 bg-slate-100 px-1 py-0.2 rounded">{{ $article->source_name }}</span>
                                                <span>•</span>
                                                <span>{{ $article->published_at->format('M d, Y H:i') }}</span>
                                                <span>•</span>
                                                <span class="text-slate-400 italic">#{{ $article->newsSection?->name ?? 'Section' }}</span>
                                                <span>•</span>
                                                <span class="text-slate-400 italic">{{ $article->newsTopic->name }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-3.5 text-center">
                                        <span class="text-xs font-bold text-slate-800">{{ number_format($article->views_count) }}</span>
                                    </td>

                                    <td class="p-3.5 text-center">
                                        <span class="text-xs font-bold text-amber-600">{{ number_format($article->clicks_count) }}</span>
                                    </td>
                                    
                                    <!-- Visibility status toggle -->
                                    <td class="p-3.5 text-center">
                                        <form action="{{ route('admin.articles.toggle-visibility', $article) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold border cursor-pointer transition-colors 
                                                @if($article->is_visible) border-emerald-500/20 text-emerald-650 bg-emerald-50 hover:bg-emerald-100/70 @else border-slate-200 text-slate-400 bg-slate-100/60 hover:text-slate-600 @endif">
                                                {{ $article->is_visible ? 'Visible' : 'Hidden' }}
                                            </button>
                                        </form>
                                    </td>
                                    
                                    <!-- Featured status toggle -->
                                    <td class="p-3.5 text-center">
                                        <form action="{{ route('admin.articles.toggle-featured', $article) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold border cursor-pointer transition-colors 
                                                @if($article->is_featured) border-amber-400/20 text-amber-700 bg-amber-50 hover:bg-amber-100/80 @else border-slate-200 text-slate-400 bg-slate-100/60 hover:text-slate-600 @endif">
                                                {{ $article->is_featured ? 'Featured' : 'Standard' }}
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Delete article button -->
                                    <td class="p-3.5 text-center">
                                        <form action="{{ route('admin.articles.delete', $article) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this article?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 rounded-lg border border-red-100 text-red-600 bg-red-50/30 hover:bg-red-50 hover:text-red-700 cursor-pointer transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400 italic">
                                        No articles found. Try changing your filters or searching another keyword.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Custom Pagination -->
                @if($articles->hasPages())
                    <div class="mt-5">
                        {{ $articles->appends(request()->query())->links() }}
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const monitor = document.getElementById('sync-monitor');

        if (!monitor) {
            return;
        }

        const statusUrl = monitor.dataset.syncStatusUrl;
        let syncState = JSON.parse(monitor.dataset.initialSync || '{}');
        let poller = null;
        let pollDelay = null;
        let lastFailsafeTrigger = null;

        const els = {
            badge: document.getElementById('sync-status-badge'),
            startButton: document.getElementById('sync-start-button'),
            startForm: document.getElementById('sync-start-form'),
            restartForm: document.getElementById('sync-restart-form'),
            progressLabel: document.getElementById('sync-progress-label'),
            progressBar: document.getElementById('sync-progress-bar'),
            stage: document.getElementById('sync-stage'),
            currentAction: document.getElementById('sync-current-action'),
            currentDetail: document.getElementById('sync-current-detail'),
            topicProgress: document.getElementById('sync-topic-progress'),
            newArticles: document.getElementById('sync-new-articles'),
            duplicates: document.getElementById('sync-duplicates'),
            requestedAt: document.getElementById('sync-requested-at'),
            startedAt: document.getElementById('sync-started-at'),
            finishedAt: document.getElementById('sync-finished-at'),
            log: document.getElementById('sync-log'),
            imagesRecovered: document.getElementById('sync-images-recovered'),
            officialArticles: document.getElementById('sync-official-articles'),
            summary: document.getElementById('sync-summary'),
            lastOutput: document.getElementById('sync-last-output'),
            autoCountdown: document.getElementById('sync-auto-countdown'),
            autoStatus: document.getElementById('sync-auto-status'),
            autoNextAt: document.getElementById('sync-auto-next-at'),
            autoNote: document.getElementById('sync-auto-note'),
            autoRuns: document.getElementById('sync-auto-runs'),
            autoLastAt: document.getElementById('sync-auto-last-at'),
            autoInterval: document.getElementById('sync-auto-interval'),
            autoHealth: document.getElementById('sync-auto-health'),
            autoHealthNote: document.getElementById('sync-auto-health-note'),
            failsafeToggle: document.getElementById('sync-failsafe-toggle'),
        };

        const FAILSAFE_STORAGE_KEY = 'admin-sync-failsafe-enabled';
        const FAILSAFE_SLOT_KEY = 'admin-sync-failsafe-last-slot';
        const FAILSAFE_GRACE_SECONDS = 20;

        const formatTime = (iso) => {
            if (!iso) {
                return 'Waiting';
            }

            try {
                return new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }).format(new Date(iso));
            } catch (error) {
                return iso;
            }
        };

        const secondsUntilNextFetch = (state) => {
            const nextFetch = state.fetch_stats?.next_scheduled_at;

            if (!nextFetch) {
                return null;
            }

            const diffMs = new Date(nextFetch).getTime() - Date.now();

            if (Number.isNaN(diffMs)) {
                return null;
            }

            return Math.max(0, Math.floor(diffMs / 1000));
        };

        const formatCountdown = (seconds) => {
            if (seconds === null) {
                return '--:--';
            }

            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;

            return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
        };

        const shouldPoll = (state) => ['queued', 'running'].includes(state.status);

        const failsafeEnabled = () => els.failsafeToggle?.checked === true;

        const activeSlotKey = (state) => {
            const nextFetch = state.fetch_stats?.next_scheduled_at;
            return nextFetch || null;
        };

        const restoreFailsafeState = () => {
            if (!els.failsafeToggle) {
                return;
            }

            els.failsafeToggle.checked = localStorage.getItem(FAILSAFE_STORAGE_KEY) === '1';
            lastFailsafeTrigger = localStorage.getItem(FAILSAFE_SLOT_KEY);
        };

        const rememberFailsafeTrigger = (slotKey) => {
            lastFailsafeTrigger = slotKey;
            localStorage.setItem(FAILSAFE_SLOT_KEY, slotKey);
        };

        const maybeTriggerFailsafe = () => {
            if (!failsafeEnabled() || !els.startForm || shouldPoll(syncState)) {
                return;
            }

            const nextFetch = syncState.fetch_stats?.next_scheduled_at;

            if (!nextFetch) {
                return;
            }

            const diffMs = new Date(nextFetch).getTime() - Date.now();

            if (Number.isNaN(diffMs)) {
                return;
            }

            const overdueSeconds = Math.floor(Math.abs(diffMs) / 1000);
            const slotKey = activeSlotKey(syncState);

            if (diffMs > 0 || overdueSeconds < FAILSAFE_GRACE_SECONDS || !slotKey) {
                return;
            }

            if (lastFailsafeTrigger === slotKey) {
                return;
            }

            rememberFailsafeTrigger(slotKey);

            if (els.autoStatus) {
                els.autoStatus.textContent = 'Failsafe triggered. Starting manual sync because the scheduler missed this window.';
            }

            els.startForm.requestSubmit();
        };

        const pollingDelayFor = (state) => {
            const seconds = secondsUntilNextFetch(state);

            if (shouldPoll(state) || (seconds !== null && seconds <= 90)) {
                return 2500;
            }

            return 10000;
        };

        function setPollingDelay(delay) {
            if (poller && pollDelay === delay) {
                return;
            }

            if (poller) {
                window.clearInterval(poller);
            }

            pollDelay = delay;
            poller = window.setInterval(() => {
                fetchState().catch(() => {});
            }, delay);
        }

        const updateAutoCountdown = () => {
            const seconds = secondsUntilNextFetch(syncState);

            if (els.autoCountdown) {
                els.autoCountdown.textContent = formatCountdown(seconds);
            }

            if (els.autoStatus) {
                if (shouldPoll(syncState)) {
                    els.autoStatus.textContent = 'Automatic monitor is following the active fetch.';
                } else if (seconds === null) {
                    els.autoStatus.textContent = 'Waiting for scheduler data.';
                } else if (seconds === 0) {
                    els.autoStatus.textContent = failsafeEnabled()
                        ? 'Due now. Failsafe will trigger if Laravel does not queue the job.'
                        : 'Due now. Waiting for Laravel to queue the job.';
                } else if (seconds <= 90) {
                    els.autoStatus.textContent = failsafeEnabled()
                        ? 'Scheduler window is near. Failsafe is armed and polling faster.'
                        : 'Scheduler window is near. Polling faster.';
                } else {
                    els.autoStatus.textContent = failsafeEnabled()
                        ? 'Countdown to the next automatic fetch. Failsafe is armed.'
                        : 'Countdown to the next automatic fetch.';
                }
            }

            if (els.autoNote) {
                els.autoNote.textContent = failsafeEnabled()
                    ? `If Laravel scheduler misses the slot, this page will auto-submit Sync & Fetch News Now after ${FAILSAFE_GRACE_SECONDS} seconds.`
                    : 'Laravel scheduler watches this every minute.';
            }

            if (seconds !== null && seconds <= 90 && !shouldPoll(syncState)) {
                setPollingDelay(2500);
            }

            maybeTriggerFailsafe();
        };

        const statusTheme = (status) => {
            switch (status) {
                case 'running':
                    return ['bg-emerald-600', 'Sync Running', 'bg-emerald-300'];
                case 'queued':
                    return ['bg-amber-500', 'Queued', 'bg-amber-200'];
                case 'completed':
                    return ['bg-sky-600', 'Completed', 'bg-sky-200'];
                case 'partial_failed':
                    return ['bg-orange-700', 'Partial Failed', 'bg-orange-200'];
                case 'failed':
                    return ['bg-red-600', 'Failed', 'bg-red-200'];
                case 'stopped':
                    return ['bg-rose-600', 'Stopped', 'bg-rose-200'];
                case 'stalled':
                    return ['bg-orange-600', 'Stalled', 'bg-orange-200'];
                default:
                    return ['bg-slate-900', 'Idle', 'bg-white/80'];
            }
        };

        const logTone = (level) => {
            switch (level) {
                case 'error':
                    return 'text-red-300';
                case 'warning':
                    return 'text-amber-300';
                case 'success':
                    return 'text-emerald-300';
                default:
                    return 'text-slate-200';
            }
        };

        const render = (state) => {
            const meta = state.meta || {};
            const stats = meta.stats || {};
            const fetchStats = state.fetch_stats || {};
            const progress = Math.max(0, Math.min(100, Number(meta.progress || 0)));
            const [badgeClass, badgeText, dotClass] = statusTheme(state.status);
            const processedSections = Number(meta.processed_sections || 0);
            const totalSections = Number(meta.total_sections || 0);
            const currentTopic = meta.current_topic || null;
            const currentSection = meta.current_section || null;
            const currentItem = Number(meta.current_item || 0);
            const totalItems = Number(meta.total_items || 0);
            const currentArticle = meta.current_article || null;

            els.badge.className = `inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-white ${badgeClass}`;
            els.badge.innerHTML = `<span class="h-2 w-2 rounded-full ${dotClass}"></span><span>${badgeText}</span>`;

            els.progressLabel.textContent = `${progress}%`;
            els.progressBar.style.width = `${progress}%`;
            els.stage.textContent = meta.stage || 'Idle';
            els.topicProgress.textContent = `${processedSections} / ${totalSections}`;
            els.newArticles.textContent = String(stats.new_articles || 0);
            els.duplicates.textContent = String(stats.skipped_duplicates || 0);
            els.imagesRecovered.textContent = String(stats.images_recovered || 0);
            els.officialArticles.textContent = String(stats.official_articles || 0);
            els.requestedAt.textContent = formatTime(state.requested_at);
            els.startedAt.textContent = state.started_at ? formatTime(state.started_at) : 'Not started';
            els.finishedAt.textContent = state.finished_at ? formatTime(state.finished_at) : 'Waiting';
            els.summary.textContent = meta.summary || 'Background sync is waiting for new work.';
            els.lastOutput.textContent = state.last_output || 'Waiting for next run.';

            if (els.autoNextAt) {
                els.autoNextAt.textContent = fetchStats.next_scheduled_at ? formatTime(fetchStats.next_scheduled_at) : 'Calculating...';
            }

            if (els.autoRuns) {
                els.autoRuns.textContent = new Intl.NumberFormat().format(Number(fetchStats.total_runs || 0));
            }

            if (els.autoLastAt) {
                els.autoLastAt.textContent = fetchStats.last_success_at
                    ? `Last refresh ${formatTime(fetchStats.last_success_at)}`
                    : 'Last refresh pending.';
            }

            if (els.autoInterval) {
                els.autoInterval.textContent = `Every ${fetchStats.interval_minutes || 2} minutes fetch ${fetchStats.section_batch_size || 12} of ${fetchStats.section_count || 0} active sections. Full rotation in ${fetchStats.cycles_to_cover_all_sections || 1} runs.`;
            }

            if (els.autoHealth) {
                els.autoHealth.textContent = fetchStats.content_health_label || 'Monitoring';
                els.autoHealth.className = `mt-2 text-xs font-bold ${
                    fetchStats.content_health === 'stale'
                        ? 'text-rose-600'
                        : fetchStats.content_health === 'healthy'
                            ? 'text-emerald-600'
                            : 'text-amber-600'
                }`;
            }

            if (els.autoHealthNote) {
                const newsTotal = new Intl.NumberFormat().format(Number(fetchStats.news_total || 0));
                const healthMessage = fetchStats.content_health_message || 'Waiting for the first 15-minute comparison window.';
                els.autoHealthNote.textContent = `${healthMessage} Current total: ${newsTotal}.`;
            }

            updateAutoCountdown();
            setPollingDelay(pollingDelayFor(state));

            if (els.startButton) {
                els.startButton.disabled = ['queued', 'running'].includes(state.status);
                els.startButton.classList.toggle('opacity-60', els.startButton.disabled);
                els.startButton.classList.toggle('cursor-not-allowed', els.startButton.disabled);
            }

            if (els.restartForm) {
                els.restartForm.classList.toggle('hidden', !['queued', 'running', 'stalled'].includes(state.status));
            }

            if (state.status === 'running') {
                els.currentAction.textContent = currentSection
                    ? `Working on section ${processedSections + 1 > totalSections ? totalSections : processedSections + 1} of ${totalSections}: ${currentSection}`
                    : (meta.stage || 'Running sync steps');
                els.currentDetail.textContent = currentItem > 0 && totalItems > 0
                    ? `${currentTopic ? `Topic ${currentTopic}. ` : ''}Article ${currentItem} of ${totalItems}${currentArticle ? `: ${currentArticle}` : ''}. New articles ${stats.new_articles || 0}, duplicates ${stats.skipped_duplicates || 0}, recovered images ${stats.images_recovered || 0}.`
                    : `${currentTopic ? `Topic ${currentTopic}. ` : ''}Current progress ${progress}%. New articles ${stats.new_articles || 0}, duplicates ${stats.skipped_duplicates || 0}, recovered images ${stats.images_recovered || 0}.`;
            } else if (state.status === 'queued') {
                els.currentAction.textContent = 'Preparing background sync job.';
                els.currentDetail.textContent = 'The response has returned and Laravel is about to start the fetch process.';
            } else if (state.status === 'completed') {
                els.currentAction.textContent = 'Latest sync completed successfully.';
                els.currentDetail.textContent = meta.summary || 'The crawler finished all configured steps.';
            } else if (state.status === 'partial_failed') {
                els.currentAction.textContent = 'Sync completed with some section failures.';
                els.currentDetail.textContent = meta.summary || 'One or more sections did not complete cleanly.';
            } else if (['failed', 'stalled', 'stopped'].includes(state.status)) {
                els.currentAction.textContent = 'Sync needs attention.';
                els.currentDetail.textContent = state.last_output || 'The current run did not complete cleanly.';
            } else {
                els.currentAction.textContent = 'Waiting for next sync.';
                els.currentDetail.textContent = 'No active fetch step at the moment.';
            }

            const log = Array.isArray(state.log) ? state.log : [];
            if (log.length === 0) {
                els.log.innerHTML = '<p class="text-slate-400">No process messages yet.</p>';
            } else {
                els.log.innerHTML = log.map((entry) => `
                    <div class="flex gap-3">
                        <span class="shrink-0 text-slate-500">${formatTime(entry.time)}</span>
                        <span class="${logTone(entry.level)}">${entry.message}</span>
                    </div>
                `).join('');
                els.log.scrollTop = els.log.scrollHeight;
            }
        };

        const fetchState = async () => {
            const response = await fetch(statusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error('Failed to load sync status');
            }

            syncState = await response.json();
            render(syncState);
        };

        render(syncState);
        restoreFailsafeState();
        updateAutoCountdown();
        window.setInterval(updateAutoCountdown, 1000);

        if (els.failsafeToggle) {
            els.failsafeToggle.addEventListener('change', () => {
                localStorage.setItem(FAILSAFE_STORAGE_KEY, els.failsafeToggle.checked ? '1' : '0');
                updateAutoCountdown();
            });
        }

        if (els.startForm) {
            els.startForm.addEventListener('submit', () => {
                setPollingDelay(2500);
                window.setTimeout(() => {
                    fetchState().catch(() => {});
                }, 500);
            });
        }
    });
</script>
@endsection
