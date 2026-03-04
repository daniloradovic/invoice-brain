<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Invoice Brain') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-gray-50">

<div class="flex h-screen overflow-hidden">

    {{-- Dark Sidebar --}}
    <aside class="w-64 flex-shrink-0 flex flex-col" style="background-color: #1a1a2e;">
        {{-- Logo / Brand --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-white/10">
            <div>
                <span class="text-white font-bold text-lg tracking-tight">Invoice Brain</span>
                <div class="mt-1">
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-400">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        MCP Connected
                    </span>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            @php
                $navLinks = [
                    ['route' => 'dashboard',      'label' => 'Dashboard',  'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route' => 'clients.index',  'label' => 'Clients',    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['route' => 'invoices.index', 'label' => 'Invoices',   'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['route' => 'worklogs.index', 'label' => 'Work Logs',  'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
            @endphp

            @foreach ($navLinks as $link)
                @php $active = request()->routeIs($link['route']) || request()->routeIs($link['route'].'.*'); @endphp
                <a href="{{ route($link['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ $active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $link['icon'] }}" />
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Footer --}}
        <div class="px-5 py-4 border-t border-white/10">
            <p class="text-xs text-gray-500">Laravel 12 · MCP Demo</p>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="flex-shrink-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <div>
                @isset($heading)
                    <h1 class="text-xl font-semibold text-gray-900">{{ $heading }}</h1>
                @endisset
            </div>
        </header>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mx-6 mt-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2 text-green-800 text-sm"
                 x-data="{ show: true }" x-show="show" x-transition>
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
                <button @click="show = false" class="ml-auto text-green-500 hover:text-green-700">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="mx-6 mt-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-800 text-sm"
                 x-data="{ show: true }" x-show="show" x-transition>
                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
                <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">&times;</button>
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>

</div>

</body>
</html>
