@extends('layouts.frontend')

@section('content')
    <!-- Hero -->
    <section class="relative overflow-hidden"
             style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 24px 24px;" aria-hidden="true"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
            <div class="max-w-3xl">
                <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight mb-4">
                    {{ $heroTitle ?: ($tagline ?: __('home.tagline')) }}
                </h1>
                @if ($heroSubtitle)
                    <p class="text-lg md:text-xl text-white/90 mb-8 leading-relaxed">{{ $heroSubtitle }}</p>
                @endif

                <!-- Search -->
                <form action="{{ route('properties.public.index') }}" method="GET"
                      class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 sm:p-5 flex flex-col md:flex-row gap-3 max-w-4xl">
                    <x-search-input :label="__('home.search_name')"
                                    :placeholder="__('home.search_placeholder')"
                                    :value="request('search')"
                                    :additional-classes="'flex-1'"
                                    input-classes="w-full px-5 py-3.5 text-base rounded-xl border border-gray-200 focus:outline-none focus:ring-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                    <button type="submit"
                            class="px-8 py-3.5 rounded-xl text-white font-semibold hover:opacity-90 transition"
                            style="background-color: {{ $primaryColor }}">
                        {{ __('home.search') }}
                    </button>
                </form>
            </div>

            <!-- Stats -->
            <div class="mt-14 grid grid-cols-3 gap-6 max-w-xl">
                <div>
                    <p class="text-3xl md:text-4xl font-bold text-white">{{ number_format($stats['properties']) }}</p>
                    <p class="text-sm text-white/80 mt-1">{{ __('home.stats_apartments') }}</p>
                </div>
                <div>
                    <p class="text-3xl md:text-4xl font-bold text-white">{{ number_format($stats['units']) }}</p>
                    <p class="text-sm text-white/80 mt-1">{{ __('home.stats_units') }}</p>
                </div>
                <div>
                    <p class="text-3xl md:text-4xl font-bold text-white">{{ number_format($stats['cities']) }}</p>
                    <p class="text-sm text-white/80 mt-1">{{ __('home.stats_cities') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Properties -->
    <section class="py-16 md:py-20 bg-slate-50 dark:bg-gray-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ __('home.featured') }}</h2>
                    <p class="text-gray-600 mt-2 dark:text-gray-400">{{ __('home.featured_sub') }}</p>
                </div>
                <a href="{{ route('properties.public.index') }}"
                   class="hidden md:inline-flex items-center text-sm font-semibold hover:opacity-80 transition"
                   style="color: {{ $primaryColor }}">
                    {{ __('home.view_all') }}
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>

            @if ($properties->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($properties as $property)
                        <a href="{{ route('properties.public.show', $property->slug) }}"
                           class="group property-card overflow-hidden dark:!bg-gray-800 dark:!shadow-gray-900/30">
                            <div class="relative aspect-[4/3] bg-gray-200">
                                @if ($property->featuredImage)
                                    <img src="{{ $property->featuredImage->url }}" alt="{{ $property->name }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-blue-400 bg-gradient-to-br from-blue-100 to-indigo-200">
                                        <i data-lucide="building-2" class="w-14 h-14"></i>
                                    </div>
                                @endif
                                @if ($property->is_featured)
                                    <span class="absolute top-3 left-3 bg-white/95 text-xs font-bold px-3 py-1 rounded-full shadow" style="color: {{ $primaryColor }}">
                                        {{ __('home.featured_badge') }}
                                    </span>
                                @endif
                                @php
                                    $typeBadge = $property->unit_types[0] ?? null;
                                    $amenityBadges = $property->amenities->take(3);
                                @endphp
                                @if ($typeBadge || $amenityBadges->isNotEmpty())
                                    <div class="absolute bottom-3 left-3 flex flex-wrap gap-1.5">
                                        @if ($typeBadge)
                                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-black/60 text-white backdrop-blur-sm">{{ $property->typeLabel($typeBadge) }}</span>
                                        @endif
                                        @foreach ($amenityBadges as $amenity)
                                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-white/90 text-gray-800 backdrop-blur-sm">{{ $amenity->icon ? $amenity->icon . ' ' : '' }}{{ $amenity->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:opacity-80 transition">{{ $property->name }}</h3>
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $property->city ?: 'Tangerang' }}{{ $property->province ? ', ' . $property->province : '' }}
                                </div>
                                @php
                                    $cheapest = $property->cheapestNight();
                                @endphp
                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    @if ($cheapest)
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('home.from') }}</p>
                                            <p class="text-lg font-bold" style="color: {{ $primaryColor }}">
                                                Rp {{ number_format((float) $cheapest, 0, ',', '.') }}<span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('home.per_night') }}</span>
                                            </p>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">{{ __('home.contact_for_price') }}</span>
                                    @endif
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full text-white group-hover:translate-x-1 transition"
                                          style="background-color: {{ $primaryColor }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-10 text-center md:hidden">
                    <a href="{{ route('properties.public.index') }}"
                       class="inline-flex items-center px-8 py-3 rounded-full text-white font-semibold hover:opacity-90 transition"
                       style="background-color: {{ $primaryColor }}">
                        {{ __('home.view_all_apartments') }}
                    </a>
                </div>
            @else
                <div class="bg-white rounded-2xl shadow-sm p-12 text-center dark:bg-gray-800">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('home.empty') }}</p>
                </div>
            @endif
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-16 md:py-20 bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ $featuresTitle ?: __('home.why_title') }}</h2>
                <p class="text-gray-600 mt-2 max-w-2xl mx-auto dark:text-gray-400">{{ $featuresSubtitle ?: __('home.why_sub') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4" style="background-color: {{ $primaryColor }}18">
                        <svg class="w-8 h-8" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 dark:text-white">{{ __('home.why_daily') }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed dark:text-gray-400">{{ __('home.why_daily_desc') }}</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4" style="background-color: {{ $primaryColor }}18">
                        <svg class="w-8 h-8" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 dark:text-white">{{ __('home.why_safe') }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed dark:text-gray-400">{{ __('home.why_safe_desc') }}</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4" style="background-color: {{ $primaryColor }}18">
                        <svg class="w-8 h-8" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 dark:text-white">{{ __('home.why_price') }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed dark:text-gray-400">{{ __('home.why_price_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Posts -->
    @if ($posts->isNotEmpty())
        <section class="py-16 md:py-20 bg-gray-50 dark:bg-gray-800/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between mb-10">
                    <div>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ __('home.blog_title') }}</h2>
                        <p class="text-gray-600 mt-2 dark:text-gray-400">{{ __('home.blog_sub') }}</p>
                    </div>
                    <a href="{{ route('blog.index') }}" class="hidden md:inline-flex items-center text-sm font-semibold hover:opacity-80 transition" style="color: {{ $primaryColor }}">
                        {{ __('home.all_articles') }}
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach ($posts as $post)
                        <a href="{{ route('blog.show', $post->slug) }}" class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition">
                            <div class="relative aspect-[16/9] bg-gray-200">
                                @if ($post->featured_image)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) }}"
                                         alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300" style="background: linear-gradient(135deg, {{ $secondaryColor }}22, {{ $primaryColor }}22);">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-6">
                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    @if ($post->category)
                                        <span class="px-2 py-1 rounded-full font-medium" style="background-color: {{ $primaryColor }}18; color: {{ $primaryColor }}">{{ $post->category->name }}</span>
                                    @endif
                                    <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:opacity-80 transition leading-snug">{{ $post->title }}</h3>
                                @if ($post->excerpt)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 110) }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- CTA / Contact -->
    <section class="py-16 md:py-20" style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">{{ $ctaTitle ?: __('home.cta_title') }}</h2>
            <p class="text-white/90 mb-8">{{ $ctaText ?: __('home.cta_sub') }}</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                @php
                    $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
                @endphp
                @if ($whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center px-8 py-3.5 rounded-full bg-white dark:bg-gray-800 font-semibold hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        {{ __('home.chat_whatsapp') }}
                    </a>
                @endif
                <a href="{{ $ctaButtonUrl ?: route('properties.public.index') }}"
                   class="inline-flex items-center px-8 py-3.5 rounded-full font-semibold text-white border-2 border-white hover:bg-white hover:text-gray-900 transition">
                    {{ $ctaButtonLabel ?: __('home.explore') }}
                </a>
            </div>
        </div>
    </section>
@endsection
