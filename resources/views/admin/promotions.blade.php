@extends('layouts.app')

@section('title', 'Promotions - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-8">
        <div class="max-w-3xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-emerald-500">Admin Promotions</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900">Ad Creation Hub</h1>
            <p class="mt-2 text-sm text-slate-500">Build the revenue placements that appear across the homepage and curated feeds. Keep the current hero card, fill both desktop rails, and stack compact mobile ads professionally.</p>
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

    <div class="grid gap-8 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Desktop Revenue</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">2 rail slots</p>
                    <p class="mt-2 text-sm text-slate-500">Left and right cards stay visible during long reads on large screens.</p>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700/80">Homepage Hero</p>
                    <p class="mt-2 text-2xl font-black text-emerald-950">1 anchor slot</p>
                    <p class="mt-2 text-sm text-emerald-800/80">Existing sponsored placement remains between homepage sections.</p>
                </div>
                <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700/80">Mobile Revenue</p>
                    <p class="mt-2 text-2xl font-black text-sky-950">2 stacked cards</p>
                    <p class="mt-2 text-sm text-sky-800/80">Compact Google Ads-style cards appear before feed content on phones.</p>
                </div>
            </div>

            <form action="{{ route('admin.promotions.update') }}" method="POST" class="space-y-6">
                @csrf

                @foreach($promotions['cards'] as $key => $card)
                    @php($label = $promotions['labels'][$key] ?? ucfirst(str_replace('_', ' ', $key)))
                    <section class="rounded-[2rem] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">{{ strtoupper(str_replace('_', ' ', $key)) }}</p>
                                <h2 class="mt-1 text-xl font-extrabold text-slate-950">{{ $label }}</h2>
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="cards[{{ $key }}][enabled]" value="1" {{ old("cards.$key.enabled", $card['enabled']) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500">
                                Slot enabled
                            </label>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Badge</label>
                                <input type="text" name="cards[{{ $key }}][badge]" value="{{ old("cards.$key.badge", $card['badge']) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500 focus:bg-white">
                                @error("cards.$key.badge")
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Note</label>
                                <input type="text" name="cards[{{ $key }}][note]" value="{{ old("cards.$key.note", $card['note']) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500 focus:bg-white">
                                @error("cards.$key.note")
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Headline</label>
                            <input type="text" name="cards[{{ $key }}][title]" value="{{ old("cards.$key.title", $card['title']) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500 focus:bg-white">
                            @error("cards.$key.title")
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase tracking-wider">Body Copy</label>
                            <textarea name="cards[{{ $key }}][body]" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500 focus:bg-white">{{ old("cards.$key.body", $card['body']) }}</textarea>
                            @error("cards.$key.body")
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Primary CTA</p>
                                <div class="mt-3 space-y-3">
                                    <input type="text" name="cards[{{ $key }}][primary_label]" value="{{ old("cards.$key.primary_label", $card['primary_label']) }}" placeholder="Button label" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500">
                                    <input type="text" name="cards[{{ $key }}][primary_url]" value="{{ old("cards.$key.primary_url", $card['primary_url']) }}" placeholder="https://example.com" spellcheck="false" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500">
                                </div>
                                @error("cards.$key.primary_label")
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                                @error("cards.$key.primary_url")
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Secondary CTA</p>
                                <div class="mt-3 space-y-3">
                                    <input type="text" name="cards[{{ $key }}][secondary_label]" value="{{ old("cards.$key.secondary_label", $card['secondary_label']) }}" placeholder="Button label" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500">
                                    <input type="text" name="cards[{{ $key }}][secondary_url]" value="{{ old("cards.$key.secondary_url", $card['secondary_url']) }}" placeholder="https://example.com" spellcheck="false" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500">
                                </div>
                                @error("cards.$key.secondary_label")
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                                @error("cards.$key.secondary_url")
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>
                @endforeach

                <section class="rounded-[2rem] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                    <h2 class="text-xl font-extrabold text-slate-950">WhatsApp opener</h2>
                    <p class="mt-1 text-sm text-slate-500">Used by the floating WhatsApp contact button across the site.</p>
                    <div class="mt-4">
                        <textarea name="whatsapp_message" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 outline-none transition-all focus:border-emerald-500 focus:bg-white">{{ old('whatsapp_message', $promotions['whatsapp_message']) }}</textarea>
                        @error('whatsapp_message')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                    URLs can be pasted with or without `https://`. The hero primary and secondary buttons still sync to the legacy homepage promotion keys for compatibility with the existing site logic.
                </div>

                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-emerald-500">
                    Save Promotion Hub
                </button>
            </form>
        </div>

        <aside class="space-y-6">
            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Site Preview</p>
                <h2 class="mt-2 text-2xl font-extrabold text-slate-950">How sponsors appear on the site</h2>
                <p class="mt-2 text-sm text-slate-500">This uses the same card component as the public pages so sales previews match the live placements.</p>
            </section>

            @if($previewPromo['hero']['enabled'])
                @include('news.partials.promo-card', ['card' => $previewPromo['hero'], 'variant' => 'hero'])
            @endif

            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-1">
                @if($previewPromo['desktop']['left']['enabled'])
                    @include('news.partials.promo-card', ['card' => $previewPromo['desktop']['left'], 'variant' => 'preview'])
                @endif
                @if($previewPromo['desktop']['right']['enabled'])
                    @include('news.partials.promo-card', ['card' => $previewPromo['desktop']['right'], 'variant' => 'preview'])
                @endif
            </div>

            @if(!empty($previewPromo['mobile']))
                <section class="rounded-[2rem] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Mobile Stack</p>
                    <div class="mt-4 space-y-4">
                        @foreach($previewPromo['mobile'] as $mobileCard)
                            @include('news.partials.promo-card', ['card' => $mobileCard, 'variant' => 'mobile'])
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
