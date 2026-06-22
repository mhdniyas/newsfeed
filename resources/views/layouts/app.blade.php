<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'FIFA World Cup 2026 News Explorer')</title>
    <meta name="description" content="@yield('meta_description', 'Get the latest FIFA World Cup 2026 news, fixtures, team updates, player news, and live football stories updated every 10 minutes.')">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'FIFA World Cup 2026 News Explorer')">
    <meta property="og:description" content="@yield('meta_description', 'Get the latest FIFA World Cup 2026 news, fixtures, team updates, player news, and live football stories.')">
    <meta property="og:image" content="{{ asset('og-image.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Tailwind CSS / Vite -->
    @vite(['resources/css/app.css'])

    @if(config('services.adsense.client'))
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('services.adsense.client') }}" crossorigin="anonymous"></script>
    @endif

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }
        /* Custom scrollbar for premium touch */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    @yield('styles')
</head>
<body class="flex flex-col min-h-screen bg-slate-50 text-slate-800 antialiased overflow-x-hidden">

    @if(isset($tickerArticles) && $tickerArticles->isNotEmpty())
        <div class="sticky top-0 z-[60] border-b border-slate-900/80 bg-slate-950 text-white shadow-lg">
            <div class="max-w-7xl mx-auto flex items-center gap-4 overflow-hidden px-4 sm:px-6 lg:px-8 h-11">
                <div class="shrink-0 inline-flex items-center gap-2 rounded-full bg-emerald-500/18 border border-emerald-400/30 px-3 py-1">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_0_4px_rgba(52,211,153,0.15)]"></span>
                    <span class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-emerald-200">Breaking News</span>
                </div>
                <div class="ticker-mask relative min-w-0 flex-1 overflow-hidden">
                    <div class="ticker-track flex items-center gap-8 whitespace-nowrap">
                        @foreach($tickerArticles as $tickerArticle)
                            <a href="{{ route('news.visit', $tickerArticle) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-3 text-sm font-medium text-slate-200 transition-colors hover:text-white">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                <span>{{ $tickerArticle->title }}</span>
                            </a>
                        @endforeach
                        @foreach($tickerArticles as $tickerArticle)
                            <a href="{{ route('news.visit', $tickerArticle) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-3 text-sm font-medium text-slate-200 transition-colors hover:text-white" aria-hidden="true" tabindex="-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                <span>{{ $tickerArticle->title }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Header -->
    <header class="sticky {{ isset($tickerArticles) && $tickerArticles->isNotEmpty() ? 'top-11' : 'top-0' }} z-50 backdrop-blur-md bg-white/90 border-b border-slate-200/80 transition-all duration-300 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <nav class="flex items-center gap-2 overflow-x-auto whitespace-nowrap pr-2">
                    <a href="{{ route('news.index') }}"
                       class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition-colors hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                        Home
                    </a>
                    <a href="{{ route('news.index') }}#fixtures"
                       class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition-colors hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                        Fixtures
                    </a>
                    <a href="{{ route('news.index') }}#live-score"
                       class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition-colors hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                        Live
                    </a>
                </nav>

                <div class="flex items-center space-x-4 shrink-0">
                    @if(session('admin_authenticated'))
                        <a href="{{ route('admin.dashboard') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all duration-200">
                            Dashboard
                        </a>
                        <a href="{{ route('admin.analytics') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-700 border border-amber-500/20 hover:bg-amber-500/20 transition-all duration-200">
                            Analytics
                        </a>
                        <a href="{{ route('admin.destroy') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-700 border border-rose-500/20 hover:bg-rose-500/20 transition-all duration-200">
                            Destroy
                        </a>
                        <a href="{{ route('admin.logout') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">
                            Logout
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-gradient-to-b from-white to-slate-50 py-5 shadow-inner">
        <div class="max-w-7xl mx-auto px-4">
            @if(isset($visitStats) || isset($fetchStats))
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 shadow-sm">
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
    </footer>


    @yield('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const countdownNodes = document.querySelectorAll('.js-fetch-countdown');

            if (countdownNodes.length === 0) {
                return;
            }

            const updateCountdown = () => {
                countdownNodes.forEach((node) => {
                    const nextFetch = node.dataset.nextFetch;

                    if (!nextFetch) {
                        node.textContent = 'unknown';
                        return;
                    }

                    const diffMs = new Date(nextFetch).getTime() - Date.now();

                    if (Number.isNaN(diffMs)) {
                        node.textContent = 'unknown';
                        return;
                    }

                    if (diffMs <= 0) {
                        node.textContent = 'less than a minute';
                        return;
                    }

                    const totalSeconds = Math.floor(diffMs / 1000);
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = totalSeconds % 60;
                    node.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                });
            };

            updateCountdown();
            window.setInterval(updateCountdown, 1000);
        });
    </script>

    <style>
        .ticker-mask::before,
        .ticker-mask::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            width: 3rem;
            pointer-events: none;
            z-index: 1;
        }

        .ticker-mask::before {
            left: 0;
            background: linear-gradient(90deg, rgba(2, 6, 23, 1) 0%, rgba(2, 6, 23, 0) 100%);
        }

        .ticker-mask::after {
            right: 0;
            background: linear-gradient(270deg, rgba(2, 6, 23, 1) 0%, rgba(2, 6, 23, 0) 100%);
        }

        .ticker-track {
            width: max-content;
            animation: ticker-scroll 44s linear infinite;
        }

        .ticker-track:hover {
            animation-play-state: paused;
        }

        @keyframes ticker-scroll {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-50%);
            }
        }
    </style>
</body>
</html>
