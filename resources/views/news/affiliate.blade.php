@extends('layouts.app')

@section('title', 'Affiliate Disclosure - Signalz Online')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Signalz Online</p>
        <h1 class="mt-1 text-3xl font-extrabold text-slate-950">Affiliate Disclosure</h1>
        <div class="mt-2 h-1 w-12 bg-emerald-500 rounded"></div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 p-6 sm:p-10 shadow-sm leading-relaxed text-slate-600 space-y-6">
        <p class="text-sm text-slate-400">Last updated: June 25, 2026</p>

        <p>
            In compliance with the FTC guidelines and advertising standards, this page is here to disclose that <strong>Signalz Online</strong> (<a href="http://signalz.online" class="text-emerald-600 font-medium hover:underline">http://signalz.online</a>) may contain affiliate links, sponsored contents, and promotional integrations.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-2">What is an Affiliate Link?</h2>
        <p>
            An affiliate link is a specific URL that contains a tracking code. When you click on one of these links and make a purchase or sign up for a service, Signalz Online receives a small commission from the merchant or platform at no extra cost to you.
        </p>
        <p>
            These commissions help support the maintenance of our automated news indexing servers, live scoreboard caching systems, and server infrastructure, allowing us to keep this sports resource free for all visitors.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">Promotions & Signalz Features</h2>
        <p>
            We may feature widgets, links, or banners promoting premium trading signals, sportsbooks, or other partner services (e.g. promotional WhatsApp links). We only promote products, services, or platforms that we believe add value to our audience. However, any participation, deposit, or registration you undertake is strictly your own responsibility, and we encourage you to perform your own research.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">AdSense and Advertisements</h2>
        <p>
            Beside affiliate relationships, Signalz Online displays advertisements served through networks like Google AdSense. These ads are labeled accordingly and are selected programmatically.
        </p>

        <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">&copy; Signalz Online</span>
            <a href="{{ route('news.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-600 hover:text-emerald-700 transition">
                Back to Home &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
