@extends('layouts.app')

@section('title', 'Admin Gold Rates Management - World Cup News Explorer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">Admin Dashboard</p>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Gold Rates Control Panel</h1>
            <p class="mt-2 text-sm text-slate-500">Trigger crawl updates, review price spikes flagged by safety checks, and inspect complete price logs.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.analytics') }}?tab=gold" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition shadow-sm">
                View Traffic Analytics
            </a>
            
            <form action="{{ route('admin.gold-rates.sync') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-950 text-white hover:bg-slate-800 text-xs font-bold transition shadow-md">
                    Trigger Manual Sync
                </button>
            </form>
        </div>
    </div>

    {{-- Success/Error Banners --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Database Records</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($goldAnalyticsSummary['total_records']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Saved daily closing and historical rates.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Flagged For Review</p>
            <p class="mt-2 text-3xl font-black {{ $goldAnalyticsSummary['pending_review_count'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                {{ number_format($goldAnalyticsSummary['pending_review_count']) }}
            </p>
            <p class="text-xs text-slate-500 mt-1">Requires manual approval before showing to visitors.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Scheduled Updates</p>
            <p class="mt-2 text-sm font-bold text-slate-800">4 Times Daily (IST)</p>
            <p class="text-xs text-slate-500 mt-1.5">Runs at 10:30 AM, 12:30 PM, 4:30 PM, and 8:30 PM.</p>
        </div>
    </div>

    {{-- Pending Approval Panel --}}
    @if($pendingRates->isNotEmpty())
        <section class="mb-8 rounded-3xl border border-amber-200 bg-amber-50/30 overflow-hidden shadow-sm">
            <div class="border-b border-amber-200 bg-amber-50/60 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-amber-900">Safety Flag: Price Spikes Awaiting Review</h2>
                    <p class="text-xs text-amber-700/80 mt-0.5">Rates changing more than 5% compared to yesterday. Flagged entries are hidden from public pages.</p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800 border border-amber-200/50">
                    {{ $pendingRates->count() }} flagged
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-amber-100 text-sm">
                    <thead class="bg-amber-50/20 text-left text-xs font-bold uppercase tracking-wider text-amber-800">
                        <tr>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5">Region</th>
                            <th class="px-6 py-3.5">Purity</th>
                            <th class="px-6 py-3.5 text-right">Price per 10g</th>
                            <th class="px-6 py-3.5 text-right">Change Amount</th>
                            <th class="px-6 py-3.5 text-right">Change %</th>
                            <th class="px-6 py-3.5">Source</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100/50 font-semibold text-slate-800">
                        @foreach($pendingRates as $rate)
                            <tr class="hover:bg-amber-50/10">
                                <td class="px-6 py-4">{{ $rate->rate_date->format('d M Y') }}</td>
                                <td class="px-6 py-4">{{ $rate->city }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-black border border-amber-200/50">
                                        {{ $rate->purity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-black">&#x20b9;{{ number_format($rate->price_10g) }}</td>
                                <td class="px-6 py-4 text-right {{ $rate->change_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ $rate->change_amount > 0 ? '+' : '' }}{{ number_format($rate->change_amount) }}
                                </td>
                                <td class="px-6 py-4 text-right {{ $rate->change_percent > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ $rate->change_percent > 0 ? '+' : '' }}{{ number_format($rate->change_percent, 2) }}%
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ $rate->source_url }}" target="_blank" class="text-xs text-amber-700 underline hover:text-amber-900">
                                        {{ $rate->source }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <form action="{{ route('admin.gold-rates.approve', $rate->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-3.5 py-1.5 text-xs font-bold text-white transition hover:bg-slate-800 shadow-sm">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.gold-rates.reject', $rate->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3.5 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-50 shadow-sm">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Main Logs Table --}}
    <section class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
            <h2 class="text-base font-bold text-slate-900">Synchronized Rates Log</h2>
            <p class="text-xs text-slate-500 mt-0.5">Chronological database list of gold rate entries.</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3.5">Date</th>
                        <th class="px-6 py-3.5">Region</th>
                        <th class="px-6 py-3.5">Purity</th>
                        <th class="px-6 py-3.5 text-right">Price per 1g</th>
                        <th class="px-6 py-3.5 text-right">Price per 10g</th>
                        <th class="px-6 py-3.5 text-right">Daily Change</th>
                        <th class="px-6 py-3.5 text-right">Change %</th>
                        <th class="px-6 py-3.5">Source</th>
                        <th class="px-6 py-3.5 text-right">Review Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @forelse($allRates as $rate)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 text-slate-900 font-bold">{{ $rate->rate_date->format('d M Y') }}</td>
                            <td class="px-6 py-4">{{ $rate->city }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $rate->purity === '24K' ? 'bg-amber-50 text-amber-700 border-amber-100' : ($rate->purity === '22K' ? 'bg-yellow-50 text-yellow-800 border-yellow-100' : 'bg-orange-50 text-orange-800 border-orange-100') }}">
                                    {{ $rate->purity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-black">&#x20b9;{{ number_format($rate->price_1g) }}</td>
                            <td class="px-6 py-4 text-right font-black">&#x20b9;{{ number_format($rate->price_10g) }}</td>
                            <td class="px-6 py-4 text-right {{ $rate->change_amount > 0 ? 'text-rose-600' : ($rate->change_amount < 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                                @if($rate->change_amount > 0)
                                    +{{ number_format($rate->change_amount) }}
                                @elseif($rate->change_amount < 0)
                                    {{ number_format($rate->change_amount) }}
                                @else
                                    0
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right {{ $rate->change_percent > 0 ? 'text-rose-600' : ($rate->change_percent < 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                                @if($rate->change_percent)
                                    {{ $rate->change_percent > 0 ? '+' : '' }}{{ number_format($rate->change_percent, 2) }}%
                                @else
                                    0%
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold text-slate-500">{{ $rate->source }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($rate->is_pending_review)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[10px] font-bold text-amber-700">
                                        Pending Review
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                        Approved
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-400 font-medium">
                                No gold rates saved in database. Run the manual sync trigger above to retrieve them.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($allRates->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $allRates->links() }}
            </div>
        @endif
    </section>

</div>
@endsection
