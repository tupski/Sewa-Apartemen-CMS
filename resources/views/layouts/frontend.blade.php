<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ dark: document.documentElement.classList.contains('dark') }"
      x-init="$watch('dark', v => { document.documentElement.classList.toggle('dark', v); localStorage.setItem('theme', v ? 'dark' : 'light'); })"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $siteName = \App\Services\SettingsService::get('site_name', config('app.name', 'Kakarama Room'));
        $siteDescription = \App\Services\SettingsService::get('site_description', '');
        $siteLogo = \App\Services\SettingsService::get('site_logo', '');
        $siteFavicon = \App\Services\SettingsService::get('site_favicon', '');
        $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
        $contactEmail = \App\Services\SettingsService::get('contact_email', '');
        $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
        $contactAddress = \App\Services\SettingsService::get('contact_address', '');
        $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
        $enableDark = \App\Services\SettingsService::get('enable_dark_mode', false);
        $socialFacebook = \App\Services\SettingsService::get('social_facebook', '');
        $socialTwitter = \App\Services\SettingsService::get('social_twitter', '');
        $socialInstagram = \App\Services\SettingsService::get('social_instagram', '');
        $socialLinkedin = \App\Services\SettingsService::get('social_linkedin', '');
        $socialYoutube = \App\Services\SettingsService::get('social_youtube', '');

        $mainMenu = \App\Models\Navigation::with('page')->active()->inLocation('main')->rootItems()->ordered()->get();
        if ($mainMenu->isEmpty()) {
            $mainMenu = collect([
                (object) ['title' => __('nav.home'), 'url' => url('/')],
                (object) ['title' => __('nav.find_apartments'), 'url' => slug_url('slug_apartments', 'apartments')],
                (object) ['title' => 'Blog', 'url' => slug_url('slug_blog', 'blog')],
            ]);
        }
        $footerMenu = \App\Models\Navigation::with('page')->active()->inLocation('footer')->rootItems()->ordered()->get();
        if ($footerMenu->isEmpty()) {
            $footerMenu = collect([
                (object) ['title' => __('nav.find_apartments'), 'url' => slug_url('slug_apartments', 'apartments')],
                (object) ['title' => 'Blog', 'url' => slug_url('slug_blog', 'blog')],
            ]);
        }
    @endphp

    @if($siteFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif

    @if (isset($seo))
        {!! \App\Services\SeoService::renderMetaTags($seo) !!}
    @else
        <title>{{ isset($pageTitle) ? $pageTitle . ' — ' . $siteName : $siteName }}</title>
        @if ($siteDescription)
            <meta name="description" content="{{ $siteDescription }}">
        @endif
    @endif

    <!-- Apply dark mode before first paint (no flash) -->
    <script>
        (function () {
            var stored = localStorage.getItem('theme');
            var dark = stored ? stored === 'dark' : {{ $enableDark ? 'true' : 'false' }};
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Font Awesome 6 (free) — amenity icons; loaded async so CDN slowness doesn't block render -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css"></noscript>

    <!-- Lucide Icons (MIT) -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')

    {{-- Analytics & Integrations --}}
    @if (\App\Services\AnalyticsService::hasAny())
        @include('components.analytics')
    @endif
</head>
<body class="font-sans antialiased bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm dark:bg-gray-900/95 dark:border-gray-800">
        <div x-data="{ open: false }" x-effect="open ? document.getElementById('mobile-menu').classList.add('menu-open') : document.getElementById('mobile-menu').classList.remove('menu-open')" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                <!-- Brand -->
                <a href="{{ url('/') }}" class="flex items-center space-x-2">
                    @if ($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}"
                             alt="{{ $siteName }}"
                             class="h-10 w-auto object-contain">
                    @else
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold"
                              style="background-color: {{ $primaryColor }}">{{ mb_substr($siteName, 0, 1) }}</span>
                        <span class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ $siteName }}</span>
                    @endif
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden lg:flex items-center space-x-8" aria-label="Main navigation">
                    @foreach ($mainMenu as $item)
                        <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                           class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition {{ request()->url() === ($item->url ?? '') ? 'text-gray-900 font-semibold dark:text-white' : '' }}">
                            {{ $item->title }}
                        </a>
                    @endforeach
                </nav>

                <!-- Actions (desktop) -->
                <div class="hidden lg:flex items-center space-x-4">
                    <!-- Dark mode toggle -->
                    <button @click="dark = !dark"
                            class="p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 transition"
                            aria-label="Toggle dark mode" :title="dark ? 'Light mode' : 'Dark mode'">
                        <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="dark" class="w-5 h-5" style="display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>

                    @if ($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-semibold text-white transition hover:opacity-90"
                           style="background-color: #25d366">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            {{ __('nav.whatsapp') }}
                        </a>
                    @endif
                    <a href="{{ slug_url('slug_apartments', 'apartments') }}" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-semibold text-white transition hover:opacity-90"
                       style="background-color: {{ $primaryColor }}">
                       {{ __('nav.find_apartments') }}
                    </a>
                </div>

                <!-- Mobile toggle -->
                <div class="flex lg:hidden items-center space-x-2">
                    <!-- Dark mode toggle (mobile) -->
                    <button @click="dark = !dark"
                            class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none"
                            aria-label="Toggle dark mode" :title="dark ? 'Light mode' : 'Dark mode'">
                        <svg x-show="!dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="dark" class="h-5 w-5" style="display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                    <!-- Hamburger: animates to X when open -->
                    <button @click="open = !open"
                            id="mobile-menu-btn"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none transition"
                            aria-label="Toggle menu"
                            :aria-expanded="open.toString()">
                        <i data-lucide="menu" id="menu-icon-open" class="w-6 h-6" :class="{ 'hidden': open }"></i>
                        <i data-lucide="x" id="menu-icon-close" class="w-6 h-6 hidden" :class="{ 'hidden': !open }"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Nav — smooth slide via CSS #mobile-menu transition -->
            <div id="mobile-menu" class="lg:hidden">
                @foreach ($mainMenu as $item)
                    <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif>
                        {{ $item->title }}
                    </a>
                @endforeach
                <div class="pt-2 pb-1 flex space-x-3 px-1">
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                           class="flex-1 text-center px-4 py-2.5 rounded-full text-sm font-semibold text-white" style="background-color: #25d366">
                            {{ __('nav.whatsapp') }}
                        </a>
                    @endif
                    <a href="{{ slug_url('slug_apartments', 'apartments') }}" class="flex-1 text-center px-4 py-2.5 rounded-full text-sm font-semibold text-white"
                       style="background-color: {{ $primaryColor }}">
                       {{ __('nav.find_apartments') }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                <!-- Brand -->
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        @if ($siteLogo)
                            <img src="{{ asset('storage/' . $siteLogo) }}"
                                 alt="{{ $siteName }}"
                                 class="h-10 w-auto object-contain">
                        @else
                            <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold"
                                  style="background-color: {{ $primaryColor }}">{{ mb_substr($siteName, 0, 1) }}</span>
                            <span class="text-lg font-bold text-white">{{ $siteName }}</span>
                        @endif
                    </div>
                    @if ($siteDescription)
                        <p class="text-sm text-gray-400 mb-4 leading-relaxed">{{ $siteDescription }}</p>
                    @endif
                    <div class="flex items-center space-x-3 text-sm">
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center px-4 py-2 rounded-full text-white text-xs font-semibold hover:opacity-90 transition" style="background-color: #25d366">
                                {{ __('footer.whatsapp_us') }}
                            </a>
                        @endif
                        <a href="{{ slug_url('slug_apartments', 'apartments') }}" class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold text-white hover:opacity-90 transition"
                           style="background-color: {{ $primaryColor }}">
                           {{ __('footer.view_apartments') }}
                        </a>
                    </div>
                </div>

                <!-- Menu -->
                <div>
                    <h3 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">{{ __('footer.menu') }}</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach ($footerMenu as $item)
                            <li>
                                <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                                   class="text-gray-400 hover:text-white transition">
                                    {{ $item->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">{{ __('footer.contact') }}</h3>
                    <ul class="space-y-3 text-sm text-gray-400">
                        @if ($contactAddress)
                            <li class="flex items-start space-x-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>{{ $contactAddress }}</span>
                            </li>
                        @endif
                        @if ($contactPhone)
                            <li class="flex items-center space-x-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ $contactPhone }}</span>
                            </li>
                        @endif
                        @if ($contactEmail)
                            <li class="flex items-center space-x-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <a href="mailto:{{ $contactEmail }}" class="hover:text-white transition">{{ $contactEmail }}</a>
                            </li>
                        @endif
                    </ul>

                    <!-- Social Media -->
                    @if($socialFacebook || $socialTwitter || $socialInstagram || $socialLinkedin || $socialYoutube)
                        <div class="flex items-center space-x-3 mt-4">
                            @if($socialFacebook)
                                <a href="{{ $socialFacebook }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition" title="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                            @endif
                            @if($socialTwitter)
                                <a href="{{ $socialTwitter }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition" title="Twitter">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                                </a>
                            @endif
                            @if($socialInstagram)
                                <a href="{{ $socialInstagram }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition" title="Instagram">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                </a>
                            @endif
                            @if($socialLinkedin)
                                <a href="{{ $socialLinkedin }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition" title="LinkedIn">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                            @endif
                            @if($socialYoutube)
                                <a href="{{ $socialYoutube }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition" title="YouTube">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. {{ __('footer.rights') }}</p>
                <p>{{ __('footer.tagline') }}</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to top -->
    <button id="scroll-top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="hidden fixed bottom-36 right-6 z-50 w-11 h-11 rounded-full shadow-lg items-center justify-center text-white hover:opacity-90 transition"
            style="background-color: {{ $primaryColor }}" aria-label="Scroll to top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <script>
        window.addEventListener('scroll', function () {
            var btn = document.getElementById('scroll-top');
            if (!btn) return;
            if (window.scrollY > 300) {
                btn.classList.remove('hidden');
                btn.classList.add('inline-flex');
            } else {
                btn.classList.add('hidden');
                btn.classList.remove('inline-flex');
            }
        });
    </script>

    {{-- Floating WhatsApp Button --}}
    @if (!empty($whatsapp))
        @php $waNumber = preg_replace('/\D/', '', $whatsapp); @endphp
        <a href="https://wa.me/{{ $waNumber }}"
           target="_blank"
           rel="noopener noreferrer"
           class="fixed bottom-20 right-6 z-50 w-14 h-14 rounded-full shadow-xl flex items-center justify-center hover:scale-110 transition-transform"
           style="background-color: #25D366;"
           aria-label="Chat via WhatsApp">
            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </a>
    @endif

    <script>
        // Reinitialise Lucide after Alpine toggles icon visibility
        document.addEventListener('alpine:initialized', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>

    {{-- Global Share Modal (opened via `open-share-modal` window event) --}}
    @include('components.share-modal')

    @stack('scripts')

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
