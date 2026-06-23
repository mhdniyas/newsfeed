@php
    $variant = $variant ?? 'sidebar';
    $card = $card ?? [];
    $isInteractive = !empty($card['primary_url']) || !empty($card['secondary_url']);

    $shellClasses = match ($variant) {
        'hero' => 'overflow-hidden rounded-[2.25rem] border border-emerald-300/40 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.34),_transparent_34%),linear-gradient(135deg,_#020617_0%,_#0f172a_48%,_#052e2b_100%)] p-6 sm:p-7 text-white shadow-[0_24px_60px_rgba(2,6,23,0.18)]',
        'mobile' => 'overflow-hidden rounded-[1.8rem] border border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] p-4 shadow-sm',
        'preview' => 'overflow-hidden rounded-[1.8rem] border border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] p-5 shadow-sm',
        default => 'overflow-hidden rounded-[1.8rem] border border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] p-5 shadow-sm',
    };

    $badgeClasses = $variant === 'hero'
        ? 'border border-emerald-300/30 bg-emerald-400/10 text-emerald-200'
        : 'border border-emerald-200 bg-emerald-50 text-emerald-700';

    $titleClasses = $variant === 'hero' ? 'text-white' : 'text-slate-950';
    $bodyClasses = $variant === 'hero' ? 'text-slate-200' : 'text-slate-600';
    $noteClasses = $variant === 'hero' ? 'text-slate-300' : 'text-slate-500';
    $primaryClasses = $variant === 'hero'
        ? 'bg-emerald-400 text-slate-950 hover:bg-emerald-300'
        : 'bg-slate-950 text-white hover:bg-slate-800';
    $secondaryClasses = $variant === 'hero'
        ? 'border border-white/20 bg-white/10 text-white hover:bg-white/15'
        : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50';
@endphp

<section class="{{ $shellClasses }}">
    <div class="flex flex-col gap-4 {{ $variant === 'hero' ? 'lg:flex-row lg:items-center lg:justify-between' : '' }}">
        <div class="{{ $variant === 'hero' ? 'max-w-3xl' : '' }}">
            @if(!empty($card['badge']))
                <p class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] {{ $badgeClasses }}">
                    {{ $card['badge'] }}
                </p>
            @endif

            @if(!empty($card['title']))
                <h2 class="mt-3 {{ $variant === 'hero' ? 'text-3xl sm:text-4xl' : 'text-xl' }} font-black tracking-tight {{ $titleClasses }}">
                    {{ $card['title'] }}
                </h2>
            @endif

            @if(!empty($card['body']))
                <p class="mt-3 text-sm leading-6 {{ $bodyClasses }}">
                    {{ $card['body'] }}
                </p>
            @endif
        </div>

        <div class="flex flex-col gap-3 {{ $variant === 'hero' ? 'sm:min-w-[260px]' : '' }}">
            @if(!empty($card['primary_label']))
                @if(!empty($card['primary_url']))
                    <a href="{{ $card['primary_url'] }}" target="_blank" rel="noopener noreferrer sponsored" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-black transition {{ $primaryClasses }}">
                        {{ $card['primary_label'] }}
                    </a>
                @else
                    <div class="inline-flex items-center justify-center rounded-2xl border border-dashed border-slate-300 px-5 py-3 text-sm font-bold text-slate-500">
                        {{ $card['primary_label'] }}
                    </div>
                @endif
            @endif

            @if(!empty($card['secondary_label']))
                @if(!empty($card['secondary_url']))
                    <a href="{{ $card['secondary_url'] }}" target="_blank" rel="noopener noreferrer sponsored" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-bold transition {{ $secondaryClasses }}">
                        {{ $card['secondary_label'] }}
                    </a>
                @else
                    <div class="inline-flex items-center justify-center rounded-2xl border border-dashed border-slate-300 px-5 py-3 text-sm font-semibold text-slate-500">
                        {{ $card['secondary_label'] }}
                    </div>
                @endif
            @endif

            @if(!$isInteractive && empty($card['primary_label']) && empty($card['secondary_label']))
                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-3 text-xs font-semibold text-slate-500">
                    Add CTA labels and URLs in admin to activate this slot.
                </div>
            @endif
        </div>
    </div>

    @if(!empty($card['note']))
        <p class="mt-4 text-xs {{ $noteClasses }}">{{ $card['note'] }}</p>
    @endif
</section>
