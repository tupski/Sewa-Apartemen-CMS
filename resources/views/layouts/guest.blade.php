<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $guestSiteName    = \App\Services\SettingsService::get('site_name', config('app.name', 'Sewa Apartemen'));
            $guestSiteLogo    = \App\Services\SettingsService::get('site_logo', '');
            $guestSiteFavicon = \App\Services\SettingsService::get('site_favicon', '');
            $guestPrimary     = \App\Services\SettingsService::get('primary_color', '#3b82f6');
        @endphp

        <title>{{ $guestSiteName }}</title>

        @if($guestSiteFavicon)
            <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $guestSiteFavicon) }}">
        @else
            <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --guest-primary: {{ $guestPrimary }};
            }
        </style>

        @stack('head')

        {{-- Analytics & Integrations --}}
        @if(\App\Services\AnalyticsService::hasAny())
            @include('components.analytics')
        @endif
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        {{-- Google Tag Manager (noscript) — must be immediately after opening <body> --}}
        @include('components.analytics-body')
        @stack('body_start')

        {{ $slot }}

        @stack('scripts')
    </body>
</html>
