<div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between gap-3 mb-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Fixtures</p>
            <h2 class="text-xl font-extrabold text-slate-900">Upcoming games in your local time</h2>
            <p class="mt-1 text-xs text-slate-500">
                Source:
                <a href="{{ $scoreboard['source_url'] }}" target="_blank" rel="noopener noreferrer" class="font-bold text-emerald-600 hover:text-emerald-700">FIFA scores & fixtures</a>
                @if(!empty($scoreboard['synced_at']))
                    <span class="ml-2">Updated {{ $scoreboard['synced_at']->diffForHumans() }}</span>
                @endif
            </p>
        </div>
        <button type="button" data-scoreboard-refresh="fixtures" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
            Refresh
        </button>
    </div>

    <div class="space-y-3">
        @forelse($scoreboard['upcoming'] as $match)
            <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 transition-colors hover:border-amber-200 hover:bg-amber-50/40">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-900 truncate">{{ $match['home_team'] }} vs {{ $match['away_team'] }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $match['group'] }} · {{ $match['stadium'] }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-extrabold text-amber-500 js-local-kickoff" data-kickoff="{{ optional($match['kickoff_at'])->toIso8601String() }}">{{ $match['kickoff'] ?? 'TBD' }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $match['stage'] }}</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 text-xs">
                    <span class="text-slate-400">{{ $match['city'] }}</span>
                    <a href="{{ $match['match_url'] }}" target="_blank" rel="noopener noreferrer" class="font-bold text-emerald-600 hover:text-emerald-700">Match Centre</a>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                <p>{{ $scoreboard['message'] ?? 'Fixture data is only shown when FIFA.com schedule data is available.' }}</p>
                <a href="{{ $scoreboard['source_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex font-bold text-emerald-600 hover:text-emerald-700">Open FIFA scores & fixtures</a>
            </div>
        @endforelse
    </div>
</div>
