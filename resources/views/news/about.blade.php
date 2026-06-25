@extends('layouts.app')

@section('title', 'About Us - Signalz Online')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Signalz Online</p>
        <h1 class="mt-1 text-3xl font-extrabold text-slate-950">About Us</h1>
        <div class="mt-2 h-1 w-12 bg-emerald-500 rounded"></div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 p-6 sm:p-10 shadow-sm leading-relaxed text-slate-600 space-y-6">
        <p class="text-lg text-slate-900 font-medium">
            Welcome to <strong class="text-slate-950">Signalz Online</strong>, the ultimate real-time news dashboard designed for football enthusiasts, general sports fans, and digital audiences worldwide.
        </p>
        
        <p>
            Our core mission is to bridge the gap between fast-paced global developments and your screen. By indexing the most trusted news sources, parsing live tournament schedules, and delivering up-to-the-minute updates on the FIFA World Cup 2026, we ensure you never miss a kickoff, a transfer rumor, or a record-breaking performance.
        </p>

        <div class="grid gap-6 md:grid-cols-2 mt-8">
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-6">
                <h3 class="text-base font-bold text-slate-900 mb-2">Automated Curation</h3>
                <p class="text-sm">
                    Leveraging our state-of-the-art sync services, Signalz Online processes and summarizes trending sports articles every two minutes. This ensures an updated, spam-free reading feed optimized for mobile.
                </p>
            </div>
            
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-6">
                <h3 class="text-base font-bold text-slate-900 mb-2">Clean User Interface</h3>
                <p class="text-sm">
                    We value clarity and speed. Our interface is tailored to prevent visual clutter, providing a premium reading environment with quick search tools and customized topic filters.
                </p>
            </div>
        </div>

        <h2 class="text-xl font-bold text-slate-900 pt-4">Our Vision</h2>
        <p>
            We believe that staying updated should be effortless and beautifully integrated. From live score updates to curated analysis of top-performing teams, Signalz Online is built to keep you ahead of the game.
        </p>

        <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">Published: June 2026</span>
            <a href="{{ route('news.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-600 hover:text-emerald-700 transition">
                Explore Latest News &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
