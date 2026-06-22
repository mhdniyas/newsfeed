@extends('layouts.app')

@section('title', 'Live Scores - FIFA 2026 News Explorer')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-end justify-between gap-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">World Cup 2026</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-950">Live Score</h1>
            <p class="mt-2 text-sm text-slate-500">Recent completed matches on a dedicated page, separated from the news landing page.</p>
        </div>
        <a href="{{ route('news.index') }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
            Back To News
        </a>
    </div>

    <div id="scores-panel-content">
        @include('news.partials.scores')
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const scoresPanelContent = document.getElementById('scores-panel-content');

        document.addEventListener('click', async (event) => {
            const refreshButton = event.target.closest('[data-scoreboard-refresh]');

            if (!refreshButton) {
                return;
            }

            event.preventDefault();

            const originalText = refreshButton.textContent.trim();
            refreshButton.disabled = true;
            refreshButton.textContent = 'Refreshing...';

            try {
                const response = await fetch(`{{ route('news.scoreboard.refresh') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Refresh failed');
                }

                const data = await response.json();

                if (scoresPanelContent) {
                    scoresPanelContent.innerHTML = data.scores_html;
                }
            } catch (error) {
                alert('Could not refresh live scores. Check whether Chrome/Chromium is installed on the VPS.');
            } finally {
                refreshButton.disabled = false;
                refreshButton.textContent = originalText;
            }
        });
    });
</script>
@endsection
