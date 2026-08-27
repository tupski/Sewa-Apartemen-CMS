@extends('layouts.frontend')

@section('content')
    @php
        $primaryColor   = \App\Services\SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');

        // Active filter count untuk badge di tombol mobile
        $activeFilterCount = collect([
            $typeFilter,
            $unitTypeFilter,
            $cityFilter,
            $priceMin,
            $priceMax,
        ])->filter()->count() + count($amenityFilter);

        // Sort options
        $sortOptions = [
            'default'  => __('prop.sort_default'),
            'newest'   => __('prop.sort_newest'),
            'oldest'   => __('prop.sort_oldest'),
            'featured' => __('prop.sort_featured'),
        ];

        // Booking type options untuk filter durasi
        $typeOptions = [
            ''        => __('prop.all_durations'),
            'transit' => __('prop.transit_hour'),
            'daily'   => __('prop.daily'),
            'weekly'  => __('prop.weekly'),
            'monthly' => __('prop.monthly'),
        ];

        $unitTypeOptions = \App\Models\Property::UNIT_TYPES;
    @endphp

    {{-- ── Page Header ─────────────────────────────────────────────── --}}
    <section class="py-10 md:py-14 text-white"
             style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
        <div class="absolute inset-0 opacity-10 pointer-events-none"
             style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 24px 24px;"
             aria-hidden="true"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl md:text-4xl font-bold mb-1">{{ __('prop.title') }}</h1>
            <p class="text-white/80 text-sm md:text-base">{{ __('prop.subtitle') }}</p>

        </div>
    </section>

    {{-- ── Main Layout ─────────────────────────────────────────────── --}}
    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen"
         x-data="listingPage()"
         x-init="init()">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- Mobile: Filter + Sort bar --}}
            <div id="mobile-filter-bar" class="flex lg:hidden items-center gap-3 mb-5">

                {{-- Sort dropdown mobile --}}
                <form action="{{ route('properties.public.index') }}" method="GET" class="flex-1">
                    @foreach(request()->except('sort') as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $vi)<input type="hidden" name="{{ $k }}[]" value="{{ $vi }}">@endforeach
                        @else
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <label for="sort-mobile" class="sr-only">{{ __('prop.sort_label') }}</label>
                    <select id="sort-mobile" name="sort" onchange="this.form.submit()"
                            class="w-full h-[42px] px-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:outline-none focus:ring-2 appearance-none pr-8"
                            style="background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1rem;">
                        @foreach($sortOptions as $val => $label)
                            <option value="{{ $val }}" @selected($sort === $val)>{{ __('prop.sort_label') }}: {{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Desktop layout: sidebar kiri + listing kanan --}}
            <div class="flex gap-8 items-start">

                {{-- ══ SIDEBAR FILTER (Desktop) ═══════════════════════════════════ --}}
                <aside id="filter-section" class="hidden lg:block w-72 xl:w-80 shrink-0">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 sticky top-8 max-h-[calc(100vh-4rem)] overflow-y-auto">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('prop.filter_title') }}</h2>
                            @if($activeFilterCount > 0)
                                <a href="{{ route('properties.public.index', array_filter(['search' => request('search'), 'sort' => $sort !== 'default' ? $sort : null])) }}"
                                   class="text-xs font-medium hover:opacity-80 transition"
                                   style="color: {{ $primaryColor }}">
                                    {{ __('prop.filter_reset_all') }}
                                </a>
                            @endif
                        </div>

                        <form action="{{ route('properties.public.index') }}" method="GET" id="filter-form-desktop">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            @if($sort !== 'default')
                                <input type="hidden" name="sort" value="{{ $sort }}">
                            @endif

                            <div class="px-5 py-4 space-y-6 max-h-[calc(100vh-16rem)] overflow-y-auto">

                                {{-- Search --}}
                                <fieldset>
                                    <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                                        {{ __('prop.search_label') }}
                                    </legend>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" aria-hidden="true">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                        </span>
                                        <input type="text" name="search" value="{{ request('search') }}"
                                               placeholder="{{ __('prop.search_placeholder') }}"
                                               class="w-full h-9 pl-10 pr-3 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800">
                                    </div>
                                </fieldset>

                                {{-- Durasi Sewa --}}
                                <fieldset>
                                    <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                                        {{ __('prop.filter_duration') }}
                                    </legend>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($typeOptions as $typeVal => $typeLabel)
                                            @php $isActive = ($typeFilter === $typeVal) || ($typeVal === '' && !$typeFilter); @endphp
                                            <label class="cursor-pointer">
                                                <input type="radio" name="type" value="{{ $typeVal }}"
                                                       class="sr-only peer" @checked($isActive)>
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border transition
                                                    peer-checked:text-white peer-checked:border-transparent
                                                    text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    style="{{ $isActive ? 'background-color:'.$primaryColor.'; border-color:'.$primaryColor.'; color:#fff;' : '' }}">
                                                    {{ $typeLabel }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                {{-- Harga --}}
                                <fieldset>
                                    <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                                        {{ __('prop.filter_price') }}
                                    </legend>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label for="price_min_d" class="sr-only">{{ __('prop.filter_price_min') }}</label>
                                            <input type="hidden" id="price_min_d" name="price_min" value="{{ $priceMin }}">
                                            <div class="flex items-center h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 overflow-hidden focus-within:ring-2 focus-within:ring-blue-200 dark:focus-within:ring-blue-800">
                                                <span class="px-2 text-xs text-gray-400 bg-gray-100 dark:bg-gray-600/60 border-r border-gray-200 dark:border-gray-600 h-full flex items-center select-none shrink-0">Rp</span>
                                                <input type="text" id="price_min_d_display"
                                                       data-price-hidden="price_min_d"
                                                       value="{{ $priceMin ? number_format($priceMin, 0, ',', '.') : '' }}"
                                                       placeholder="Min"
                                                       class="w-full h-full px-2 text-sm bg-transparent text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label for="price_max_d" class="sr-only">{{ __('prop.filter_price_max') }}</label>
                                            <input type="hidden" id="price_max_d" name="price_max" value="{{ $priceMax }}">
                                            <div class="flex items-center h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 overflow-hidden focus-within:ring-2 focus-within:ring-blue-200 dark:focus-within:ring-blue-800">
                                                <span class="px-2 text-xs text-gray-400 bg-gray-100 dark:bg-gray-600/60 border-r border-gray-200 dark:border-gray-600 h-full flex items-center select-none shrink-0">Rp</span>
                                                <input type="text" id="price_max_d_display"
                                                       data-price-hidden="price_max_d"
                                                       value="{{ $priceMax ? number_format($priceMax, 0, ',', '.') : '' }}"
                                                       placeholder="Max"
                                                       class="w-full h-full px-2 text-sm bg-transparent text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none">
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                                {{-- Lokasi --}}
                                @if($availableCities->isNotEmpty())
                                <fieldset>
                                    <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                                        {{ __('prop.filter_location') }}
                                    </legend>
                                    <label for="city_d" class="sr-only">{{ __('prop.filter_location') }}</label>
                                    <select id="city_d" name="city"
                                            class="w-full h-9 px-3 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 appearance-none"
                                            style="background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1rem;">
                                        <option value="">{{ __('prop.filter_all_cities') }}</option>
                                        @foreach($availableCities as $city)
                                            <option value="{{ $city }}" @selected($cityFilter === $city)>{{ $city }}</option>
                                        @endforeach
                                    </select>
                                </fieldset>
                                @endif

                                {{-- Tipe Unit --}}
                                <fieldset>
                                    <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                                        {{ __('prop.filter_unit_type') }}
                                    </legend>
                                    <div class="space-y-2">
                                        @foreach($unitTypeOptions as $typeKey => $typeLabel)
                                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                                <input type="radio" name="unit_type" value="{{ $typeKey }}"
                                                       @checked($unitTypeFilter === $typeKey)
                                                       class="w-4 h-4 rounded text-blue-600 border-gray-300 dark:border-gray-600 focus:ring-2">
                                                <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100 transition">
                                                    {{ $typeLabel }}
                                                </span>
                                            </label>
                                        @endforeach
                                        @if($unitTypeFilter)
                                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                                <input type="radio" name="unit_type" value=""
                                                           @checked(!$unitTypeFilter)
                                                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 focus:ring-2">
                                                <span class="text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200 transition">
                                                    {{ __('prop.all_types') }}
                                                </span>
                                            </label>
                                        @endif
                                    </div>
                                </fieldset>

                                {{-- Fasilitas --}}
                                @if($availableAmenities->isNotEmpty())
                                <fieldset>
                                    <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                                        {{ __('prop.filter_amenities') }}
                                    </legend>
                                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                        @foreach($availableAmenities as $amenity)
                                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                                <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                                       @checked(in_array((string)$amenity->id, array_map('strval', $amenityFilter)))
                                                       class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-2">
                                                <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100 transition flex items-center gap-1">
                                                    @if($amenity->icon_class)
                                                        <i class="{{ $amenity->icon_class }}" aria-hidden="true"></i>
                                                    @endif
                                                    {{ $amenity->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                @endif

                            </div>{{-- end scrollable --}}

                            {{-- Filter actions --}}
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 flex gap-2">
                                <button type="submit"
                                        class="flex-1 h-9 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition focus:outline-none focus-visible:ring-2"
                                        style="background-color: {{ $primaryColor }}">
                                    {{ __('prop.filter_apply') }}
                                </button>
                                <a href="{{ route('properties.public.index', array_filter(['search' => request('search'), 'sort' => $sort !== 'default' ? $sort : null])) }}"
                                   class="flex-1 h-9 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                                    {{ __('prop.filter_reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </aside>

                {{-- ══ LISTING AREA ════════════════════════════════════════════════ --}}
                <div class="flex-1 min-w-0">

                    {{-- Listing header: count + sort --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ __('prop.found', ['count' => $properties->total()]) }}
                            </p>

                            {{-- Active filter chips --}}
                            @php
                                $chips = [];
                                if($typeFilter) $chips[] = ['key' => 'type', 'label' => $typeOptions[$typeFilter] ?? $typeFilter];
                                if($unitTypeFilter) $chips[] = ['key' => 'unit_type', 'label' => $unitTypeOptions[$unitTypeFilter] ?? $unitTypeFilter];
                                if($cityFilter) $chips[] = ['key' => 'city', 'label' => $cityFilter];
                                if($priceMin) $chips[] = ['key' => 'price_min', 'label' => '≥ Rp '.number_format($priceMin,0,',','.')];
                                if($priceMax) $chips[] = ['key' => 'price_max', 'label' => '≤ Rp '.number_format($priceMax,0,',','.')];
                                foreach($amenityFilter as $aid) {
                                    $am = $availableAmenities->firstWhere('id', $aid);
                                    if($am) $chips[] = ['key' => 'amenity_'.$aid, 'label' => $am->name, 'amenity_id' => $aid];
                                }
                            @endphp
                            @if(!empty($chips))
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($chips as $chip)
                                        @php
                                            // Build URL tanpa chip ini
                                            $removeParams = request()->except([]);
                                            if(isset($chip['amenity_id'])) {
                                                $removeParams['amenities'] = array_values(array_filter($amenityFilter, fn($x) => (string)$x !== (string)$chip['amenity_id']));
                                            } else {
                                                unset($removeParams[$chip['key']]);
                                            }
                                        @endphp
                                        <a href="{{ route('properties.public.index', array_filter($removeParams, fn($v) => $v !== null && $v !== '')) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition"
                                           aria-label="Hapus filter {{ $chip['label'] }}">
                                            {{ $chip['label'] }}
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                        </a>
                                    @endforeach
                                    @if(count($chips) >= 2)
                                        <a href="{{ route('properties.public.index', array_filter(['search' => request('search'), 'sort' => $sort !== 'default' ? $sort : null])) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition underline underline-offset-2">
                                            {{ __('prop.filter_reset_all') }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Sort dropdown desktop --}}
                        <form action="{{ route('properties.public.index') }}" method="GET" class="hidden sm:block shrink-0">
                            @foreach(request()->except('sort') as $k => $v)
                                @if(is_array($v))
                                    @foreach($v as $vi)<input type="hidden" name="{{ $k }}[]" value="{{ $vi }}">@endforeach
                                @else
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <div class="flex items-center gap-2">
                                <label for="sort-desktop" class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ __('prop.sort_label') }}:</label>
                                <select id="sort-desktop" name="sort" onchange="this.form.submit()"
                                        class="h-9 px-3 pr-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:outline-none focus:ring-2 appearance-none"
                                        style="background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1rem;">
                                    @foreach($sortOptions as $val => $label)
                                        <option value="{{ $val }}" @selected($sort === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    {{-- ── Cards Grid ──────────────────────────────────────────── --}}
                    @if ($properties->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 xl:gap-6">
                            @foreach ($properties as $property)
                                @include('properties._card', ['property' => $property, 'primaryColor' => $primaryColor])
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-10">
                            {{ $properties->links() }}
                        </div>

                    @else
                        {{-- Empty State --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 sm:p-16 text-center">
                            <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center text-3xl" aria-hidden="true">
                                🏢
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">{{ __('prop.empty_title') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">{{ __('prop.empty_desc') }}</p>
                            <a href="{{ route('properties.public.index', array_filter(['search' => request('search')])) }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition"
                               style="background-color: {{ $primaryColor }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                {{ __('prop.filter_reset_all') }}
                            </a>
                        </div>
                    @endif
                </div>{{-- end listing --}}
            </div>{{-- end flex row --}}
        </div>{{-- end max-w container --}}

        {{-- ══ MOBILE FILTER TRIGGER (Fixed bottom-center pill) ════════════ --}}
        <button @click="filterOpen = true"
                type="button"
                class="fixed bottom-4 left-1/2 -translate-x-1/2 z-30 flex items-center gap-2 px-6 py-3 rounded-full bg-red-600 border border-red-600 text-sm font-semibold text-white shadow-lg hover:bg-red-700 hover:border-red-700 active:bg-red-800 transition lg:hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900"
                aria-label="{{ __('prop.filter') }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" d="M3 6h18M7 12h10M11 18h2"/>
            </svg>
            {{ __('prop.filter') }}
            @if($activeFilterCount > 0)
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white rounded-full"
                      style="background-color: {{ $primaryColor }}">{{ $activeFilterCount }}</span>
            @endif
        </button>

        {{-- ══ MOBILE FILTER DRAWER ══════════════════════════════════════════ --}}
        {{-- Backdrop --}}
        <div x-show="filterOpen"
             x-cloak
             x-transition:enter="transition-opacity duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="filterOpen = false"
             class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden"
             aria-hidden="true">
        </div>

        {{-- Drawer panel --}}
        <div x-show="filterOpen"
             x-cloak
             x-transition:enter="transition-transform duration-300 ease-out"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition-transform duration-200 ease-in"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             role="dialog" aria-modal="true" aria-label="{{ __('prop.filter_title') }}"
             class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-xl z-50 lg:hidden bg-white dark:bg-gray-900 rounded-t-3xl shadow-2xl max-h-[90dvh] flex flex-col">

            {{-- Drag handle --}}
            <div class="flex justify-center pt-3 pb-1">
                <span class="block w-10 h-1 rounded-full bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>
            </div>

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('prop.filter_title') }}</h2>
                <button @click="filterOpen = false" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        aria-label="Tutup filter">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Scrollable body --}}
            <form action="{{ route('properties.public.index') }}" method="GET" id="filter-form-mobile" class="flex-1 overflow-y-auto">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if($sort !== 'default')
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif

                <div class="px-5 py-4 space-y-6">

                    {{-- Search --}}
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('prop.search_label') }}</legend>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="{{ __('prop.search_placeholder') }}"
                                   class="w-full h-10 pl-10 pr-3 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800">
                        </div>
                    </fieldset>

                    {{-- Durasi --}}
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('prop.filter_duration') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach($typeOptions as $typeVal => $typeLabel)
                                @php $isActive = ($typeFilter === $typeVal) || ($typeVal === '' && !$typeFilter); @endphp
                                <label class="cursor-pointer">
                                    <input type="radio" name="type" value="{{ $typeVal }}" class="sr-only peer" @checked($isActive)>
                                    <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium border transition
                                        peer-checked:text-white peer-checked:border-transparent
                                        text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800"
                                          style="{{ $isActive ? 'background-color:'.$primaryColor.'; border-color:'.$primaryColor.'; color:#fff;' : '' }}">
                                        {{ $typeLabel }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Harga --}}
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('prop.filter_price') }}</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="price_min_m" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('prop.filter_price_min') }}</label>
                                <input type="hidden" id="price_min_m" name="price_min" value="{{ $priceMin }}">
                                <div class="flex items-center h-11 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 overflow-hidden focus-within:ring-2 focus-within:ring-blue-200 dark:focus-within:ring-blue-800">
                                    <span class="px-2.5 text-xs text-gray-400 bg-gray-100 dark:bg-gray-700/60 border-r border-gray-200 dark:border-gray-600 h-full flex items-center select-none shrink-0">Rp</span>
                                    <input type="text" id="price_min_m_display"
                                           data-price-hidden="price_min_m"
                                           value="{{ $priceMin ? number_format($priceMin, 0, ',', '.') : '' }}"
                                           placeholder="0"
                                           class="w-full h-full px-2.5 text-sm bg-transparent text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label for="price_max_m" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('prop.filter_price_max') }}</label>
                                <input type="hidden" id="price_max_m" name="price_max" value="{{ $priceMax }}">
                                <div class="flex items-center h-11 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 overflow-hidden focus-within:ring-2 focus-within:ring-blue-200 dark:focus-within:ring-blue-800">
                                    <span class="px-2.5 text-xs text-gray-400 bg-gray-100 dark:bg-gray-700/60 border-r border-gray-200 dark:border-gray-600 h-full flex items-center select-none shrink-0">Rp</span>
                                    <input type="text" id="price_max_m_display"
                                           data-price-hidden="price_max_m"
                                           value="{{ $priceMax ? number_format($priceMax, 0, ',', '.') : '' }}"
                                           placeholder="Max"
                                           class="w-full h-full px-2.5 text-sm bg-transparent text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Lokasi --}}
                    @if($availableCities->isNotEmpty())
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('prop.filter_location') }}</legend>
                        <label for="city_m" class="sr-only">{{ __('prop.filter_location') }}</label>
                        <select id="city_m" name="city"
                                class="w-full h-11 px-3 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 appearance-none"
                                style="background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem;">
                            <option value="">{{ __('prop.filter_all_cities') }}</option>
                            @foreach($availableCities as $city)
                                <option value="{{ $city }}" @selected($cityFilter === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </fieldset>
                    @endif

                    {{-- Tipe Unit --}}
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('prop.filter_unit_type') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="unit_type" value="" class="sr-only peer" @checked(!$unitTypeFilter)>
                                <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium border transition
                                    peer-checked:text-white peer-checked:border-transparent
                                    text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800"
                                      style="{{ !$unitTypeFilter ? 'background-color:'.$primaryColor.'; border-color:'.$primaryColor.'; color:#fff;' : '' }}">
                                    {{ __('prop.all_types') }}
                                </span>
                            </label>
                            @foreach($unitTypeOptions as $typeKey => $typeLabel)
                                <label class="cursor-pointer">
                                    <input type="radio" name="unit_type" value="{{ $typeKey }}" class="sr-only peer" @checked($unitTypeFilter === $typeKey)>
                                    <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium border transition
                                        peer-checked:text-white peer-checked:border-transparent
                                        text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800"
                                          style="{{ $unitTypeFilter === $typeKey ? 'background-color:'.$primaryColor.'; border-color:'.$primaryColor.'; color:#fff;' : '' }}">
                                        {{ $typeLabel }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Fasilitas --}}
                    @if($availableAmenities->isNotEmpty())
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('prop.filter_amenities') }}</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($availableAmenities as $amenity)
                                <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                           @checked(in_array((string)$amenity->id, array_map('strval', $amenityFilter)))
                                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-2">
                                    <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-1 truncate">
                                        @if($amenity->icon_class)<i class="{{ $amenity->icon_class }}" aria-hidden="true"></i>@endif
                                        {{ $amenity->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @endif

                </div>{{-- end scrollable body --}}

                {{-- Sticky footer actions --}}
                <div class="sticky bottom-0 px-5 py-4 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 flex gap-3">
                    <a href="{{ route('properties.public.index', array_filter(['search' => request('search'), 'sort' => $sort !== 'default' ? $sort : null])) }}"
                       class="flex-1 h-12 flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        {{ __('prop.filter_reset') }}
                    </a>
                    <button type="submit"
                            class="flex-1 h-12 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition focus:outline-none focus-visible:ring-2"
                            style="background-color: {{ $primaryColor }}">
                        {{ __('prop.filter_apply') }}
                    </button>
                </div>
            </form>
        </div>
    </div>{{-- end x-data --}}
@endsection

@push('scripts')
<script>
function listingPage() {
    return {
        filterOpen: false,

        init() {
            // Tutup drawer dengan Escape
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.filterOpen) {
                    this.filterOpen = false;
                }
            });

            // Mobile sticky filter: hide when scrolled past original position
            const mobileBar = document.getElementById('mobile-filter-bar');
            const filterSection = document.getElementById('filter-section');

            if (mobileBar && filterSection && window.innerWidth < 1024) {
                const observer = new IntersectionObserver(
                    ([entry]) => {
                        // Hide bar when filter section is visible, show when scrolled past
                        if (entry.isIntersecting) {
                            mobileBar.style.transform = 'translateY(100%)';
                        } else {
                            mobileBar.style.transform = 'translateY(0)';
                        }
                    },
                    { threshold: 0, rootMargin: '0px' }
                );

                observer.observe(filterSection);
            }
        },
    };
}
</script>

