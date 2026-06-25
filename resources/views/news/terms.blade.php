@extends('layouts.app')

@section('title', 'Terms of Service - Signalz Online')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Signalz Online</p>
        <h1 class="mt-1 text-3xl font-extrabold text-slate-950">Terms of Service</h1>
        <div class="mt-2 h-1 w-12 bg-emerald-500 rounded"></div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 p-6 sm:p-10 shadow-sm leading-relaxed text-slate-600 space-y-6">
        <p class="text-sm text-slate-400">Last updated: June 25, 2026</p>

        <h2 class="text-xl font-bold text-slate-950">1. Acceptance of Terms</h2>
        <p>
            By accessing and browsing the website <strong>Signalz Online</strong> (<a href="http://signalz.online" class="text-emerald-600 font-medium hover:underline">http://signalz.online</a>), you agree to comply with and be bound by the following Terms of Service. If you disagree with any part of these terms, please do not use our website.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">2. Intellectual Property Rights</h2>
        <p>
            Unless otherwise stated, Signalz Online and/or its licensors own the intellectual property rights for all material on Signalz Online. All intellectual property rights are reserved. You may access this from Signalz Online for your own personal use subjected to restrictions set in these terms.
        </p>
        <p>You must not:</p>
        <ul class="list-disc list-inside pl-4 space-y-1 text-sm">
            <li>Republish material from Signalz Online</li>
            <li>Sell, rent, or sub-license material from Signalz Online</li>
            <li>Reproduce, duplicate or copy material from Signalz Online</li>
            <li>Redistribute content from Signalz Online without prior permission</li>
        </ul>

        <h2 class="text-xl font-bold text-slate-950 pt-4">3. Hyperlinking to our Content</h2>
        <p>
            Organizations may link to our home page, to publications, or to other website information so long as the link is not in any way deceptive, does not falsely imply sponsorship or endorsement, and fits within the context of the linking party's site.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">4. Content Liability</h2>
        <p>
            We shall not be held responsible for any content that appears on your website. You agree to protect and defend us against all claims that are rising on your website. No link(s) should appear on any website that may be interpreted as libelous, obscene, or criminal, or which infringes, otherwise violates, or advocates the infringement or other violation of, any third party rights.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">5. Disclaimer of Warranties</h2>
        <p>
            To the maximum extent permitted by applicable law, we exclude all representations, warranties, and conditions relating to our website and the use of this website. Nothing in this disclaimer will limit or exclude our or your liability for death or personal injury, or limit or exclude fraud or fraudulent misrepresentation.
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
