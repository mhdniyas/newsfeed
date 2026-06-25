@extends('layouts.app')

@section('title', 'Privacy Policy - Signalz Online')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Signalz Online</p>
        <h1 class="mt-1 text-3xl font-extrabold text-slate-950">Privacy Policy</h1>
        <div class="mt-2 h-1 w-12 bg-emerald-500 rounded"></div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 p-6 sm:p-10 shadow-sm leading-relaxed text-slate-600 space-y-6">
        <p class="text-sm text-slate-400">Last updated: June 25, 2026</p>

        <p>
            At <strong>Signalz Online</strong> (accessible from <a href="http://signalz.online" class="text-emerald-600 font-medium hover:underline">http://signalz.online</a>), one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by Signalz Online and how we use it.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">1. Log Files & Analytics</h2>
        <p>
            Signalz Online follows a standard procedure of using log files and localized databases to monitor visitor analytics. The information collected by log files includes internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring/exit pages, and country locations. This data is used solely to analyze trends, administer the site, track users' movement on the website, and compile demographic insights.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">2. Cookies and Web Beacons</h2>
        <p>
            Like any other website, Signalz Online uses "cookies". These cookies are used to store information including visitors' preferences, and the pages on the website that the visitor accessed or visited. The information is used to optimize the users' experience by customizing our web page content based on visitors' browser type and/or other information.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">3. Google DoubleClick DART Cookie</h2>
        <p>
            Google is one of the third-party vendors on our site. It also uses cookies, known as DART cookies, to serve ads to our site visitors based upon their visit to our site and other sites on the internet. However, visitors may choose to decline the use of DART cookies by visiting the Google ad and content network Privacy Policy at the following URL: <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:underline">https://policies.google.com/technologies/ads</a>.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">4. Advertising Partners Privacy Policies</h2>
        <p>
            Third-party ad servers or ad networks use technologies like cookies, JavaScript, or Web Beacons that are used in their respective advertisements and links that appear on Signalz Online, which are sent directly to users' browsers. They automatically receive your IP address when this occurs. These technologies are used to measure the effectiveness of their advertising campaigns and/or to personalize the advertising content that you see on websites that you visit.
        </p>
        <p class="text-sm bg-slate-50 border border-slate-100 rounded-xl p-4">
            <strong>Note:</strong> Signalz Online has no access to or control over these cookies that are used by third-party advertisers.
        </p>

        <h2 class="text-xl font-bold text-slate-950 pt-4">5. Consent</h2>
        <p>
            By using our website, you hereby consent to our Privacy Policy and agree to its terms.
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
