<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="unsaved-warning" content="{{ __('admin.unsaved_warning') }}">

    <title>@yield('title', config('app.name', 'Laravel').' - Admin')</title>

    {{-- Appearance helper Flux (scrollbar dark + toggle .dark) --}}
    @fluxAppearance

    {{-- CSS admin: pipeline Tailwind v4 terpisah (built ke public/assets/admin.css) --}}
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">

    @stack('head')
</head>
<body class="font-sans antialiased">
    {{ $slot ?? '' }}

    {{-- Livewire JS (UMD) + Flux JS (pro dist via /flux/flux.js route) --}}
    @fluxScripts

    @stack('scripts')
</body>
</html>
