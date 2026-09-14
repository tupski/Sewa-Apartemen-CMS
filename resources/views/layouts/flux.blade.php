<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="unsaved-warning" content="{{ __('admin.unsaved_warning') }}">

    <title>@yield('title', \App\Services\SettingsService::get('site_name', config('app.name')).' - Admin')</title>

    @php
        $fluxEnableDark = \App\Services\SettingsService::get('enable_dark_mode', false);
        $fluxSiteFavicon = \App\Services\SettingsService::get('site_favicon', '');
    @endphp

    @if($fluxSiteFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $fluxSiteFavicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif

    {{-- Same admin.theme key as the legacy admin layout, so the choice carries
         across both shells. Guarded by the admin dark-mode setting. --}}
    <script>
        (function () {
            var enableDark = {{ $fluxEnableDark ? 'true' : 'false' }};
            var stored = localStorage.getItem('admin.theme');
            var dark = stored ? stored === 'dark' : enableDark;
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>

    {{-- Appearance helper Flux (dark scrollbars + flux.appearance) --}}
    @fluxAppearance

    {{-- Font Awesome 6 (free): the admin panel's existing icon language --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css"></noscript>

    {{-- Admin CSS: separate Tailwind v4 pipeline (built to public/assets/admin.css) --}}
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">

    @stack('head')
</head>
<body class="font-sans antialiased bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-100 focus:bg-zinc-900 focus:text-white focus:px-4 focus:py-2 focus:rounded-md focus:outline-2 focus:outline-offset-2 focus:outline-blue-600">
        {{ __('admin.skip_to_content') }}
    </a>

    <flux:sidebar stashable sticky class="border-r border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-900!">
        <x-admin.sidebar />

        <flux:sidebar.collapse class="max-lg:hidden" />
    </flux:sidebar>

    <flux:header class="border-b border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-900">
        <x-admin.topbar :breadcrumbs="$breadcrumbs ?? null" />
    </flux:header>

    <flux:main id="main-content" class="max-w-7xl">
        @if (session('success') || session('error') || session('warning') || session('info'))
            <div class="mb-5 space-y-2">
                @foreach (['success', 'error', 'warning', 'info'] as $flashKey)
                    @if (session($flashKey))
                        <flux:callout variant="inline" color="{{ ['success' => 'green', 'error' => 'red', 'warning' => 'amber', 'info' => 'blue'][$flashKey] }}">
                            <flux:callout.text>{{ session($flashKey) }}</flux:callout.text>
                        </flux:callout>
                    @endif
                @endforeach
            </div>
        @endif

        {{ $slot ?? '' }}
    </flux:main>

    {{-- Livewire JS (UMD, boots Alpine) + Flux JS (pro dist via /flux/flux.js route).
         This layout never loads resources/js/app.js: the UMD bundle assigns
         window.Alpine, and app.js starts its own instance. --}}
    @fluxScripts

    @fluxToastBridge

    <flux:toast position="bottom end" />

    @stack('scripts')
</body>
</html>
