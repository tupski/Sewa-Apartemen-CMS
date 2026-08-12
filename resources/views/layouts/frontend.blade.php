<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $siteName = \App\Services\SettingsService::get('site_name', config('app.name', 'Kakarama Room'));
        $siteDescription = \App\Services\SettingsService::get('site_description', '');
        $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
        $contactEmail = \App\Services\SettingsService::get('contact_email', '');
        $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
        $contactAddress = \App\Services\SettingsService::get('contact_address', '');
        $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');

        $mainMenu = \App\Models\Navigation::active()->inLocation('main')->rootItems()->ordered()->get();
        if ($mainMenu->isEmpty()) {
            $mainMenu = collect([
                (object) ['title' => 'Beranda', 'url' => url('/')],
                (object) ['title' => 'Apartemen', 'url' => url('/apartments')],
                (object) ['title' => 'Blog', 'url' => url('/blog')],
            ]);
        }
        $footerMenu = \App\Models\Navigation::active()->inLocation('footer')->rootItems()->ordered()->get();
        if ($footerMenu->isEmpty()) {
            $footerMenu = collect([
                (object) ['title' => 'Apartemen', 'url' => url('/apartments')],
                (object) ['title' => 'Blog', 'url' => url('/blog')],
            ]);
        }
    @endphp

    @if (isset($seo))
        {!! \App\Services\SeoService::renderMetaTags($seo) !!}
    @else
        <title>{{ isset($pageTitle) ? $pageTitle . ' — ' . $siteName : $siteName }}</title>
        @if ($siteDescription)
            <meta name="description" content="{{ $siteDescription }}">
        @endif
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')

    {{-- Analytics & Integrations --}}
    @if (\App\Services\AnalyticsService::hasAny())
        @include('components.analytics')
    @endif
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm">
        <div x-data="{ open: false }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                <!-- Brand -->
                <a href="{{ url('/') }}" class="flex items-center space-x-2">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold"
                          style="background-color: {{ $primaryColor }}">{{ mb_substr($siteName, 0, 1) }}</span>
                    <span class="text-lg md:text-xl font-bold text-gray-900">{{ $siteName }}</span>
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden lg:flex items-center space-x-8" aria-label="Main navigation">
                    @foreach ($mainMenu as $item)
                        <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                           class="text-sm font-medium text-gray-700 hover:text-gray-900 transition {{ request()->url() === ($item->url ?? '') ? 'text-gray-900 font-semibold' : '' }}">
                            {{ $item->title }}
                        </a>
                    @endforeach
                </nav>

                <!-- Actions -->
                <div class="hidden lg:flex items-center space-x-4">
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-semibold text-white transition hover:opacity-90"
                           style="background-color: #25d366">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WhatsApp
                        </a>
                    @endif
                    <a href="{{ url('/apartments') }}" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-semibold text-white transition hover:opacity-90"
                       style="background-color: {{ $primaryColor }}">
                        Cari Apartemen
                    </a>
                </div>

                <!-- Mobile toggle -->
                <button @click="open = !open" class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none" aria-label="Toggle menu" :aria-expanded="open.toString()">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Nav -->
            <div :class="{'block': open, 'hidden': !open}" class="hidden lg:hidden pb-4 space-y-1">
                @foreach ($mainMenu as $item)
                    <a href="{{ $item->url ?? '#' }}" @if(($item->target ?? '') === '_blank') target="_blank" rel="noopener" @endif
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                        {{ $item->title }}
                    </a>
                @endforeach
                <div class="pt-2 flex space-x-3">
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                           class="flex-1 text-center px-4 py-2.5 rounded-full text-sm font-semibold text-white" style="background-color: #25d366">
                            WhatsApp
                        </a>
                    @endif
                    <a href="{{ url('/apartments') }}" class="flex-1 text-center px-4 py-2.5 rounded-full text-sm font-semibold text-white"
                       style="background-color: {{ $primaryColor }}">
                        Cari Apartemen
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
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold"
                              style="background-color: {{ $primaryColor }}">{{ mb_substr($siteName, 0, 1) }}</span>
                        <span class="text-lg font-bold text-white">{{ $siteName }}</span>
                    </div>
                    @if ($siteDescription)
                        <p class="text-sm text-gray-400 mb-4 leading-relaxed">{{ $siteDescription }}</p>
                    @endif
                    <div class="flex items-center space-x-3 text-sm">
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center px-4 py-2 rounded-full text-white text-xs font-semibold hover:opacity-90 transition" style="background-color: #25d366">
                                WhatsApp Kami
                            </a>
                        @endif
                        <a href="{{ url('/apartments') }}" class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold text-white hover:opacity-90 transition"
                           style="background-color: {{ $primaryColor }}">
                            Lihat Apartemen
                        </a>
                    </div>
                </div>

                <!-- Menu -->
                <div>
                    <h3 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Menu</h3>
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
                    <h3 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Kontak</h3>
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
                </div>
            </div>

            <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                <p>Sewa apartemen harian &amp; transit</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
