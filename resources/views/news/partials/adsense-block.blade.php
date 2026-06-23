@props([
    'client',
    'slot' => null,
    'label' => 'Advertisement',
    'variant' => 'default',
])

@if($client && $slot)
    @php
        $wrapperClasses = match ($variant) {
            'hero' => 'rounded-[1.8rem] border border-slate-200 bg-white px-4 py-4 shadow-sm',
            'subtle' => 'rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3',
            default => 'rounded-2xl border border-slate-200 bg-white px-3 py-3 shadow-sm',
        };
    @endphp

    <section class="{{ $wrapperClasses }}" aria-label="{{ $label }}">
        <div class="mb-3 flex items-center gap-2">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $label }}</span>
            <span class="h-px flex-1 bg-slate-200"></span>
        </div>
        <div class="min-h-[110px]">
            <ins class="adsbygoogle block"
                 style="display:block"
                 data-ad-client="{{ $client }}"
                 data-ad-slot="{{ $slot }}"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
        </div>
    </section>
@endif
