@extends('layouts.app')

@section('title', 'Admin Passcode Access - World Cup News Explorer')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-md w-full space-y-8 bg-white border border-slate-200/80 rounded-3xl p-8 shadow-lg shadow-slate-100/85">
        
        <div class="text-center">
            <!-- Lock Icon -->
            <div class="w-12 h-12 bg-amber-500/10 border border-amber-500/20 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Admin Portal</h2>
            <p class="mt-1 text-sm text-slate-500">
                Please enter your security passcode to log in.
            </p>
        </div>

        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-650 text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

        <form class="mt-6 space-y-4" action="{{ route('admin.login.submit') }}" method="POST">
            @csrf
            
            <div>
                <label for="passcode" class="sr-only">Passcode</label>
                <div class="relative">
                    <input id="passcode" 
                           name="passcode" 
                           type="password" 
                           required 
                           placeholder="••••••••" 
                           class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl px-4 py-3 pl-11 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all duration-200">
                    <div class="absolute left-4 text-slate-400 top-3.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                </div>
                @error('passcode')
                    <p class="mt-1.5 text-xs text-red-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-xl text-sm font-bold text-slate-950 bg-amber-500 hover:bg-amber-600 active:scale-98 transition-all duration-150 cursor-pointer shadow-sm">
                    Unlock Dashboard
                </button>
            </div>
        </form>

        <div class="text-center pt-2">
            <a href="{{ route('news.index') }}" class="text-xs font-semibold text-slate-400 hover:text-slate-600 flex items-center justify-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to News Explorer</span>
            </a>
        </div>

    </div>
</div>
@endsection
