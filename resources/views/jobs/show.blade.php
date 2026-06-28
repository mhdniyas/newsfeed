@extends('layouts.app')

@section('title', $job->title . ' at ' . ($job->company ?: 'Confidential') . ' – Job Details')
@section('meta_description', \Illuminate\Support\Str::limit($job->description, 155))

@section('styles')
<style>
.company-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 1.25rem;
    font-weight: 800;
    font-size: 1.4rem;
    color: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.apply-btn {
    background-color: #4f46e5;
    color: white !important;
    font-weight: 700;
    padding: 0.75rem 1.5rem;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}
.apply-btn:hover {
    background-color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
}
.job-detail-card {
    border-radius: 2rem;
    border: 1px solid #e2e8f0;
    background: #fff;
}
.related-job-card {
    display: block;
    border-radius: 1.25rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1.25rem;
    transition: all 0.2s;
    text-decoration: none;
}
.related-job-card:hover {
    border-color: #818cf8;
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.05);
    transform: translateY(-2px);
}
</style>
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Breadcrumb --}}
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1.5 md:space-x-2 text-xs font-semibold text-slate-500">
            <li class="inline-flex items-center">
                <a href="{{ route('news.index') }}" class="hover:text-slate-800 transition">Home</a>
            </li>
            <li class="flex items-center gap-1.5">
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('jobs.index') }}" class="hover:text-slate-800 transition">Jobs</a>
            </li>
            @if($job->is_remote)
                <li class="flex items-center gap-1.5">
                    <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ route('jobs.remote') }}" class="hover:text-slate-800 transition">Remote</a>
                </li>
            @endif
            <li class="flex items-center gap-1.5 text-slate-850 truncate max-w-[180px] sm:max-w-xs" aria-current="page">
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                <span>{{ $job->title }}</span>
            </li>
        </ol>
    </nav>

    {{-- ── Job Details Card ── --}}
    <div class="job-detail-card overflow-hidden shadow-sm bg-white">
        {{-- Job Top Header --}}
        <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/50">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                <div class="flex items-start gap-4">
                    @php
                        // Deterministic company colors
                        $colors = [
                            ['from-indigo-500 to-purple-600', 'from-emerald-400 to-teal-600', 'from-amber-400 to-orange-600', 'from-pink-500 to-rose-600', 'from-sky-400 to-blue-600'],
                            ['from-violet-500 to-fuchsia-600', 'from-cyan-400 to-sky-600', 'from-lime-400 to-emerald-600', 'from-yellow-400 to-amber-600', 'from-rose-500 to-pink-600']
                        ];
                        $charIndex = ord(substr($job->company, 0, 1)) ?: 0;
                        $grad = $colors[$charIndex % 2][$charIndex % 5];
                        $initials = strtoupper(substr($job->company ?: 'C', 0, 2));
                    @endphp
                    <div class="company-badge bg-gradient-to-br {{ $grad }} shrink-0">
                        {{ $initials }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-indigo-50 border border-indigo-100 px-2.5 py-0.5 text-[10px] font-bold text-indigo-700 uppercase tracking-wider">
                                {{ $job->category }}
                            </span>
                            @if($job->is_remote)
                                <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Remote Work
                                </span>
                            @endif
                        </div>
                        <h1 class="mt-2 text-xl sm:text-2xl font-black text-slate-900 leading-snug">
                            {{ $job->title }}
                        </h1>
                        <p class="text-base font-bold text-slate-700 mt-1">
                            {{ $job->company ?: 'Confidential' }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-500 font-semibold">
                            <span class="flex items-center gap-1">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $job->location ?: 'India' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.25 2.25 0 00-2.25-2.25H15"/></svg>
                                {{ $job->source_name }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Posted {{ $job->published_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0">
                    <a href="{{ route('jobs.apply', $job->slug) }}" target="_blank" rel="noopener noreferrer" class="apply-btn">
                        <span>Apply on Original Site</span>
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Job Description --}}
        <div class="p-6 sm:p-8 space-y-6">
            {{-- Quick Summary Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl border border-slate-100 bg-slate-50/50 text-center">
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Views</span>
                    <span class="block mt-1 text-lg font-black text-slate-800">{{ number_format($job->views_count) }}</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Applications Initiated</span>
                    <span class="block mt-1 text-lg font-black text-slate-800">{{ number_format($job->apply_clicks_count) }}</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Location Mode</span>
                    <span class="block mt-1 text-xs font-bold text-slate-800">{{ $job->is_remote ? 'Remote' : 'On-Site / Hybrid' }}</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Indexed At</span>
                    <span class="block mt-1 text-xs font-bold text-slate-800">{{ $job->created_at->format('M d, Y') }}</span>
                </div>
            </div>

            <div>
                <h3 class="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-3 mb-4">Job Description Summary</h3>
                <div class="text-slate-700 leading-relaxed space-y-4 text-base">
                    @foreach(explode("\n", $job->description) as $line)
                        @if(trim($line) !== '')
                            <p>{{ trim($line) }}</p>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="p-5 rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/40 text-center">
                <h4 class="text-sm font-bold text-indigo-900">Want to see full job details?</h4>
                <p class="text-xs text-indigo-700/80 mt-1 max-w-lg mx-auto">
                    This job listing is aggregated from official indexing channels. Click below to view full specifications, salary details, and submit your application directly on the hiring platform.
                </p>
                <div class="mt-4">
                    <a href="{{ route('jobs.apply', $job->slug) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md hover:bg-indigo-700 transition">
                        <span>Initiate Application</span>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Related Jobs Section ── --}}
    @if($relatedJobs->isNotEmpty())
        <section class="space-y-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Related Openings</p>
                <h2 class="mt-1 text-2xl font-extrabold text-slate-900">More jobs in {{ $job->category }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedJobs as $rJob)
                    @php
                        $charIndex = ord(substr($rJob->company, 0, 1)) ?: 0;
                        $grad = $colors[$charIndex % 2][$charIndex % 5];
                        $initials = strtoupper(substr($rJob->company ?: 'C', 0, 2));
                    @endphp
                    <a href="{{ route('jobs.show', $rJob->slug) }}" class="related-job-card flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br {{ $grad }} flex items-center justify-center text-white font-bold text-xs">
                                    {{ $initials }}
                                </div>
                                @if($rJob->is_remote)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[8px] font-bold text-emerald-700 uppercase tracking-wider">
                                        Remote
                                    </span>
                                @endif
                            </div>
                            <h4 class="mt-3 text-sm font-extrabold text-slate-900 line-clamp-2 leading-snug">
                                {{ $rJob->title }}
                            </h4>
                            <p class="text-xs font-semibold text-slate-600 mt-1 truncate">
                                {{ $rJob->company ?: 'Confidential' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-medium">
                            <span class="truncate max-w-[80px]">{{ $rJob->location ?: 'India' }}</span>
                            <span>{{ $rJob->published_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
