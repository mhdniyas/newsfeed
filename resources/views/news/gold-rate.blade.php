@extends('layouts.app')

@section('title', 'Gold Rate Today in ' . $cityMeta['title'] . ' - 24K, 22K & 18K Gold Prices')
@section('meta_description', 'Check daily live gold prices in ' . $cityName . ' per 1 gram, 8 grams, and 10 grams. View historical price charts, purity comparison (24K, 22K, 18K), and city-wise trends.')

@php
    // Prepare SVG chart coordinate helpers
    $chartData = $history->reverse(); // oldest first
    $points24k = [];
    $points22k = [];
    $index = 0;
    $maxVal = 0;
    $minVal = 999999;
    
    foreach ($chartData as $dateStr => $rates) {
        $r24 = $rates->firstWhere('purity', '24K');
        $r22 = $rates->firstWhere('purity', '22K');
        
        $p24 = $r24 ? (float)$r24->price_1g : 0;
        $p22 = $r22 ? (float)$r22->price_1g : 0;
        
        if ($p24 > 0) {
            $points24k[] = ['x' => $index, 'y' => $p24, 'date' => $dateStr];
            if ($p24 > $maxVal) $maxVal = $p24;
            if ($p24 < $minVal) $minVal = $p24;
        }
        if ($p22 > 0) {
            $points22k[] = ['x' => $index, 'y' => $p22, 'date' => $dateStr];
            if ($p22 > $maxVal) $maxVal = $p22;
            if ($p22 < $minVal) $minVal = $p22;
        }
        $index++;
    }

    if ($maxVal == $minVal) {
        $maxVal += 100;
        $minVal -= 100;
    }
    
    $svgWidth = 700;
    $svgHeight = 160;
    $padding = 25;
    
    $getCoords = function($points) use ($maxVal, $minVal, $svgWidth, $svgHeight, $padding) {
        if (count($points) === 0) return [];
        $dx = count($points) > 1 ? ($svgWidth - 2 * $padding) / (count($points) - 1) : 0;
        $dy = ($svgHeight - 2 * $padding) / ($maxVal - $minVal);
        
        $coords = [];
        foreach ($points as $i => $pt) {
            $coords[] = [
                'x' => $padding + $i * $dx,
                'y' => $svgHeight - $padding - ($pt['y'] - $minVal) * $dy,
                'price' => $pt['y'],
                'date' => Carbon::parse($pt['date'])->format('d M')
            ];
        }
        return $coords;
    };
    
    $coords24k = $getCoords($points24k);
    $coords22k = $getCoords($points22k);
    
    $getPath = function($coords) {
        if (count($coords) < 2) return '';
        $path = '';
        foreach ($coords as $i => $c) {
            if ($i === 0) {
                $path .= "M {$c['x']} {$c['y']}";
            } else {
                $path .= " L {$c['x']} {$c['y']}";
            }
        }
        return $path;
    };

    $getAreaPath = function($coords, $svgHeight, $padding) {
        if (count($coords) < 2) return '';
        $path = '';
        foreach ($coords as $i => $c) {
            if ($i === 0) {
                $path .= "M {$c['x']} " . ($svgHeight - $padding) . " L {$c['x']} {$c['y']}";
            } else {
                $path .= " L {$c['x']} {$c['y']}";
            }
        }
        $last = end($coords);
        $path .= " L {$last['x']} " . ($svgHeight - $padding) . " Z";
        return $path;
    };
    
    $path24k = $getPath($coords24k);
    $path22k = $getPath($coords22k);
    $area24k = $getAreaPath($coords24k, $svgHeight, $padding);
    $area22k = $getAreaPath($coords22k, $svgHeight, $padding);
