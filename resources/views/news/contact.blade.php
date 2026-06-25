@extends('layouts.app')

@section('title', 'Contact Us - Signalz Online')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">Signalz Online</p>
        <h1 class="mt-1 text-3xl font-extrabold text-slate-950">Contact Us</h1>
        <div class="mt-2 h-1 w-12 bg-emerald-500 rounded"></div>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <!-- Contact Information -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-[2rem] border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-950 mb-4">Get in Touch</h3>
                
                <div class="space-y-4 text-sm text-slate-600">
                    <div class="flex items-start gap-3">
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 mt-0.5">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="font-semibold text-slate-900">Email Address</p>
                            <a href="mailto:support@signalz.online" class="hover:underline text-emerald-600 font-medium">support@signalz.online</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 mt-0.5">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                <path d="M2 12h20"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="font-semibold text-slate-900">Website</p>
                            <a href="{{ route('news.index') }}" class="hover:underline text-emerald-600 font-medium">www.signalz.online</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-900 to-slate-950 text-white rounded-[2rem] p-6 shadow-sm border border-slate-800">
                <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-400">Response Time</h4>
                <p class="mt-2 text-2xl font-black">24-48 Hours</p>
                <p class="mt-2 text-xs text-slate-400">We work diligently to respond to all inquiries as quickly as possible during standard business days.</p>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-[2rem] border border-slate-200 p-6 sm:p-10 shadow-sm">
                <h3 class="text-lg font-bold text-slate-950 mb-2">Send us a Message</h3>
                <p class="text-sm text-slate-500 mb-6">Fill out the form below, and we will get back to you shortly.</p>

                <form id="contact-mock-form" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" id="name" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:border-emerald-500 focus:bg-white focus:outline-none transition">
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Email Address</label>
                            <input type="email" id="email" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:border-emerald-500 focus:bg-white focus:outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Subject</label>
                        <input type="text" id="subject" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:border-emerald-500 focus:bg-white focus:outline-none transition">
                    </div>

                    <div>
                        <label for="message" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Message</label>
                        <textarea id="message" rows="5" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:border-emerald-500 focus:bg-white focus:outline-none transition resize-none"></textarea>
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-2xl bg-slate-950 py-3.5 text-sm font-black text-white hover:bg-slate-800 transition shadow-sm">
                        Send Message
                    </button>
                </form>

                <div id="contact-success-msg" class="hidden mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">
                    Thank you! Your message has been successfully received. We will contact you soon.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('contact-mock-form');
        const successMsg = document.getElementById('contact-success-msg');

        if (form && successMsg) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                form.reset();
                form.classList.add('hidden');
                successMsg.classList.remove('hidden');
            });
        }
    });
</script>
@endsection
