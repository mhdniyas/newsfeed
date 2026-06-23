@extends('layouts.app')

@section('title', $feedMeta['title'] . ' - World Cup News Explorer')

@section('content')
@php
    $desktopPromoLeft = $homepagePromo['desktop']['left'] ?? null;
    $desktopPromoRight = $homepagePromo['desktop']['right'] ?? null;
    $mobilePromos = $homepagePromo['mobile'] ?? [];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if($adsense['client'] && $adsense['tab_slot'])
        <div class="mb-8">
            @include('news.partials.adsense-block', [
                'client' => $adsense['client'],
                'slot' => $adsense['tab_slot'],
                'label' => 'Advertisement',
                'variant' => 'hero',
            ])
        </div>
    @endif

    @if(!empty($mobilePromos))
        <div class="mb-8 space-y-4 xl:hidden">
            @foreach($mobilePromos as $mobilePromo)
                @include('news.partials.promo-card', ['card' => $mobilePromo, 'variant' => 'mobile'])
            @endforeach
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[260px_minmax(0,1fr)_260px]">
        <aside class="hidden xl:block">
            <div class="sticky top-32 space-y-4">
                @if(!empty($desktopPromoLeft['enabled']))
                    @include('news.partials.promo-card', ['card' => $desktopPromoLeft, 'variant' => 'sidebar'])
                @endif
            </div>
        </aside>

        <div class="min-w-0">
            <section class="overflow-hidden rounded-[2.2rem] border p-5 sm:p-7 shadow-sm {{ $feedMeta['accent_classes'] }}">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] {{ $feedMeta['stat_text_classes'] }}">{{ $feedMeta['eyebrow'] }}</p>
                        <h1 class="mt-2 text-3xl sm:text-4xl font-black tracking-tight {{ $feedMeta['hero_text_classes'] }}">{{ $feedMeta['title'] }}</h1>
                        <p class="mt-3 max-w-2xl text-sm sm:text-base leading-7 {{ $feedMeta['hero_copy_classes'] }}">{{ $feedMeta['description'] }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:min-w-[340px]">
                        <div class="rounded-[1.6rem] border px-5 py-4 shadow-sm {{ $feedMeta['stat_classes'] }}">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] {{ $feedMeta['stat_text_classes'] }}">{{ $feedMeta['stat_label'] }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($feedMeta['stat_value'] ?? $articles->total()) }}</p>
                        </div>
                        <a href="{{ route('news.index') }}" class="rounded-[1.6rem] border border-slate-200 bg-white/90 px-5 py-4 shadow-sm transition hover:border-emerald-200 hover:bg-white">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Back To News</p>
                            <p class="mt-2 text-base font-black text-slate-950">Open Home Feed</p>
                            <p class="mt-1 text-xs text-slate-500">Return to the main news homepage and section overview.</p>
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-8">
                @if($articles->isEmpty())
                    <div class="rounded-[2rem] border border-dashed border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                        <h2 class="text-xl font-black text-slate-900">{{ $feedMeta['empty_title'] }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $feedMeta['empty_description'] }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @include('news.partials.cards')
                    </div>

                    @if($articles->hasPages())
                        <div class="mt-8">
                            {{ $articles->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>

        <aside class="hidden xl:block">
            <div class="sticky top-32 space-y-4">
                @if(!empty($desktopPromoRight['enabled']))
                    @include('news.partials.promo-card', ['card' => $desktopPromoRight, 'variant' => 'sidebar'])
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection
