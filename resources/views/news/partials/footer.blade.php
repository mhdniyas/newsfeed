<!-- Footer -->
<footer class="border-t border-slate-200 bg-gradient-to-b from-white to-slate-50 pt-10 pb-28 md:pb-10 shadow-inner mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pb-8 border-b border-slate-200/60">
            <!-- Brand & Description -->
            <div class="lg:col-span-4 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-xs font-black tracking-widest text-slate-700 shadow-sm">SZ</span>
                    <span class="text-sm font-extrabold text-slate-900 tracking-wider uppercase">Signalz Online</span>
                </div>
                <p class="text-xs leading-relaxed text-slate-500 max-w-sm">
                    Signalz Online is an independent real-time sports portal tracking FIFA World Cup 2026 match schedules, news, live updates, and sporting analysis.
                </p>
            </div>

            <!-- Stats card -->
            <div class="lg:col-span-8">
                @if(isset($visitStats) || isset($fetchStats))
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.14)]"></span>
                                <span class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">{{ isset($visitStats) ? 'Audience Snapshot' : 'Sync Snapshot' }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:flex lg:flex-wrap lg:justify-end">
                                @if(isset($visitStats))
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-left">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Visits</p>
                                        <p class="mt-1 text-sm font-extrabold text-slate-900">{{ number_format($visitStats['total']) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-left">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/70">Today</p>
                                        <p class="mt-1 text-sm font-extrabold text-emerald-700">{{ number_format($visitStats['today']) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-left">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Unique Today</p>
                                        <p class="mt-1 text-sm font-extrabold text-slate-900">{{ number_format($visitStats['unique_today']) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-amber-50 px-3 py-2 text-left">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700/70">All Unique</p>
                                        <p class="mt-1 text-sm font-extrabold text-amber-700">{{ number_format($visitStats['unique_total']) }}</p>
                                    </div>
                                @elseif(isset($fetchStats))
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-left">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Fetch Runs</p>
                                        <p class="mt-1 text-sm font-extrabold text-slate-900">{{ number_format($fetchStats['total_runs']) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-left">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/70">Interval</p>
                                        <p class="mt-1 text-sm font-extrabold text-emerald-700">{{ $fetchStats['interval_minutes'] }} min</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 flex flex-col gap-1 border-t border-slate-100 pt-3 text-[11px] text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                            @if(isset($visitStats))
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
                                    <span id="viewer-country" class="font-medium">Country detecting...</span>
                                    <span id="viewer-time" class="font-medium">Local time loading...</span>
                                </div>
                            @else
                                <div class="flex flex-col gap-1">
                                    <span class="font-medium text-slate-600">Admin sync footer mirrors fetch activity</span>
                                    <span class="text-slate-500">Track refresh timing without leaving the dashboard.</span>
                                </div>
                            @endif
                            @if(isset($fetchStats))
                                <div class="flex flex-col gap-1 text-[11px] sm:items-end">
                                    <span class="font-medium text-slate-600">Fetched {{ number_format($fetchStats['total_runs']) }} times</span>
                                    <span class="text-slate-500">Last refresh {{ $fetchStats['last_success_at'] ? \Carbon\Carbon::parse($fetchStats['last_success_at'])->diffForHumans() : 'not yet available' }}</span>
                                    <span class="text-slate-500">Next fetch in <span class="font-semibold text-slate-700 js-fetch-countdown" data-next-fetch="{{ $fetchStats['next_scheduled_at'] ?? '' }}">calculating...</span></span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bottom Copyright & Links -->
        <div class="mt-6 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <p>&copy; {{ date('Y') }} Signalz Online. All rights reserved.</p>
            <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 font-semibold text-slate-500">
                <a href="{{ route('pages.about') }}" class="hover:text-slate-950 transition font-medium">About Us</a>
                <a href="{{ route('pages.contact') }}" class="hover:text-slate-950 transition font-medium">Contact Us</a>
                <a href="{{ route('pages.privacy') }}" class="hover:text-slate-950 transition font-medium">Privacy Policy</a>
                <a href="{{ route('pages.terms') }}" class="hover:text-slate-950 transition font-medium">Terms</a>
                <a href="{{ route('pages.disclaimer') }}" class="hover:text-slate-950 transition font-medium">Disclaimer</a>
                <a href="{{ route('pages.affiliate') }}" class="hover:text-slate-950 transition font-medium">Affiliate Disclosure</a>
            </div>
        </div>
    </div>
</footer>
