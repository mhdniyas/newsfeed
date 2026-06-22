@extends('layouts.app')

@section('title', 'Promotions - World Cup News Explorer')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-emerald-500">Admin Promotions</p>
            <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Homepage Promotion Links</h1>
            <p class="mt-2 text-sm text-slate-500">Manage the referral links used by the sponsored card between homepage news sections.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-colors shadow-sm">
                Dashboard
            </a>
            <a href="{{ route('admin.analytics') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 text-xs font-bold transition-colors shadow-sm">
                Analytics
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <form action="{{ route('admin.promotions.update') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Quotex Referral URL</label>
                <input type="text" name="quotex_url" value="{{ old('quotex_url', $promotions['quotex_url']) }}" placeholder="broker-qx.pro/sign-up/?lid=1748398" spellcheck="false" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-3 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all">
                @error('quotex_url')
                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Premium Signals URL</label>
                <input type="text" name="signals_url" value="{{ old('signals_url', $promotions['signals_url']) }}" placeholder="broker-qx.pro/sign-up/?lid=1748398" spellcheck="false" class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-3 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all">
                @error('signals_url')
                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">WhatsApp Promotion Message</label>
                <textarea name="whatsapp_message" rows="4" placeholder="I want premium trading signals from Newsfeed." class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl px-3.5 py-3 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all">{{ old('whatsapp_message', $promotions['whatsapp_message']) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">This message opens first when visitors tap the WhatsApp contact button.</p>
                @error('whatsapp_message')
                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                You can paste referral links with or without `https://`. The form will automatically save them as full URLs, and WhatsApp opens with your saved promo message.
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-emerald-500">
                Save Promotion Links
            </button>
        </form>
    </div>
</div>
@endsection