<script>
// ── Price input: Rp prefix + thousand-separator formatting ───────────────────
(function () {
    function formatThousands(raw) {
        // Strip everything except digits
        var digits = raw.replace(/\D/g, '');
        if (!digits) return '';
        // Format with dots as thousand separators (Indonesian: 1.000.000)
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function initPriceInput(displayId) {
        var display = document.getElementById(displayId);
        if (!display) return;

        var hiddenId = display.getAttribute('data-price-hidden');
        var hidden   = document.getElementById(hiddenId);
        if (!hidden) return;

        display.addEventListener('input', function () {
            var digits = display.value.replace(/\D/g, '');
            // Update the visible formatted display
            display.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            // Keep raw numeric value in the hidden field for form submission
            hidden.value = digits;
        });

        // Also handle paste
        display.addEventListener('paste', function (e) {
            e.preventDefault();
            var pasted = (e.clipboardData || window.clipboardData).getData('text');
            var digits = pasted.replace(/\D/g, '');
            display.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            hidden.value  = digits;
        });
    }

    // Desktop price inputs
    initPriceInput('price_min_d_display');
    initPriceInput('price_max_d_display');

    // Mobile price inputs
    initPriceInput('price_min_m_display');
    initPriceInput('price_max_m_display');
})();
</script>
@endpush
