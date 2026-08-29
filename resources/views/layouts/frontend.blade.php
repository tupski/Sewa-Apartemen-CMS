<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ dark: document.documentElement.classList.contains('dark') }"
      x-init="$watch('dark', v => { document.documentElement.classList.toggle('dark', v); localStorage.setItem('theme', v ? 'dark' : 'light'); })"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ \App\Services\SettingsService::get('primary_color', '#ffffff') }}">

    @php
        $siteName = \App\Services\SettingsService::get('site_name', config('app.name', 'Kakarama Room'));
        $siteDescription = \App\Services\SettingsService::get('site_description', '');
        $footerAbout = \App\Services\SettingsService::get('footer_about', '');
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

        $mainMenu = \App\Models\Navigation::with([
                'page',
                'children' => fn ($q) => $q->where('status', 'active')->orderBy('order'),
                'children.page',
            ])->active()->inLocation('main')->rootItems()->ordered()->get();
        if ($mainMenu->isEmpty()) {
            $mainMenu = collect([
                (object) ['title' => __('nav.home'), 'url' => url('/')],
                (object) ['title' => __('nav.find_apartments'), 'url' => slug_url('slug_apartments', 'apartments')],
                (object) ['title' => 'Blog', 'url' => slug_url('slug_blog', 'blog')],
                (object) ['title' => __('nav.promo'), 'url' => route('promotions')],
                (object) ['title' => __('nav.contact'), 'url' => route('contact')],
            ]);
        }
        // Footer menu mirrors the header (main) menu: same Navigation source, children
        // flattened so dropdown items are still reachable from the footer.
        $footerMenu = $mainMenu->flatMap(function ($item) {
            return collect([$item])->concat(collect($item->children ?? []));
        })->values();

        // Social links, shared by the footer and the mobile drawer.
        $socialLinks = collect([
            ['name' => 'Facebook', 'url' => $socialFacebook, 'path' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
            ['name' => 'Twitter', 'url' => $socialTwitter, 'path' => 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z'],
            ['name' => 'Instagram', 'url' => $socialInstagram, 'path' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z'],
            ['name' => 'LinkedIn', 'url' => $socialLinkedin, 'path' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
            ['name' => 'YouTube', 'url' => $socialYoutube, 'path' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'],
        ])->filter(fn ($s) => ! empty($s['url']))->values();
    @endphp

    @if($siteFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif

    @if (isset($seo))
        {!! \App\Services\SeoService::renderMetaTags($seo) !!}
    @else
        @php
            $isHome = \App\Services\SeoService::isHomepage();
            $fallbackTitle = isset($pageTitle)
                ? \App\Services\SeoService::title($pageTitle, $isHome)
                : \App\Services\SeoService::title($siteName, $isHome);
        @endphp
        <title>{{ $fallbackTitle }}</title>
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

    <!-- Leaflet (map library) — CDN, pinned + SRI. Used by the property detail map. -->
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')

    {{-- Analytics & Integrations --}}
    @if (\App\Services\AnalyticsService::hasAny())
        @include('components.analytics')
    @endif
</head>
<body class="font-sans antialiased bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    {{-- Google Tag Manager (noscript) — must be immediately after opening <body> --}}
    @include('components.analytics-body')

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm dark:bg-gray-900/95 dark:border-gray-800"
            x-data="{ open: false }"
            @keydown.escape.window="open = false">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                <!-- Left cluster: hamburger (mobile only) + brand -->
                <div class="flex items-center gap-2">
                    <!-- Hamburger (mobile) — opens left slide-in drawer -->
                    <button @click="open = true"
                            class="lg:hidden inline-flex items-center justify-center p-2 -ml-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none transition"
                            :aria-expanded="open.toString()"
                            aria-controls="mobile-drawer"
                            aria-label="{{ __('menu.open') }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Brand -->
                    <a href="{{ url('/') }}" class="flex items-center space-x-2">
                        @if ($siteLogo)
                            <img src="{{ asset('storage/' . $siteLogo) }}"
                                 alt="{{ $siteName }}"
                                 class="h-10 w-auto object-contain dark:brightness-0 dark:invert">
                        @else
                            <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold"
                                  style="background-color: {{ $primaryColor }}">{{ mb_substr($siteName, 0, 1) }}</span>
                            <span class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ $siteName }}</span>
                        @endif
                    </a>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden lg:flex items-center space-x-8" aria-label="Main navigation">
                    @foreach ($mainMenu as $item)
                        <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                           class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition {{ request()->url() === ($item->url ?? '') ? 'text-gray-900 font-semibold dark:text-white' : '' }}">
                            {{ $item->title }}
                        </a>
                    @endforeach
                </nav>

                <!-- Actions (right, all breakpoints) -->
                <div class="flex items-center gap-1 sm:gap-2 lg:gap-4">
                    <!-- Search (magnifier) — opens fullscreen overlay -->
                    <button type="button"
                            @click="$dispatch('open-search')"
                            class="p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 transition focus:outline-none"
                            aria-label="{{ __('search.open') }}" title="{{ __('search.open') }}">
                        <i class="fa-solid fa-magnifying-glass w-5 h-5 text-[1.05rem] leading-5 text-center" aria-hidden="true"></i>
                    </button>

                    <!-- Dark mode toggle -->
                    <button @click="dark = !dark"
                            class="p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 transition focus:outline-none"
                            aria-label="Toggle dark mode" :title="dark ? 'Light mode' : 'Dark mode'">
                        <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="dark" class="w-5 h-5" style="display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>

                    <!-- WhatsApp + Find Apartments (desktop only) -->
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                           class="hidden lg:inline-flex items-center px-5 py-2.5 rounded-full text-sm font-semibold text-white transition hover:opacity-90"
                           style="background-color: #25d366">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            {{ __('nav.whatsapp') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- ===================== Mobile slide-in drawer (left → right) ===================== -->
        {{-- Teleported to <body>: the header's `backdrop-blur` (backdrop-filter) makes
             it the containing block for position:fixed children, so an in-header drawer
             gets `h-full` = header height (64px) and the nav links overflow hidden.
             Teleporting escapes that containing block while keeping the `open` scope. --}}
        <template x-teleport="body">
        <div class="lg:hidden" x-cloak>
            <!-- Backdrop over the exposed area; tap to close -->
            <div id="mobile-drawer-backdrop"
                 x-show="open"
                 @click="open = false"
                 x-transition:enter="transition-opacity ease-out duration-300"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-200"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
                 aria-hidden="true"></div>

            <!-- Drawer panel: ~80% width, max 320px, slides from the left -->
            {{-- Swipe LEFT (the panel's own slide-out direction) dismisses it on touch
                 devices; the gesture calls the same `open = false` the X button and the
                 backdrop tap use, so aria-expanded / x-cloak / Escape all still apply. --}}
            <div id="mobile-drawer"
                 role="dialog" aria-modal="true" aria-label="{{ __('menu.open') }}"
                 x-show="open"
                 x-swipe-close="{ direction: 'left', backdrop: '#mobile-drawer-backdrop', onClose: () => open = false }"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 class="fixed top-0 left-0 z-50 h-full w-4/5 max-w-xs bg-white dark:bg-gray-900 shadow-2xl flex flex-col">
                <!-- Drawer header: brand + close (X) -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-100 dark:border-gray-800">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2">
                        @if ($siteLogo)
                            <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain dark:brightness-0 dark:invert">
                        @else
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold"
                                  style="background-color: {{ $primaryColor }}">{{ mb_substr($siteName, 0, 1) }}</span>
                            <span class="text-base font-bold text-gray-900 dark:text-white">{{ $siteName }}</span>
                        @endif
                    </a>
                    <button @click="open = false"
                            class="p-2 -mr-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none transition"
                            aria-label="{{ __('menu.close') }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Drawer nav links -->
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Mobile navigation">
                    @foreach ($mainMenu as $item)
                        @php $children = collect($item->children ?? []); @endphp
                        @if ($children->isNotEmpty())
                            <div x-data="{ sub: false }">
                                <button type="button" @click="sub = !sub"
                                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[0.9375rem] font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition"
                                        :aria-expanded="sub.toString()">
                                    <span>{{ $item->title }}</span>
                                    <svg class="w-4 h-4 transition-transform" :class="sub ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="sub" x-cloak
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     class="mt-1 ml-3 pl-2 border-l border-gray-100 dark:border-gray-800 space-y-1">
                                    <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                                       class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white transition">
                                        {{ $item->title }}
                                    </a>
                                    @foreach ($children as $child)
                                        <a href="{{ $child->url ?? '#' }}" @if(($child->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                                           class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white transition">
                                            {{ $child->title }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                               class="block px-3 py-2.5 rounded-lg text-[0.9375rem] font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition">
                                {{ $item->title }}
                            </a>
                        @endif
                    @endforeach
                </nav>

                <!-- Drawer: WhatsApp button (above the divider) -->
                @if ($whatsapp)
                    <div class="px-4 py-4 border-t border-gray-100 dark:border-gray-800">
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                           class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-full text-sm font-semibold text-white hover:opacity-90 transition"
                           style="background-color: #25d366">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            {{ __('nav.whatsapp') }}
                        </a>
                    </div>
                @endif

                <!-- Drawer: pinned bottom — Follow us + social icons -->
                @if ($socialLinks->isNotEmpty())
                    <div class="mt-auto px-4 py-4 border-t border-gray-100 dark:border-gray-800">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                            {{ __('footer.follow_us') }}
                        </h2>
                        <div class="flex items-center gap-4">
                            @foreach ($socialLinks as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-400 rounded"
                                   aria-label="{{ $social['name'] }}" title="{{ $social['name'] }}">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $social['path'] }}"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        </template>
    </header>

    <!-- ===================== Fullscreen live-search overlay ===================== -->
    <div x-data="searchOverlay({ action: @js(route('search.suggest')) })"
         @open-search.window="openOverlay()"
         x-cloak>
        <div x-show="open"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] bg-white/98 dark:bg-gray-900/98 backdrop-blur-md overflow-y-auto"
             role="dialog" aria-modal="true" aria-label="{{ __('search.open') }}">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 pt-6 sm:pt-16 pb-10">
                <!-- Close (X) -->
                <div class="flex justify-end mb-4">
                    <button @click="closeOverlay()"
                            class="p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none transition"
                            aria-label="{{ __('search.close') }}" title="{{ __('search.close') }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Large input -->
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" aria-hidden="true">
                        <i class="fa-solid fa-magnifying-glass text-xl"></i>
                    </span>
                    <input type="text" x-ref="input"
                           x-model="query" x-on:input="search()" x-on:keydown="onKeydown($event)"
                           autocomplete="off" spellcheck="false"
                           placeholder="{{ __('search.placeholder') }}"
                           aria-label="{{ __('search.placeholder') }}"
                           :aria-busy="loading.toString()"
                           class="w-full h-16 pl-14 pr-14 text-lg sm:text-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-0"
                           :style="'--tw-ring-color: {{ $primaryColor }}'" />
                    <span x-show="loading" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    </span>
                </div>

                <!-- Live results -->
                <div class="mt-4">
                    <ul x-show="hasResults" role="listbox"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg ring-1 ring-black/5 border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                        <template x-for="(r, i) in results" :key="r.url">
                            <li role="option" :aria-selected="highlighted === i">
                                <a x-on:click.prevent="go(r)"
                                   x-on:mouseenter="highlighted = i"
                                   href="#" :class="highlighted === i ? 'bg-gray-50 dark:bg-gray-700/60' : ''"
                                   class="flex items-start justify-between gap-3 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                                    <span class="text-base text-gray-800 dark:text-gray-100" x-html="highlight(r.title)"></span>
                                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide"
                                          :class="r.type === 'property' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300' : (r.type === 'post' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300')"
                                          x-text="r.type"></span>
                                </a>
                            </li>
                        </template>
                    </ul>

                    <!-- No results -->
                    <div x-show="!hasResults && !loading && query.trim().length >= 2"
                         class="mt-2 px-5 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('search.no_results') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                            {{-- Footer is dark in both light & dark themes, so always render the logo white --}}
                            <img src="{{ asset('storage/' . $siteLogo) }}"
                                 alt="{{ $siteName }}"
                                 class="h-10 w-auto object-contain brightness-0 invert">
                        @else
                            <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold"
                                  style="background-color: {{ $primaryColor }}">{{ mb_substr($siteName, 0, 1) }}</span>
                            <span class="text-lg font-bold text-white">{{ $siteName }}</span>
                        @endif
                    </div>
                    @if ($footerAbout)
                        <p class="text-sm text-gray-400 leading-relaxed">{{ $footerAbout }}</p>
                    @endif
                </div>

                <!-- Menu (mirrors the header navigation, children flattened) -->
                <div>
                    <h3 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">{{ __('footer.menu') }}</h3>
                    <nav aria-label="Footer navigation">
                        <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-1 gap-x-6 gap-y-2 text-sm">
                            @foreach ($footerMenu as $item)
                                <li>
                                    <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                                       class="text-gray-400 hover:text-white transition">
                                        {{ $item->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
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
                    @if($socialLinks->isNotEmpty())
                        <div class="flex items-center space-x-3 mt-4">
                            @foreach ($socialLinks as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="text-gray-400 hover:text-white transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 focus-visible:ring-gray-400 rounded"
                                   aria-label="{{ $social['name'] }}" title="{{ $social['name'] }}">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $social['path'] }}"/></svg>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. {{ __('footer.rights') }}</p>
                <div class="flex flex-col sm:flex-row items-center gap-x-4 gap-y-2">
                    <p>{{ __('footer.tagline') }}</p>
                    <x-powered-by class="inline-flex flex-wrap items-center gap-1 text-gray-500"
                                  link-class="font-medium text-gray-400 underline underline-offset-2 hover:text-white transition" />
                </div>
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
           class="fixed bottom-20 right-6 z-30 w-14 h-14 rounded-full shadow-xl flex items-center justify-center hover:scale-110 transition-transform whatsapp-pulse"
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

    <!-- Leaflet (map library) — CDN, pinned + SRI. Loaded before per-page scripts
         so the global `L` is available to initPropertyMap() in app.js. -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLEg="
            crossorigin=""></script>

    @stack('scripts')

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
