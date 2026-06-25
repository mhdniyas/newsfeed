@extends('layouts.app')

@section('title', 'Disclaimer - Signalz Online')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Signalz Online</p>
        <h1 class="mt-1 text-3xl font-extrabold text-slate-950">Disclaimer</h1>
        <div class="mt-2 h-1 w-12 bg-emerald-500 rounded"></div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 p-6 sm:p-10 shadow-sm leading-relaxed text-slate-600 space-y-6">
        <p class="text-sm text-slate-400">Last updated: June 25, 2026</p>

        <p>
            If you require any more information or have any questions about our site's disclaimer, please feel free to contact us by email at <a href="mailto:support@signalz.online" class="text-emerald-600 font-medium hover:underline">support@signalz.online</a>.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-2">Disclaimers for Signalz Online</h2>
        <p>
            All the information on this website - <a href="http://signalz.online" class="text-emerald-600 font-medium hover:underline">http://signalz.online</a> - is published in good faith and for general information purpose only. Signalz Online does not make any warranties about the completeness, reliability, and accuracy of this information. Any action you take upon the information you find on this website (Signalz Online), is strictly at your own risk. Signalz Online will not be liable for any losses and/or damages in connection with the use of our website.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">External Links Disclaimer</h2>
        <p>
            From our website, you can visit other websites by following hyperlinks to such external sites. While we strive to provide only quality links to useful and ethical websites, we have no control over the content and nature of these sites. These links to other websites do not imply a recommendation for all the content found on these sites. Site owners and content may change without notice and may occur before we have the opportunity to remove a link which may have gone 'bad'.
        </p>
        <p>
            Please be also aware that when you leave our website, other sites may have different privacy policies and terms which are beyond our control. Please be sure to check the Privacy Policies of these sites as well as their "Terms of Service" before engaging in any business or uploading any information.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">Accuracy of Sports Data</h2>
        <p>
            While tournament schedules, match kickoffs, scores, and team details are synced regularly using programmatic feeds, live football scores and event timelines may experience delays or errors due to network latency, upstream provider outages, or rendering problems. We recommend verifying official tournament documentation for critical schedules or statistics.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">Consent</h2>
        <p>
            By using our website, you hereby consent to our disclaimer and agree to its terms.
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