@endphp

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumbs & Header --}}
        <div class="mb-8">
            <nav class="flex text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3" aria-label="Breadcrumb">
                <a href="{{ route('news.index') }}" class="hover:text-slate-600">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('news.gold-rate', 'india') }}" class="hover:text-slate-600">Gold Rates</a>
                <span class="mx-2">/</span>
                <span class="text-slate-600">{{ $cityName }}</span>
            </nav>
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Gold Rate Today in <span class="bg-gradient-to-r from-amber-500 to-yellow-600 bg-clip-text text-transparent">{{ $cityName }}</span>
                    </h1>
                    <p class="mt-2 text-slate-500 text-sm max-w-3xl">
                        Live 24 Karat, 22 Karat, and 18 Karat gold prices in {{ $cityName }}. Prices are calculated per gram, 8 grams (1 Sovereign) and 10 grams.
                    </p>
                </div>
                <div class="flex items-center gap-3 bg-white px-4 py-2.5 rounded-2xl border border-slate-200/80 shadow-sm shrink-0 self-start md:self-auto">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <div class="text-xs">
                        <span class="font-bold text-slate-700">Source:</span> 
                        <span class="text-slate-500 font-semibold">{{ $source }} Rate</span>
                        @if($lastUpdated)
                            <span class="mx-1.5 text-slate-300">|</span>
                            <span class="text-slate-400 font-medium">As of {{ $lastUpdated->format('d M Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="mb-8 bg-white/70 backdrop-blur border border-slate-200/80 p-1.5 rounded-2xl flex flex-wrap gap-1 shadow-sm max-w-3xl">
            @foreach($supportedCities as $slug => $meta)
                <a href="{{ route('news.gold-rate', $slug) }}" 
                   class="flex-1 min-w-[100px] text-center px-4 py-3 text-xs font-bold rounded-xl transition duration-200 {{ $citySlug === $slug ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-white' }}">
                    {{ $meta['name'] }}
                </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Main Column (Today's rates & details) --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- Today's Price Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach(['24K' => 'Pure 99.9% Gold', '22K' => 'Standard 91.6% Jewellery Gold', '18K' => 'Decorative 75.0% Gold'] as $purity => $subtitle)
                        @php
                            $rate = $latestRates[$purity] ?? null;
                            $price1g = $rate ? (float)$rate->price_1g : null;
                            $changePercent = $rate ? (float)$rate->change_percent : 0;
                            $changeAmount = $rate ? (float)$rate->change_amount : 0;
                        @endphp
                        
                        <div class="group relative overflow-hidden bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md transition-all duration-300">
                            {{-- Decorative gold accent bar --}}
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r {{ $purity === '24K' ? 'from-amber-400 to-yellow-500' : ($purity === '22K' ? 'from-yellow-500 to-amber-600' : 'from-amber-600 to-orange-700') }}"></div>
                            
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-black {{ $purity === '24K' ? 'bg-amber-50 text-amber-700 border border-amber-100' : ($purity === '22K' ? 'bg-yellow-50 text-yellow-800 border border-yellow-100' : 'bg-orange-50 text-orange-800 border border-orange-100') }}">
                                        {{ $purity }} Gold
                                    </span>
                                    <p class="text-[11px] text-slate-400 font-semibold mt-1.5">{{ $subtitle }}</p>
                                </div>
                                
                                {{-- Change badge --}}
                                @if($rate && $changeAmount !== 0.0)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-extrabold {{ $changeAmount > 0 ? 'bg-rose-50 text-rose-700 border border-rose-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }}">
                                        {!! $changeAmount > 0 ? '&uarr;' : '&darr;' !!} {{ abs($changePercent) }}%
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-50 text-slate-400 border border-slate-100 px-2.5 py-1 text-xs font-bold">
                                        Stable
                                    </span>
                                @endif
                            </div>

                            <div class="mt-6">
                                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Price Per Gram</p>
                                <div class="flex items-baseline gap-1 mt-1">
                                    <span class="text-3xl font-black text-slate-900 tracking-tight">&#x20b9;{{ $price1g ? number_format($price1g) : 'N/A' }}</span>
                                    <span class="text-xs text-slate-500 font-medium">/ 1g</span>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">1 Sovereign (8g)</p>
                                    <p class="text-sm font-extrabold text-slate-800 mt-0.5">&#x20b9;{{ $price1g ? number_format($price1g * 8) : 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">10 Grams</p>
                                    <p class="text-sm font-extrabold text-slate-800 mt-0.5">&#x20b9;{{ $price1g ? number_format($price1g * 10) : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Interactive Charts Section --}}
                <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Historical Price Trends</h2>
                            <p class="text-xs text-slate-500 mt-1">Daily trend tracking for 24K vs 22K gold rates per gram.</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-bold">
                            <span class="flex items-center gap-1.5 text-amber-500">
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> 24K Gold
                            </span>
                            <span class="flex items-center gap-1.5 text-yellow-600">
                                <span class="h-2.5 w-2.5 rounded-full bg-yellow-600"></span> 22K Gold
                            </span>
                        </div>
                    </div>

                    @if(count($coords24k) > 1)
                        <div class="overflow-x-auto">
                            <div class="min-w-[650px] relative p-2">
                                <svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" class="w-full h-auto overflow-visible">
                                    <defs>
                                        <linearGradient id="grad24k" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.25"/>
                                            <stop offset="100%" stop-color="#fbbf24" stop-opacity="0.0"/>
                                        </linearGradient>
                                        <linearGradient id="grad22k" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#ca8a04" stop-opacity="0.2"/>
                                            <stop offset="100%" stop-color="#ca8a04" stop-opacity="0.0"/>
                                        </linearGradient>
                                    </defs>
                                    
                                    {{-- Horizontal Grid Lines --}}
                                    @for($i = 0; $i <= 4; $i++)
                                        @php
                                            $yVal = $padding + $i * ($svgHeight - 2 * $padding) / 4;
                                            $gridPrice = $maxVal - $i * ($maxVal - $minVal) / 4;
                                        @endphp
                                        <line x1="{{ $padding }}" y1="{{ $yVal }}" x2="{{ $svgWidth - $padding }}" y2="{{ $yVal }}" stroke="#f1f5f9" stroke-width="1.5" />
                                        <text x="{{ $padding - 5 }}" y="{{ $yVal + 3 }}" fill="#94a3b8" font-size="9" font-weight="bold" text-anchor="end">&#x20b9;{{ number_format($gridPrice) }}</text>
                                    @endfor

                                    {{-- Area paths for gradients --}}
                                    @if(!empty($area24k))
                                        <path d="{{ $area24k }}" fill="url(#grad24k)"/>
                                    @endif
                                    @if(!empty($area22k))
                                        <path d="{{ $area22k }}" fill="url(#grad22k)"/>
                                    @endif

                                    {{-- Stroke lines --}}
                                    <path d="{{ $path24k }}" fill="none" stroke="#fbbf24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="{{ $path22k }}" fill="none" stroke="#ca8a04" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="2,2" />

                                    {{-- Render points with interactive tooltips --}}
                                    @foreach($coords24k as $c)
                                        <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="4" fill="#ffffff" stroke="#fbbf24" stroke-width="2.5" />
                                    @endforeach
                                    @foreach($coords22k as $c)
                                        <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="3.5" fill="#ffffff" stroke="#ca8a04" stroke-width="2" />
                                    @endforeach

                                    {{-- Date axis labels --}}
                                    @foreach($coords24k as $c)
                                        <text x="{{ $c['x'] }}" y="{{ $svgHeight - 5 }}" fill="#64748b" font-size="9" font-weight="bold" text-anchor="middle">{{ $c['date'] }}</text>
                                    @endforeach
                                </svg>
                            </div>
                        </div>
                    @else
                        <div class="py-12 text-center text-sm text-slate-400 border border-dashed border-slate-200 rounded-2xl bg-slate-50">
                            Insufficient historical data points to render the chart.
                        </div>
                    @endif
                </div>

                {{-- Detailed Rate Tables --}}
                <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm overflow-hidden">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Gold Price History (Last 15 days)</h3>
                    
                    <div class="overflow-x-auto -mx-5 sm:-mx-6">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3.5">Date</th>
                                    <th class="px-5 py-3.5">24K Gold (1g)</th>
                                    <th class="px-5 py-3.5">22K Gold (1g)</th>
                                    <th class="px-5 py-3.5">18K Gold (1g)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($history as $dateStr => $rates)
                                    @php
                                        $r24 = $rates->firstWhere('purity', '24K');
                                        $r22 = $rates->firstWhere('purity', '22K');
                                        $r18 = $rates->firstWhere('purity', '18K');
                                        
                                        $p24 = $r24 ? (float)$r24->price_1g : null;
                                        $p22 = $r22 ? (float)$r22->price_1g : null;
                                        $p18 = $r18 ? (float)$r18->price_1g : null;
                                    @endphp
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-5 py-4 text-slate-900 font-bold">
                                            {{ Carbon::parse($dateStr)->format('d M Y') }}
                                        </td>
                                        <td class="px-5 py-4 text-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span>&#x20b9;{{ $p24 ? number_format($p24) : 'N/A' }}</span>
                                                @if($r24 && $r24->change_amount)
                                                    <span class="text-[10px] font-black {{ $r24->change_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                        ({{ $r24->change_amount > 0 ? '+' : '' }}{{ (int)$r24->change_amount }})
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span>&#x20b9;{{ $p22 ? number_format($p22) : 'N/A' }}</span>
                                                @if($r22 && $r22->change_amount)
                                                    <span class="text-[10px] font-black {{ $r22->change_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                        ({{ $r22->change_amount > 0 ? '+' : '' }}{{ (int)$r22->change_amount }})
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span>&#x20b9;{{ $p18 ? number_format($p18) : 'N/A' }}</span>
                                                @if($r18 && $r18->change_amount)
                                                    <span class="text-[10px] font-black {{ $r18->change_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                        ({{ $r18->change_amount > 0 ? '+' : '' }}{{ (int)$r18->change_amount }})
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-400">
                                            No gold rate data found in database.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Disclaimers and FAQ Section --}}
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-slate-900">Disclaimer & Gold Taxation in India</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        * The gold prices displayed above are compiled from benchmark market rates including the India Bullion and Jewellers Association (IBJA) and local retail patterns. These rates represent opening/closing standard reference rates and are updated multiple times a day.
                    </p>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        * **Goods and Services Tax (GST):** Please note that these rates exclude the **3% Goods and Services Tax (GST)** applicable on jewellery purchases. Making charges and local retail premiums will also be charged extra by your jeweller.
                    </p>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        * **Purity Guide:** 24 Karat represents pure gold (99.9% gold content), ideal for gold coins or bars. 22 Karat (91.6% gold content) is standard for making durable jewelry. 18 Karat (75.0% gold content) is commonly used for stone-studded jewelry.
                    </p>
                </div>

            </div>

            {{-- Sidebar Column (Comparison & Ads) --}}
            <div class="space-y-8">
                
                {{-- Major Cities Comparison List --}}
                <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Rates in Major Cities Today</h3>
                    
                    <div class="space-y-3.5">
                        @forelse($comparisons as $city => $data)
                            <a href="{{ route('news.gold-rate', $data['slug']) }}" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100 hover:border-amber-300 transition duration-200 group">
                                <div>
                                    <p class="text-xs font-bold text-slate-800 group-hover:text-amber-600 transition">{{ $city }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Today's Gold Rate</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black text-slate-900">&#x20b9;{{ $data['rate_24k'] ? number_format((float)$data['rate_24k'] * 10) : 'N/A' }} <span class="text-[9px] text-slate-400 font-bold">24K</span></p>
                                    <p class="text-xs font-extrabold text-slate-700 mt-0.5">&#x20b9;{{ $data['rate_22k'] ? number_format((float)$data['rate_22k'] * 10) : 'N/A' }} <span class="text-[9px] text-slate-400 font-bold">22K</span></p>
                                </div>
                            </a>
                        @empty
                            <p class="text-xs text-slate-400">No comparative data found.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Premium Ad Banner Placeholder --}}
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Advertisement</span>
                    </div>
                    @if(config('services.adsense.client'))
                        <div class="flex items-center justify-center min-h-[250px] bg-slate-50 rounded-2xl overflow-hidden">
                            <ins class="adsbygoogle"
                                 style="display:block"
                                 data-ad-client="{{ config('services.adsense.client') }}"
                                 data-ad-slot="{{ config('services.adsense.infeed_slot') }}"
                                 data-ad-format="auto"
                                 data-full-width-responsive="true"></ins>
                            <script>
                                 (adsbygoogle = window.adsbygoogle || []).push({});
                            </script>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center min-h-[250px] bg-slate-50 rounded-2xl border border-dashed border-slate-200 p-4 text-center">
                            <p class="text-xs font-bold text-slate-400">Premium Ad Slot</p>
                            <p class="text-[10px] text-slate-300 mt-1 max-w-[180px]">Configured via Google AdSense client properties.</p>
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
</div>
@endsection
