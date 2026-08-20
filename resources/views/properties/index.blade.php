@extends('layouts.frontend')

@section('content')
    @php
        $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
    @endphp

    <!-- Page Header -->
    <section class="py-12 md:py-16 text-white"
             style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl md:text-4xl font-bold">{{ __('prop.title') }}</h1>
            <p class="text-white/90 mt-2">{{ __('prop.subtitle') }}</p>
        </div>
    </section>

    <section class="py-10 bg-gray-50 dark:bg-gray-800/50 min-h-[50vh]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Search / Filters -->
            <form action="{{ route('properties.public.index') }}" method="GET"
                  class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-4 flex flex-col md:flex-row gap-3 mb-4">
                <x-search-input :label="__('home.search_name')"
                                :placeholder="__('home.search_placeholder')"
                                :value="request('search')"
                                :additional-classes="'flex-1'"
                                input-classes="w-full px-5 py-3.5 text-base rounded-xl border border-gray-200 focus:outline-none focus:ring-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                {{-- preserve type filter when searching --}}
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <button type="submit"
                        class="px-8 py-3.5 rounded-xl text-white font-semibold hover:opacity-90 transition"
                        style="background-color: {{ $primaryColor }}">
                    {{ __('home.search') }}
                </button>
                @if (request('search') || request('type'))
                    <a href="{{ route('properties.public.index') }}"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm font-medium dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('prop.reset') }}
                    </a>
                @endif
            </form>

            <!-- Booking Type Filter Pills -->
            @php
                $currentType = $typeFilter ?? request('type');
                $typeOptions = [
                    ''        => 'Semua',
                    'transit' => 'Transit Jam',
                    'daily'   => 'Harian',
                    'weekly'  => 'Mingguan',
                    'monthly' => 'Bulanan',
                ];
            @endphp
            <div class="flex flex-wrap gap-2 mb-8">
                @foreach($typeOptions as $typeVal => $typeLabel)
                    @php
                        $isActive = ($currentType === $typeVal) || ($typeVal === '' && !$currentType);
                        $url = route('properties.public.index', array_filter(['type' => $typeVal ?: null, 'search' => request('search')]));
                    @endphp
                    <a href="{{ $url }}"
                       class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition border
                              {{ $isActive
                                  ? 'text-white border-transparent'
                                  : 'text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300' }}"
                       style="{{ $isActive ? 'background-color: ' . $primaryColor . '; border-color: ' . $primaryColor . ';' : '' }}"
                       aria-current="{{ $isActive ? 'page' : 'false' }}">
                        {{ $typeLabel }}
                    </a>
                @endforeach
            </div>

            @if ($properties->isNotEmpty())
                <p class="text-sm text-gray-500 mb-6 dark:text-gray-400">
                    {{ __('prop.found', ['count' => $properties->total()]) }}
                </p>

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
                                    $lowestPx = $property->lowestPrice();
                                    // Determine "per unit" label for the lowest price type
                                    $lowestLabel = '';
                                    if ($lowestPx) {
                                        foreach (($property->unit_types ?? []) as $ut) {
                                            $p = $property->prices[$ut] ?? [];
                                            foreach (['t3_wd','t6_wd','t9_wd','t12_wd','t24_wd','t3_we','t6_we','t9_we','t12_we','t24_we'] as $tk) {
                                                if (isset($p[$tk]) && (float)$p[$tk] === $lowestPx) { $lowestLabel = '/jam'; break 2; }
                                            }
                                            if (isset($p['weekly']) && (float)$p['weekly'] === $lowestPx) { $lowestLabel = '/minggu'; break; }
                                            if (isset($p['monthly']) && (float)$p['monthly'] === $lowestPx) { $lowestLabel = '/bulan'; break; }
                                        }
                                        if (!$lowestLabel) $lowestLabel = '/malam';
                                    }
                                @endphp
                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    @if ($lowestPx)
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Mulai dari</p>
                                            <p class="text-lg font-bold" style="color: {{ $primaryColor }}">
                                                Rp {{ number_format($lowestPx, 0, ',', '.') }}<span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $lowestLabel }}</span>
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

                <div class="mt-10">
                    {{ $properties->links() }}
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-16 text-center">
                    <p class="text-4xl mb-4">🏢</p>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('prop.not_found') }}</p>
                    <a href="{{ route('properties.public.index') }}" class="inline-block mt-4 text-sm font-semibold hover:opacity-80" style="color: {{ $primaryColor }}">
                        {{ __('prop.view_all') }}
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection
