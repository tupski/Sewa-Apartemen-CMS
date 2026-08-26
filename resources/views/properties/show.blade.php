@extends('layouts.frontend')

@php
    $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
    $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
    $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
    $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
    $displayMode = \App\Services\SettingsService::get('booking_display_mode', 'both');
    $whatsappNumber = \App\Services\SettingsService::get('whatsapp_default', '');
    $photos = $property->photos;
    // Flat list of photos with category info (used by the lightbox).
    $photoGallery = $photos->map(function ($p) {
        return [
            'url'      => $p->media?->url,
            'category' => $p->category ?: 'Other',
            'name'     => $p->media?->alt_text ?: $p->media?->name ?: ($p->media?->original_name ?? ''),
        ];
    })->filter(function ($p) { return !empty($p['url']); })->values();
    $allPhotoUrls = $photoGallery->pluck('url')->values();
    $firstPhoto = $allPhotoUrls[0] ?? null;
    $restPhotos = $allPhotoUrls->slice(1)->take(6)->values();
    $hasBooking = !empty($property->unit_types) && ($property->hasBookingType('transit') || $property->hasBookingType('daily') || $property->hasBookingType('weekly') || $property->hasBookingType('monthly'));
    // When pricing_only mode, suppress the booking form even if prices exist
    $showBookingForm = $hasBooking && $displayMode !== 'pricing_only';
    $showPricingTable = $hasBooking && $displayMode !== 'form_only';
    $faqs = $property->faqs();
    // $nearbyPlacesWithDistance is injected by the controller with distance_formatted computed via Haversine.
    // Fall back to grouping raw nearby_places if the variable is missing (e.g. draft preview).
    $nearbyPlacesWithDistance = $nearbyPlacesWithDistance ?? collect($property->nearby_places ?? [])->map(fn($p) => array_merge($p, ['distance_formatted' => null, 'distance_m' => null]))->all();
    // Re-group the enriched places by category for display.
    $nearbyGroups = [];
    foreach ($nearbyPlacesWithDistance as $place) {
        $cat = $place['category'] ?? 'Others';
        if (!array_key_exists($cat, \App\Models\Property::NEARBY_CATEGORIES)) {
            $cat = 'Others';
        }
        $nearbyGroups[$cat][] = $place;
    }
    $nearbyGroups = array_filter($nearbyGroups, fn($items) => count($items) > 0);
    $hasMap = $property->latitude && $property->longitude;
    $nearbyWithCoords = array_values(array_filter($nearbyPlacesWithDistance, fn($p) => !empty($p['lat']) && !empty($p['lng'])));
@endphp

@if($hasMap ?? false)
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #property-detail-map { z-index: 1; }
    .leaflet-container { border-radius: 0; }
</style>
@endpush
@endif

@section('content')
    <!-- ============ GALLERY HEADER (Traveloka style) ============ -->
    @if ($photos->isNotEmpty())
        <section class="bg-gray-100 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <!-- Desktop: big left photo (full height) + all remaining photos right (3 per row) -->
                <div class="relative md:grid md:grid-cols-4 gap-2 rounded-2xl overflow-hidden">
                    {{-- Main photo: aspect-[4/3] on mobile, spans full grid height on desktop --}}
                    <button type="button" data-photo="0" class="relative aspect-[4/3] md:aspect-auto md:h-auto md:[grid-row:1/-1] group overflow-hidden text-left">
                        <img src="{{ $firstPhoto }}" alt="{{ $property->name }}" class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-300">
                    </button>
                    <!-- Overlay: view all photos -->
                    <button type="button" id="gal-open" class="absolute bottom-4 right-4 z-10 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/95 text-sm font-semibold text-gray-900 shadow hover:bg-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ __('prop.view_all_photos') }} ({{ $allPhotoUrls->count() }})
                    </button>
                    {{-- Thumbnail photos: uniform aspect-[4/3] so portrait/landscape all match --}}
                    @foreach ($restPhotos as $i => $url)
                        <button type="button" data-photo="{{ $i + 1 }}" class="relative hidden md:block aspect-[4/3] group overflow-hidden">
                            <img src="{{ $url }}" alt="{{ $property->name }}" loading="lazy" class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-300">
                        </button>
                    @endforeach
                </div>
                <!-- Mobile: first 3 photos row — aspect-square thumbnails for uniform grid -->
                <div class="grid grid-cols-3 gap-2 md:hidden mt-2">
                    @foreach ($restPhotos->take(3) as $i => $url)
                        <button type="button" data-photo="{{ $i + 1 }}" class="relative group overflow-hidden rounded-xl aspect-square">
                            <img src="{{ $url }}" alt="{{ $property->name }}" loading="lazy" class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-300">
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($property->featuredImage)
        <section class="bg-gray-100 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <img src="{{ $property->featuredImage?->url }}" alt="{{ $property->name }}" class="w-full h-[50vh] object-cover rounded-2xl">
            </div>
        </section>
    @endif

    <!-- ============ TITLE BAR ============ -->
    <section class="bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <a href="{{ route('properties.public.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white inline-flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('prop.back_to_list') }}
            </a>
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white">{{ $property->name }}</h1>
                {{-- Desktop Share button (near title actions) --}}
                <button type="button"
                        x-data
                        x-on:click="$dispatch('open-share-modal', { url: @js(url()->current()), title: @js($property->name) })"
                        class="hidden md:inline-flex shrink-0 items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        aria-label="{{ __('share.button') }}">
                    <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                    {{ __('share.button') }}
                </button>
            </div>
            @if ($property->city || $property->province)
                <p class="text-gray-500 dark:text-gray-400 mt-1.5 flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ $property->address ? $property->address . ', ' : '' }}{{ $property->city }}{{ $property->province ? ', ' . $property->province : '' }}
                </p>
            @endif
            @if ($property->lowestPrice())
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                    {{ __('prop.from') }} <span class="text-lg font-bold" style="color: {{ $primaryColor }}">Rp {{ number_format($property->lowestPrice(), 0, ',', '.') }}</span>
                </p>
            @endif
        </div>
    </section>

    <!-- ============ MAIN GRID: LEFT INFO + RIGHT BOOKING (desktop sticky) ============ -->
    <section class="py-8 bg-gray-50 dark:bg-gray-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Grid changes based on display mode: 3-col when booking form shown, 2-col or full when pricing_only --}}
            <div class="grid grid-cols-1 {{ $showBookingForm ? 'lg:grid-cols-3' : 'lg:grid-cols-3' }} gap-8">
                <!-- ======== LEFT COLUMN ======== -->
                <div class="{{ $showBookingForm ? 'lg:col-span-2' : 'lg:col-span-2' }} space-y-8">

                    <!-- About -->
                    @if ($property->description)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('prop.about') }} {{ $property->name }}</h2>
                            <div class="prose prose-gray max-w-none text-gray-700 dark:text-gray-300 leading-relaxed text-sm md:text-base">
                                {!! $property->description !!}
                            </div>
                        </div>
                    @endif

                    <!-- Amenities -->
                    @if ($property->amenities->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-5">{{ __('prop.amenities') }}</h2>
                            @php
                            $iconMap = [
                                'wifi' => 'wifi', 'wi-fi' => 'wifi', 'internet' => 'wifi',
                                'parkir' => 'car', 'parking' => 'car', 'parkir mobil' => 'car', 'parkir motor' => 'car',
                                'ac' => 'thermometer', 'air conditioner' => 'thermometer', 'air conditioning' => 'thermometer',
                                'dapur' => 'utensils', 'kitchen' => 'utensils', 'kitchenette' => 'utensils',
                                'tv' => 'tv', 'televisi' => 'tv', 'cable tv' => 'tv',
                                'kolam renang' => 'waves', 'pool' => 'waves', 'swimming pool' => 'waves',
                                'gym' => 'dumbbell', 'fitness' => 'dumbbell', 'pusat kebugaran' => 'dumbbell',
                                'security' => 'shield', 'keamanan' => 'shield', 'keamanan 24 jam' => 'shield', 'security 24 jam' => 'shield', '24-hour security' => 'shield',
                                'lift' => 'arrow-up-square', 'elevator' => 'arrow-up-square',
                                'laundry' => 'shirt', 'cuci baju' => 'shirt',
                                'kamar mandi' => 'bath', 'bathroom' => 'bath',
                                'kamar tidur' => 'bed-double', 'bedroom' => 'bed-double',
                                'balkon' => 'door-open', 'balcony' => 'door-open',
                                'taman' => 'trees', 'garden' => 'trees',
                                'rumah sakit' => 'cross', 'hospital' => 'cross',
                                'restoran' => 'utensils', 'restaurant' => 'utensils',
                                'mall' => 'shopping-bag', 'shopping' => 'shopping-bag', 'pusat perbelanjaan' => 'shopping-bag',
                            ];
                            @endphp
                            <div class="flex flex-wrap gap-3">
                                @foreach ($property->amenities as $amenity)
                                    @php
                                        // Prefer the admin-selected Font Awesome icon (normalised to valid FA6).
                                        // Fall back to a Lucide keyword map, then a neutral default, when no FA icon is set.
                                        $faIcon = $amenity->icon_class;
                                        $lucideName = null;
                                        if (!$faIcon) {
                                            $amenityKey = strtolower($amenity->name);
                                            $lucideName = $iconMap[$amenityKey] ?? null;
                                            if (!$lucideName) {
                                                foreach ($iconMap as $keyword => $icon) {
                                                    if (str_contains($amenityKey, $keyword)) {
                                                        $lucideName = $icon;
                                                        break;
                                                    }
                                                }
                                            }
                                            $lucideName = $lucideName ?: 'check-circle';
                                        }
                                    @endphp
                                    <div class="relative group amenity-item"
                                         title="{{ $amenity->name }}"
                                         data-tooltip="{{ $amenity->name }}">
                                        <div class="flex flex-col items-center justify-center w-16 h-16 bg-white border border-gray-200 dark:bg-gray-700 dark:border-gray-600 rounded-xl shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-500 transition-all duration-200 cursor-default">
                                            @if ($faIcon)
                                                <i class="{{ $faIcon }} text-xl text-blue-600 dark:text-blue-400 mb-1" aria-hidden="true"></i>
                                            @else
                                                <i data-lucide="{{ $lucideName }}" class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-1"></i>
                                            @endif
                                            <span class="text-xs text-gray-600 dark:text-gray-300 text-center leading-tight truncate w-full px-1">{{ \Illuminate\Support\Str::limit($amenity->name, 10) }}</span>
                                        </div>
                                        {{-- Tooltip (desktop hover) --}}
                                        <div class="amenity-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10">
                                            {{ $amenity->name }}
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <script>
                            document.querySelectorAll('.amenity-item').forEach(function(el) {
                                el.addEventListener('click', function() {
                                    var tooltip = this.querySelector('.amenity-tooltip');
                                    tooltip.classList.toggle('opacity-100');
                                    tooltip.classList.toggle('opacity-0');
                                    var self = this;
                                    setTimeout(function() {
                                        self.querySelector('.amenity-tooltip').classList.remove('opacity-100');
                                        self.querySelector('.amenity-tooltip').classList.add('opacity-0');
                                    }, 2000);
                                });
                            });
                            // Reinit lucide for amenity icons
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                            </script>
                        </div>
                    @endif

                    <!-- ===== Available Room Type ===== -->
                    @if (!empty($property->unit_types))
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8" id="rooms">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('prop.rooms_heading') }}</h2>
                            <div class="space-y-4">
                                @foreach ($property->unit_types as $type)
                                    @php
                                        $night = $property->priceFor($type, 'night_wd');
                                        $weekly = $property->priceFor($type, 'weekly');
                                        $monthly = $property->priceFor($type, 'monthly');
                                        $hasTransit = $property->hasBookingType('transit');
                                    @endphp
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 md:p-5 flex flex-col md:flex-row md:items-center gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $property->typeLabel($type) }}</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                @if ($night) <span>Rp {{ number_format($night, 0, ',', '.') }}{{ __('prop.per_night') }}</span> @endif
                                                @if ($weekly) <span class="mx-2">•</span> <span>Rp {{ number_format($weekly, 0, ',', '.') }}{{ __('prop.per_week') }}</span> @endif
                                                @if ($monthly) <span class="mx-2">•</span> <span>Rp {{ number_format($monthly, 0, ',', '.') }}{{ __('prop.per_month') }}</span> @endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if ($hasTransit)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $secondaryColor }}15; color: {{ $secondaryColor }}">{{ __('prop.rent_hourly') }}</span>
                                            @endif
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $primaryColor }}12; color: {{ $primaryColor }}">{{ __('prop.rent_nightly') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- ===== What's Around ===== -->
                    @if ($hasMap || $nearbyGroups)
                        @php
                            // Directions destination: prefer precise lat/lng, else the
                            // full postal address, so the universal Google Maps
                            // "dir/?api=1" URL resolves correctly on app + web.
                            $dirDestination = $hasMap
                                ? ($property->latitude . ',' . $property->longitude)
                                : trim(implode(', ', array_filter([
                                    $property->address,
                                    $property->city,
                                    $property->province,
                                ])));
                            $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($dirDestination);
                            // Engaging Bahasa Indonesia caption for sharing directions.
                            $dirPrice = $property->lowestPrice()
                                ? 'mulai dari Rp ' . number_format($property->lowestPrice(), 0, ',', '.')
                                : 'lokasi strategis & nyaman';
                            $dirCaption = '✨ Cek ' . $property->name . ' — ' . $dirPrice . '! '
                                . 'Lihat detail & petunjuk arah di sini: ' . $directionsUrl;
                        @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('prop.whats_around', ['name' => $property->name]) }}</h2>

                            @if ($dirDestination)
                                {{-- Directions actions: open Google Maps (app on mobile / web on desktop)
                                     + share the directions link via the global share modal. --}}
                                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                                    <a href="{{ $directionsUrl }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                       style="background-color: {{ $primaryColor }}"
                                       aria-label="{{ __('directions.button') }}">
                                        <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>
                                        {{ __('directions.button') }}
                                    </a>
                                    <button type="button"
                                            x-data
                                            x-on:click="$dispatch('open-share-modal', {
                                                url: @js($directionsUrl),
                                                title: @js($property->name),
                                                text: @js($dirCaption),
                                                heading: @js(__('directions.share_title'))
                                            })"
                                            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-full text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                            aria-label="{{ __('directions.share') }}">
                                        <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                                        {{ __('directions.share') }}
                                    </button>
                                </div>
                            @endif

                            @if ($hasMap || !empty($nearbyWithCoords))
                                <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 mb-6 h-64 md:h-80">
                                    <div id="property-detail-map" style="height:100%;width:100%;"></div>
                                </div>
                            @endif

                            @if ($nearbyGroups)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach ($nearbyGroups as $category => $places)
                                        @php
                                            $catEmoji = \App\Models\Property::NEARBY_CATEGORIES[$category] ?? '📌';
                                        @endphp
                                        <div>
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide mb-3 flex items-center gap-2">
                                                <span class="text-base leading-none" aria-hidden="true">{{ $catEmoji }}</span>
                                                {{ $category }}
                                            </h3>
                                            <ul class="space-y-2.5">
                                                @foreach ($places as $place)
                                                    <li class="flex items-start justify-between text-sm gap-3">
                                                        <span class="text-gray-700 dark:text-gray-300">{{ $place['name'] ?? '' }}</span>
                                                        @if (!empty($place['distance_formatted']))
                                                            <span class="text-gray-400 dark:text-gray-500 text-xs shrink-0 tabular-nums">{{ $place['distance_formatted'] }}</span>
                                                        @elseif (!empty($place['distance_km']))
                                                            <span class="text-gray-400 dark:text-gray-500 text-xs shrink-0 tabular-nums">{{ number_format((float) $place['distance_km'], 1, ',', '.') }} km</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('prop.no_nearby') }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- ===== Accommodation Policy ===== -->
                    @if ($property->checkin_time || $property->checkout_time || $property->required_documents || $property->checkin_method)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-5">{{ __('prop.policy_heading', ['name' => $property->name]) }}</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('prop.checkin_checkout') }}</h3>
                                    </div>
                                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>Check-in: {{ $property->checkin_time ?: '—' }}</li>
                                        <li>Check-out: {{ $property->checkout_time ?: '—' }}</li>
                                    </ul>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('prop.required_docs') }}</h3>
                                    </div>
                                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        @forelse ($property->required_documents ?? [] as $doc)
                                            <li class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                {{ $doc }}
                                            </li>
                                        @empty
                                            <li>—</li>
                                        @endforelse
                                    </ul>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('prop.checkin_method_label') }}</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $property->checkin_method ?: '—' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- ===== FAQ (single-open accordion) ===== -->
                    @if ($faqs)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8" x-data="{ open: null }">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-5">{{ __('prop.faq') }}</h2>
                            <div class="space-y-3">
                                @foreach ($faqs as $index => $faq)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                        <h3>
                                            <button type="button"
                                                    @click="open = (open === {{ $index }} ? null : {{ $index }})"
                                                    :aria-expanded="(open === {{ $index }}).toString()"
                                                    aria-controls="faq-panel-{{ $index }}"
                                                    id="faq-header-{{ $index }}"
                                                    class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                                <span>{{ $faq['q'] }}</span>
                                                <i class="fa-solid fa-chevron-down w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200"
                                                   :class="{ 'rotate-180': open === {{ $index }} }" aria-hidden="true"></i>
                                            </button>
                                        </h3>
                                        <div id="faq-panel-{{ $index }}"
                                             role="region"
                                             aria-labelledby="faq-header-{{ $index }}"
                                             x-show="open === {{ $index }}"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             x-cloak>
                                            <p class="px-5 pb-4 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $faq['a'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ===== PRICING TABLE (left column, only in 'both' mode — sits below Available Room Type) ===== --}}
                    @if ($showPricingTable && $displayMode === 'both')
                        @include('properties._pricing-table', [
                            'property'       => $property,
                            'whatsappNumber' => $whatsappNumber,
                            'primaryColor'   => $primaryColor,
                        ])
                    @endif

                    <!-- Contact card (mobile only, shown when booking form is NOT the primary CTA) -->
                    @if ($showBookingForm)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 lg:hidden">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">{{ __('prop.ask_first') }}</h2>
                            @if ($whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode(__('prop.wa_ask_message', ['property' => $property->name])) }}"
                                   target="_blank" rel="noopener"
                                   class="w-full inline-flex items-center justify-center px-5 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition" style="background-color: #25d366">
                                    {{ __('home.chat_whatsapp') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- ======== RIGHT COLUMN: Booking (desktop sticky) ======== -->
                @if ($showBookingForm)
                    <div class="hidden lg:block lg:sticky lg:top-24 lg:self-start space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('prop.book_apartment') }}</h2>
                                @include('properties._booking-form', ['prefix' => 'bkf-desktop'])
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">{{ __('prop.ask_first') }}</h2>
                                <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    @if ($contactPhone)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 shrink-0" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {{ $contactPhone }}
                                        </li>
                                    @endif
                                    @if ($whatsapp)
                                        <li>
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode(__('prop.wa_ask_message', ['property' => $property->name])) }}"
                                               target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-green-600 font-medium hover:underline">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                {{ __('home.chat_whatsapp') }}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                @elseif ($showPricingTable && $displayMode === 'pricing_only')
                    {{-- Pricing-only mode: price table REPLACES the booking form in the sidebar --}}
                    <div class="lg:sticky lg:top-24 lg:self-start space-y-6">
                        @include('properties._pricing-table', [
                            'property'       => $property,
                            'whatsappNumber' => $whatsappNumber,
                            'primaryColor'   => $primaryColor,
                        ])
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- ============ NEARBY ACCOMMODATIONS ============ -->
    @if (!empty($nearbyProperties) && $nearbyProperties->isNotEmpty())
        <section class="py-8 bg-gray-50 dark:bg-gray-800/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-5">
                    {{ __('prop.nearby_heading', ['name' => $property->name]) }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 xl:gap-6">
                    @foreach ($nearbyProperties as $nearby)
                        @include('properties._card', [
                            'property'     => $nearby,
                            'primaryColor' => $primaryColor,
                            'distance'     => $nearby->distance_km ?? null,
                        ])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- ============ MOBILE FLOATING SHARE BUTTON (bottom-LEFT) ============ -->
    {{-- Sits bottom-LEFT to avoid collision with the right-side floating buttons.
         Lifts up by 16 (4rem) when the sticky price/booking bar is visible. --}}
    @php
        $shareBottom = ($showBookingForm || $showPricingTable) ? 'bottom-24' : 'bottom-6';
    @endphp
    <button type="button"
            x-data
            x-on:click="$dispatch('open-share-modal', { url: @js(url()->current()), title: @js($property->name) })"
            class="lg:hidden fixed {{ $shareBottom }} left-4 z-30 w-12 h-12 rounded-full shadow-xl flex items-center justify-center text-white hover:scale-110 transition-transform focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500"
            style="background-color: {{ $primaryColor }}"
            aria-label="{{ __('share.button') }}">
        <i class="fa-solid fa-share-nodes text-lg" aria-hidden="true"></i>
    </button>

    <!-- ============ MOBILE FLOATING BOOKING BAR ============ -->
    @if ($showBookingForm)
        <div id="mob-bk-bar" class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-4 py-3">
            <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('prop.from') }}</p>
                    <p class="text-lg font-bold" style="color: {{ $primaryColor }}">
                        @if ($property->lowestPrice())
                            Rp {{ number_format($property->lowestPrice(), 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <button type="button" id="mob-bk-open" class="px-6 py-3 rounded-full text-white font-semibold text-sm hover:opacity-90 transition" style="background-color: {{ $primaryColor }}">
                    {{ __('prop.booking') }}
                </button>
            </div>
        </div>
        <div class="h-20 lg:hidden"></div>
    @endif

    {{-- ============ MOBILE STICKY PRICE BAR (pricing-only mode, no booking form) ============ --}}
    {{-- Only shown on mobile when pricing table is present but booking form is NOT --}}
    @if ($showPricingTable && !$showBookingForm)
        @php
            // Compute minimum price across all durations for the bar label
            $lowestForBar = $property->lowestPrice();
        @endphp
        <div
            x-data="{ open: false }"
            class="lg:hidden"
        >
            {{-- Sticky bar itself: fixed bottom-0, z-40 --}}
            <div class="fixed bottom-0 inset-x-0 z-40 bg-white shadow-[0_-4px_16px_rgba(0,0,0,0.10)] rounded-t-2xl px-4 py-3">
                <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
                    <div>
                        <p class="text-xs text-gray-500">Harga mulai dari</p>
                        <p class="text-lg font-bold text-gray-900">
                            @if ($lowestForBar)
                                Rp {{ number_format($lowestForBar, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="open = true"
                        class="px-6 py-2.5 rounded-full text-white text-sm font-semibold hover:opacity-90 transition shadow-sm"
                        style="background-color: #16a34a"
                    >
                        Cek Harga
                    </button>
                </div>
            </div>

            {{-- Spacer to push page content above the sticky bar --}}
            <div class="h-20"></div>

            {{-- Backdrop --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="open = false"
                class="fixed inset-0 bg-black/50 z-50"
                x-cloak
                aria-hidden="true"
            ></div>

            {{-- Slide-up bottom sheet with full pricing table --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-full opacity-0"
                class="fixed bottom-0 inset-x-0 z-50 bg-white rounded-t-2xl shadow-2xl max-h-[80vh] overflow-y-auto"
                x-cloak
                role="dialog"
                aria-modal="true"
                aria-label="Daftar Harga"
            >
                {{-- Sheet header --}}
                <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <h3 class="text-base font-bold text-gray-900">{{ __('prop.pricing_heading') }}</h3>
                    <button
                        type="button"
                        @click="open = false"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 transition"
                        aria-label="Tutup"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Full pricing table content --}}
                <div class="px-4 py-4">
                    @include('properties._pricing-table', [
                        'property'       => $property,
                        'whatsappNumber' => $whatsappNumber,
                        'primaryColor'   => $primaryColor,
                    ])
                </div>
            </div>
        </div>
    @endif

    <!-- ============ LIGHTBOX (all photos) with Category Sidebar ============ -->
    <div id="gal-lightbox" class="hidden fixed inset-0 z-[60] flex" role="dialog" aria-modal="true" aria-label="{{ __('lightbox.gallery') }}">
        <!-- Dark overlay (clickable to close) -->
        <div class="absolute inset-0 bg-black/90" data-gal-close></div>

        <!-- Close button (top-right) -->
        <button type="button" data-gal-close aria-label="{{ __('lightbox.close') }}" title="{{ __('lightbox.close') }}" class="absolute top-3 right-3 z-30 text-white/70 hover:text-white text-3xl leading-none w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition">&times;</button>

        <!-- ===== LEFT SIDEBAR (categories) ===== -->
        <aside id="gal-sidebar" class="relative z-10 w-56 lg:w-64 bg-black/60 backdrop-blur-md border-r border-white/10 flex-shrink-0 flex flex-col overflow-y-auto">
            <div class="p-4 border-b border-white/10">
                <h3 class="text-white/90 text-xs font-semibold uppercase tracking-wider">{{ __('lightbox.categories') }}</h3>
            </div>
            <nav id="gal-cat-list" class="flex-1 p-3 space-y-1">
                <!-- Populated by JavaScript -->
            </nav>
        </aside>

        <!-- ===== RIGHT CONTENT (image viewer) ===== -->
        <div class="relative z-10 flex-1 flex flex-col min-w-0">
            <!-- Image stage (arrows + centered image, overflow clipped so transforms stay tidy) -->
            <div class="relative flex-1 flex items-center justify-center overflow-hidden px-2">
                <button type="button" id="gal-prev" aria-label="{{ __('lightbox.prev') }}" title="{{ __('lightbox.prev') }}"
                        class="absolute left-3 md:left-6 z-10 w-11 h-11 flex items-center justify-center rounded-full bg-black/40 text-white/80 hover:text-white hover:bg-black/60 transition">
                    <i class="fa-solid fa-chevron-left text-xl" aria-hidden="true"></i>
                </button>
                <img id="gal-lightbox-img" src="" alt=""
                     class="relative max-h-[72vh] max-w-full rounded-xl shadow-2xl object-contain select-none transition-transform duration-200 will-change-transform"
                     style="transform: scale(1) rotate(0deg);">
                <button type="button" id="gal-next" aria-label="{{ __('lightbox.next') }}" title="{{ __('lightbox.next') }}"
                        class="absolute right-3 md:right-6 z-10 w-11 h-11 flex items-center justify-center rounded-full bg-black/40 text-white/80 hover:text-white hover:bg-black/60 transition">
                    <i class="fa-solid fa-chevron-right text-xl" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Controls + counter + thumbnail strip (below the photo) -->
            {{-- On mobile the category bar (#gal-mobile-bar) is fixed to the bottom, so we add
                 extra bottom padding here (pb-24) to lift the button group + thumbnail strip
                 above it. Desktop keeps the original compact padding (md:pb-3). --}}
            <div class="relative z-30 flex flex-col items-center gap-3 pt-3 pb-24 md:pb-3 px-4 bg-gradient-to-t from-black/60 to-transparent">
                <div class="flex items-center gap-2">
                    <button type="button" id="gal-zoom-in" aria-label="{{ __('lightbox.zoom_in') }}" title="{{ __('lightbox.zoom_in') }}"
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 text-white/80 hover:text-white hover:bg-white/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                        <i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i>
                    </button>
                    <button type="button" id="gal-zoom-out" aria-label="{{ __('lightbox.zoom_out') }}" title="{{ __('lightbox.zoom_out') }}"
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 text-white/80 hover:text-white hover:bg-white/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                        <i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i>
                    </button>
                    <button type="button" id="gal-rotate-left" aria-label="{{ __('lightbox.rotate_left') }}" title="{{ __('lightbox.rotate_left') }}"
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 text-white/80 hover:text-white hover:bg-white/20 transition">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" id="gal-rotate-right" aria-label="{{ __('lightbox.rotate_right') }}" title="{{ __('lightbox.rotate_right') }}"
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 text-white/80 hover:text-white hover:bg-white/20 transition">
                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                    </button>
                    <span id="gal-counter" class="ml-2 text-white/70 text-sm font-medium bg-black/40 px-3 py-1 rounded-full">1 / 1</span>
                </div>

                <!-- Thumbnail strip -->
                <div id="gal-thumbs" class="flex gap-2 overflow-x-auto max-w-full py-1 scrollbar-hide" aria-label="{{ __('lightbox.thumbnails') }}">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- ===== MOBILE BOTTOM BAR (categories) ===== -->
        <div id="gal-mobile-bar" class="hidden fixed bottom-0 left-0 right-0 z-20 bg-black/80 backdrop-blur-md border-t border-white/10">
            <div class="flex overflow-x-auto gap-1 p-2 scrollbar-hide" id="gal-mobile-cat-list">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- ============ BOOKING MODAL ============ -->
    @if ($showBookingForm)
    <div id="bk-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/60" data-close></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div id="bk-form-state">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $property->name }} — Pesan</h3>
                    <button type="button" data-close class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    {{-- Mobile: show booking form first, then contact fields --}}
                    <div id="bk-modal-form-wrapper" class="lg:hidden">
                        @include('properties._booking-form', ['prefix' => 'bkf-modal'])
                    </div>

                    {{-- Desktop: show summary (populated by desktop sidebar form) --}}
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-sm space-y-1 hidden lg:block" id="bk-modal-summary"></div>

                    {{-- Contact fields: hidden on mobile until form submitted --}}
                    <div id="bk-contact-fields" class="space-y-4 hidden lg:block">
                    <div>
                        <label for="bk-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" id="bk-name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    {{-- ====== PHONE (gabungan HP + WhatsApp) dengan country code picker ====== --}}
                    <div>
                        <label for="bk-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            No. HP / WhatsApp <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-400 font-normal">(digunakan untuk konfirmasi booking)</span>
                        </label>
                        <div class="flex">
                            {{-- Country code selector --}}
                            <div class="relative flex-shrink-0">
                                <button type="button" id="bk-country-btn"
                                        class="flex items-center gap-1.5 h-full px-3 py-2 border border-r-0 border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-l-lg text-sm bg-gray-50 hover:bg-gray-100 dark:hover:bg-gray-700 transition min-w-[90px]">
                                    <span id="bk-country-flag" class="text-base leading-none">🇮🇩</span>
                                    <span id="bk-country-code" class="font-medium text-gray-700 dark:text-gray-200">+62</span>
                                    <svg class="w-3 h-3 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                {{-- Dropdown country list --}}
                                <div id="bk-country-dropdown"
                                     class="hidden absolute left-0 top-full mt-1 z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl w-64 max-h-64 overflow-y-auto">
                                    <div class="p-2 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800">
                                        <input type="text" id="bk-country-search" placeholder="Cari negara..."
                                               class="w-full px-3 py-1.5 text-xs border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:outline-none focus:ring-1">
                                    </div>
                                    <div id="bk-country-list" class="py-1">
                                        {{-- Populated by JS --}}
                                    </div>
                                </div>
                            </div>
                            {{-- Phone number input --}}
                            <input type="tel" id="bk-phone" required
                                   inputmode="numeric"
                                   placeholder="81234567890"
                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-r-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        {{-- Peringatan jika diisi dengan awalan 0 --}}
                        <p id="bk-phone-warn" class="hidden mt-1 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            Nomor sudah diawali kode negara, tidak perlu tambah angka <strong>0</strong> di depan.
                        </p>
                        <input type="hidden" id="bk-phone-full">
                        <input type="hidden" id="bk-whatsapp">
                    </div>
                    <div>
                        <label for="bk-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" id="bk-email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="bk-msg" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pesan (opsional)</label>
                        <textarea id="bk-msg" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Deposit 30% diperlukan untuk konfirmasi. Kami akan menghubungi Anda secepatnya.</p>
                    <button type="button" id="bk-submit" class="w-full py-3 rounded-full text-white font-semibold hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed" style="background-color: {{ $primaryColor }}">
                        Kirim Permintaan Booking
                    </button>
                    </div>{{-- end bk-contact-fields --}}
                    <p id="bk-error" class="hidden text-sm text-red-600 dark:text-red-400 text-center"></p>
                </div>
            </div>
            <div id="bk-success-state" class="hidden text-center p-10">
                <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Permintaan Terkirim!</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-2">Kode booking Anda:</p>
                <p class="text-2xl font-mono font-bold" style="color: {{ $primaryColor }}" id="bk-code">—</p>
                <button type="button" data-close class="mt-6 px-6 py-2.5 rounded-full text-white font-medium hover:opacity-90 transition" style="background-color: {{ $primaryColor }}">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
@if($hasMap)
@php
    $popupHtml = '<strong>' . e($property->name) . '</strong>' . ($property->address ? '<br>' . e($property->address) : '');
@endphp
<script>
(function () {
    var LEAFLET_SRC = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

    function initDetailMap() {
        var mapLat   = {{ $property->latitude ?? 0 }};
        var mapLng   = {{ $property->longitude ?? 0 }};
        var hasPropertyPin = {{ $hasMap ? 'true' : 'false' }};

        // Nearby places that have coordinates — passed from the controller
        var nearbyPins = @json($nearbyWithCoords);

        // Determine initial centre: property coords if available, else first nearby pin
        var centreLat = mapLat, centreLng = mapLng;
        if (!hasPropertyPin && nearbyPins.length > 0) {
            centreLat = parseFloat(nearbyPins[0].lat);
            centreLng = parseFloat(nearbyPins[0].lng);
        }

        var map = L.map('property-detail-map', { scrollWheelZoom: false }).setView([centreLat, centreLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Property marker — blue, opened by default
        if (hasPropertyPin) {
            var propertyIcon = L.divIcon({
                className: '',
                html: '<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:#3b82f6;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);transform:rotate(-45deg)"></div>',
                iconSize: [28, 28],
                iconAnchor: [14, 28],
                popupAnchor: [0, -30]
            });
            L.marker([mapLat, mapLng], { icon: propertyIcon })
                .addTo(map)
                .bindPopup({!! json_encode($popupHtml) !!})
                .openPopup();
        }

        // Nearby place markers — orange
        var placeIcon = L.divIcon({
            className: '',
            html: '<div style="width:22px;height:22px;border-radius:50% 50% 50% 0;background:#f97316;border:2px solid #fff;box-shadow:0 2px 5px rgba(0,0,0,.3);transform:rotate(-45deg)"></div>',
            iconSize: [22, 22],
            iconAnchor: [11, 22],
            popupAnchor: [0, -24]
        });

        var bounds = hasPropertyPin ? [[mapLat, mapLng]] : [];

        nearbyPins.forEach(function (place) {
            var lat = parseFloat(place.lat);
            var lng = parseFloat(place.lng);
            if (isNaN(lat) || isNaN(lng)) return;

            var cat  = place.category || '';
            var dist = place.distance_formatted ? '<br><span style="color:#6b7280;font-size:0.75rem">' + place.distance_formatted + '</span>' : '';
            var popup = '<strong>' + (place.name || '') + '</strong>'
                + (cat ? '<br><span style="color:#6b7280;font-size:0.75rem">' + cat + '</span>' : '')
                + dist;

            L.marker([lat, lng], { icon: placeIcon })
                .addTo(map)
                .bindPopup(popup);

            bounds.push([lat, lng]);
        });

        // Fit map to include all pins (with padding), but not zoom in too tightly
        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
        }
    }

    // Load Leaflet on demand so `L` is defined before use (loadScript from app.js).
    if (document.getElementById('property-detail-map')) {
        if (typeof window.loadScript === 'function') {
            window.loadScript(LEAFLET_SRC).then(initDetailMap).catch(function () {});
        } else if (typeof L !== 'undefined') {
            initDetailMap();
        }
    }
})();
</script>
@endif
<script>
(function () {
    var allPhotos     = @json($allPhotoUrls);
    var photoGallery  = @json($photoGallery);
    var prices = @json($property->prices ?? []);
    var weekendDays = @json($property->weekendDays());
    var unitTypeLabels = @json(\App\Models\Property::UNIT_TYPES);
    var propertyId = {{ $property->id }};
    var maxDays = {{ $property->maxBookingDays() ?: 365 }};
    var buckets = [3, 6, 9, 12, 24];

    // ===== LIGHTBOX with Category Sidebar =====
    (function () {
        var lb     = document.getElementById('gal-lightbox');
        var lbImg  = document.getElementById('gal-lightbox-img');
        var lbPrev = document.getElementById('gal-prev');
        var lbNext = document.getElementById('gal-next');
        var lbCnt  = document.getElementById('gal-counter');
        var catEl  = document.getElementById('gal-cat-list');
        var mCatEl = document.getElementById('gal-mobile-cat-list');
        var sidebarEl = document.getElementById('gal-sidebar');
        var mbarEl    = document.getElementById('gal-mobile-bar');
        var thumbsEl  = document.getElementById('gal-thumbs');
        var zoomInBtn  = document.getElementById('gal-zoom-in');
        var zoomOutBtn = document.getElementById('gal-zoom-out');
        var rotLeftBtn  = document.getElementById('gal-rotate-left');
        var rotRightBtn = document.getElementById('gal-rotate-right');

        // State:
        //   filtered  – the full ordered gallery (next/prev traverse ALL photos so the
        //               user can move across categories; category buttons jump/scroll
        //               rather than filtering to a subset).
        //   lbIdx     – index of the current photo within `filtered`.
        //   activeCat – the CURRENT photo's category; kept in sync on every navigation
        //               (next/prev/thumbnail/keyboard) so the category bar highlights it.
        var filtered  = photoGallery;   // full gallery (never sliced)
        var lbIdx     = 0;              // index within `filtered`
        var activeCat = null;          // current photo's category (null before first show)

        // Zoom / rotate transform state for the current image
        var ZOOM_MIN = 0.5, ZOOM_MAX = 4, ZOOM_STEP = 0.25;
        var zoom = 1, rotation = 0;

        // Apply the combined scale + rotate transform to the image
        function applyTransform() {
            lbImg.style.transform = 'scale(' + zoom + ') rotate(' + rotation + 'deg)';
            if (zoomInBtn)  zoomInBtn.disabled  = zoom >= ZOOM_MAX;
            if (zoomOutBtn) zoomOutBtn.disabled = zoom <= ZOOM_MIN;
        }

        // Reset zoom + rotation (called on navigate / open / category change)
        function resetTransform() {
            zoom = 1;
            rotation = 0;
            applyTransform();
        }

        // Map a GLOBAL index (position in allPhotos / data-photo attribute)
        // to the filtered list — when "All Photos" is active they are identical,
        // otherwise find the photo with the same URL.
        function globalToFilteredIdx(globalIdx) {
            var globalUrl = allPhotos[globalIdx];
            for (var i = 0; i < filtered.length; i++) {
                if (filtered[i].url === globalUrl) return i;
            }
            return 0;
        }

        // Filtered URL array (convenience)
        function filteredUrls() {
            return filtered.map(function (p) { return p.url; });
        }

        // Build categories from actual data (only categories that have photos)
        function buildCategories() {
            var catCount = {};
            photoGallery.forEach(function (p) {
                var c = p.category || 'Other';
                catCount[c] = (catCount[c] || 0) + 1;
            });
            return {
                cats: Object.keys(catCount).sort(),
                catCount: catCount
            };
        }

        function esc(s) {
            return String(s).replace(/&/g, '&').replace(/"/g, '"').replace(/</g, '<').replace(/>/g, '>');
        }

        // Render the desktop sidebar + mobile bar. The active state now reflects the
        // CURRENT photo's category (`activeCat`) rather than a filter selection, so the
        // highlight follows the image as the user navigates next/prev/thumbnails.
        function renderCategories() {
            var data = buildCategories();

            var html = '';
            data.cats.forEach(function (c) {
                var isActive = (activeCat === c);
                html += '<button type="button" class="gal-cat-btn flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm font-medium transition ' +
                        (isActive ? 'bg-white/20 text-white' : 'text-white/60 hover:text-white hover:bg-white/10') + '" data-cat="' + esc(c) + '">' +
                        '<span>' + esc(c) + '</span>' +
                        '<span class="text-xs ml-2 opacity-70">' + data.catCount[c] + '</span>' +
                        '</button>';
            });
            catEl.innerHTML = html;

            var mhtml = '';
            data.cats.forEach(function (c) {
                var isActive = (activeCat === c);
                mhtml += '<button type="button" class="gal-cat-btn flex-shrink-0 px-4 py-1.5 rounded-full text-xs font-medium transition whitespace-nowrap ' +
                         (isActive ? 'bg-white text-black' : 'bg-white/15 text-white/80 hover:bg-white/25') + '" data-cat="' + esc(c) + '">' +
                         esc(c) + ' (' + data.catCount[c] + ')</button>';
            });
            mCatEl.innerHTML = mhtml;

            scrollActiveCatIntoView();
        }

        // Scroll the active category button into view in both the desktop sidebar
        // (vertical) and the mobile bottom bar (horizontal) so it stays visible.
        function scrollActiveCatIntoView() {
            if (activeCat == null) return;
            var sel = '.gal-cat-btn[data-cat="' + (window.CSS && CSS.escape ? CSS.escape(activeCat) : activeCat) + '"]';
            [catEl, mCatEl].forEach(function (container) {
                if (!container) return;
                var btn = container.querySelector(sel);
                if (btn && btn.scrollIntoView) {
                    btn.scrollIntoView({ block: 'nearest', inline: 'center' });
                }
            });
        }

        // Render the thumbnail strip for the current filtered list.
        function renderThumbs() {
            if (!thumbsEl) return;
            var html = '';
            filtered.forEach(function (p, i) {
                var isActive = (i === lbIdx);
                html += '<button type="button" class="gal-thumb flex-shrink-0 w-16 h-12 rounded-md overflow-hidden border-2 transition ' +
                        (isActive ? 'border-white opacity-100' : 'border-transparent opacity-60 hover:opacity-100') + '" data-idx="' + i + '">' +
                        '<img src="' + esc(p.url) + '" alt="' + esc(p.name || '') + '" class="w-full h-full object-cover" loading="lazy">' +
                        '</button>';
            });
            thumbsEl.innerHTML = html;
            // Scroll the active thumbnail into view
            var active = thumbsEl.querySelector('.gal-thumb[data-idx="' + lbIdx + '"]');
            if (active && active.scrollIntoView) {
                active.scrollIntoView({ block: 'nearest', inline: 'center' });
            }
        }

        // Show the photo at `idx` (index within `filtered`)
        function showPhoto(idx) {
            if (!filtered.length) {
                lbImg.src = '';
                lbCnt.textContent = '0 / 0';
                if (thumbsEl) thumbsEl.innerHTML = '';
                return;
            }
            lbIdx = ((idx % filtered.length) + filtered.length) % filtered.length;
            lbImg.src = filtered[lbIdx].url;
            lbImg.alt = filtered[lbIdx].name || '';
            lbCnt.textContent = (lbIdx + 1) + ' / ' + filtered.length;
            resetTransform();   // reset zoom/rotation whenever a new image is shown
            renderThumbs();
            // Keep the active category in sync with the current photo so the
            // category bar highlight follows next/prev/thumbnail navigation.
            syncActiveCat();
        }

        // Recompute the active category from the current photo, then re-render the
        // category bars (highlight + auto-scroll) only when it actually changed.
        function syncActiveCat() {
            if (!filtered.length) return;
            var cat = filtered[lbIdx].category || 'Other';
            if (cat !== activeCat) {
                activeCat = cat;
                renderCategories();
            } else {
                scrollActiveCatIntoView();
            }
        }

        function moveLb(d) {
            showPhoto(lbIdx + d);
        }

        // Open the lightbox, showing the photo at GLOBAL index `globalIdx`.
        // `filtered` is always the full gallery; showPhoto() derives activeCat
        // from the current photo and renders the category bars.
        function openLb(globalIdx) {
            filtered = photoGallery;
            showPhoto(globalToFilteredIdx(globalIdx));
            lb.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeLb() {
            lb.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Category click: jump to the FIRST photo of that category within the full
        // gallery. next/prev still traverse every photo; showPhoto() re-syncs the
        // active-category highlight from whatever photo becomes current.
        function onCatClick(cat) {
            for (var i = 0; i < filtered.length; i++) {
                if ((filtered[i].category || 'Other') === cat) {
                    showPhoto(i);
                    return;
                }
            }
        }

        // Desktop sidebar category click
        catEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.gal-cat-btn');
            if (btn) onCatClick(btn.getAttribute('data-cat'));
        });
        // Mobile bar category click
        mCatEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.gal-cat-btn');
            if (btn) onCatClick(btn.getAttribute('data-cat'));
        });

        // Navigation buttons
        lbPrev.addEventListener('click', function () { moveLb(-1); });
        lbNext.addEventListener('click', function () { moveLb(1); });

        // Thumbnail strip click -> jump to image
        if (thumbsEl) {
            thumbsEl.addEventListener('click', function (e) {
                var btn = e.target.closest('.gal-thumb');
                if (btn) showPhoto(parseInt(btn.getAttribute('data-idx'), 10));
            });
        }

        // Zoom + rotate controls
        if (zoomInBtn) zoomInBtn.addEventListener('click', function () {
            zoom = Math.min(ZOOM_MAX, Math.round((zoom + ZOOM_STEP) * 100) / 100);
            applyTransform();
        });
        if (zoomOutBtn) zoomOutBtn.addEventListener('click', function () {
            zoom = Math.max(ZOOM_MIN, Math.round((zoom - ZOOM_STEP) * 100) / 100);
            applyTransform();
        });
        if (rotLeftBtn) rotLeftBtn.addEventListener('click', function () {
            rotation -= 90;
            applyTransform();
        });
        if (rotRightBtn) rotRightBtn.addEventListener('click', function () {
            rotation += 90;
            applyTransform();
        });

        // Close on overlay click or close button
        document.querySelectorAll('[data-gal-close]').forEach(function (el) {
            el.addEventListener('click', closeLb);
        });

        // Open from thumbnail gallery
        document.querySelectorAll('[data-photo]').forEach(function (el) {
            el.addEventListener('click', function () {
                openLb(parseInt(el.dataset.photo, 10));
            });
        });
        document.getElementById('gal-open').addEventListener('click', function () { openLb(0); });

        // Keyboard support (Escape, arrows)
        document.addEventListener('keydown', function (e) {
            if (lb.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeLb();
            if (e.key === 'ArrowLeft') moveLb(-1);
            if (e.key === 'ArrowRight') moveLb(1);
        });

        // Responsive: sidebar -> top/bottom bar on small screens
        function checkMobile() {
            var isMobile = window.innerWidth < 768;
            sidebarEl.classList.toggle('hidden', isMobile);
            mbarEl.classList.toggle('hidden', !isMobile);
        }
        window.addEventListener('resize', checkMobile);
        // Re-check responsiveness whenever the lightbox is opened
        var origRemove = lb.classList.remove.bind(lb.classList);
        lb.classList.remove = function () {
            origRemove.apply(lb.classList, arguments);
            if (arguments[0] === 'hidden') checkMobile();
        };

        // Initial render
        renderCategories();
        checkMobile();
    })();

    // ---------- Booking ----------
    var modal = document.getElementById('bk-modal');
    var $summary = document.getElementById('bk-modal-summary');
    var $name = document.getElementById('bk-name');
    var $phone = document.getElementById('bk-phone');
    var $whatsapp = document.getElementById('bk-whatsapp');
    var $email = document.getElementById('bk-email');
    var $msg = document.getElementById('bk-msg');
    var $error = document.getElementById('bk-error');
    var $submit = document.getElementById('bk-submit');
    var $code = document.getElementById('bk-code');

    function fmt(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
    function isWeekend(d) { return weekendDays.indexOf(d.getDay()) !== -1; }

    function billedHours(h) {
        for (var i = 0; i < buckets.length; i++) { if (h <= buckets[i]) return buckets[i]; }
        return 24;
    }

    /** Get selected room type from a form (pill / select / hidden) */
    function getRoomType(form) {
        var activePill = form.querySelector('.bkf-room-pill.bkf-pill-active');
        if (activePill) return activePill.dataset.type;
        var sel = form.querySelector('.bkf-room-type');
        if (sel) return sel.value;
        var hidden = form.querySelector('.bkf-room-type-hidden');
        if (hidden) return hidden.value;
        return '';
    }

    /** Rebuild satuan <select> based on prices available for given type */
    function rebuildSatuan(form, type) {
        var p = prices[type] || {};
        var $unit = form.querySelector('.bkf-unit');
        var current = $unit.value;
        var opts = [];
        var hasTransit = buckets.some(function (h) {
            return parseFloat(p['t' + h + '_wd']) > 0 || parseFloat(p['t' + h + '_we']) > 0;
        });
        var hasDaily   = parseFloat(p['night_wd']) > 0 || parseFloat(p['night_we']) > 0;
        var hasWeekly  = parseFloat(p['weekly']) > 0;
        var hasMonthly = parseFloat(p['monthly']) > 0;
        if (hasTransit) opts.push({ value: 'jam',    label: 'Transit Jam' });
        if (hasDaily)   opts.push({ value: 'malam',  label: 'Harian' });
        if (hasWeekly)  opts.push({ value: 'minggu', label: 'Mingguan' });
        if (hasMonthly) opts.push({ value: 'bulan',  label: 'Bulanan' });
        $unit.innerHTML = '';
        opts.forEach(function (o) {
            var opt = document.createElement('option');
            opt.value = o.value; opt.textContent = o.label;
            if (o.value === current) opt.selected = true;
            $unit.appendChild(opt);
        });
        if (!$unit.value && opts.length) $unit.value = opts[0].value;
        return $unit.value;
    }

    /** Rebuild durasi element based on satuan */
    function rebuildDurasi(form, satuan, type) {
        var wrap = form.querySelector('[id$="-duration-wrap"]');
        if (!wrap) return;
        var p = prices[type] || {};
        if (satuan === 'jam') {
            var slots = buckets.filter(function (h) {
                return parseFloat(p['t' + h + '_wd']) > 0 || parseFloat(p['t' + h + '_we']) > 0;
            });
            if (slots.length > 0) {
                var sel = document.createElement('select');
                sel.className = 'bkf-duration w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2';
                slots.forEach(function (h) {
                    var opt = document.createElement('option'); opt.value = h; opt.textContent = h + ' jam';
                    sel.appendChild(opt);
                });
                wrap.innerHTML = ''; wrap.appendChild(sel);
                sel.addEventListener('change', function () { calc(form); });
            }
        } else if (satuan === 'minggu') {
            var sel2 = document.createElement('select');
            sel2.className = 'bkf-duration w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2';
            [1,2,3,4].forEach(function (w) {
                var opt = document.createElement('option'); opt.value = w; opt.textContent = w + ' minggu';
                sel2.appendChild(opt);
            });
            wrap.innerHTML = ''; wrap.appendChild(sel2);
            sel2.addEventListener('change', function () { calc(form); });
        } else if (satuan === 'bulan') {
            var sel3 = document.createElement('select');
            sel3.className = 'bkf-duration w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2';
            for (var m = 1; m <= 12; m++) {
                var opt2 = document.createElement('option'); opt2.value = m; opt2.textContent = m + ' bulan';
                sel3.appendChild(opt2);
            }
            wrap.innerHTML = ''; wrap.appendChild(sel3);
            sel3.addEventListener('change', function () { calc(form); });
        } else {
            var inp = document.createElement('input');
            inp.type = 'number';
            inp.className = 'bkf-duration w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2';
            inp.value = 1; inp.min = 1; inp.max = maxDays;
            wrap.innerHTML = ''; wrap.appendChild(inp);
            inp.addEventListener('change', function () { calc(form); });
            inp.addEventListener('input', function () { calc(form); });
        }
    }

    function getDuration(form) {
        var el = form.querySelector('.bkf-duration');
        return el ? (parseInt(el.value, 10) || 1) : 1;
    }

    function calc(form) {
        var type = getRoomType(form);
        var p = prices[type] || {};
        var unit = form.querySelector('.bkf-unit').value;
        var duration = getDuration(form);
        var checkin = form.querySelector('.bkf-checkin').value;
        var time = form.querySelector('.bkf-checkin-time').value || '14:00';
        var detailEl = form.querySelector('.bkf-detail');
        var totalEl = form.querySelector('.bkf-total');
        var total = 0, detail = '';
        if (!checkin) {
            detail = 'Pilih tanggal check-in';
        } else if (unit === 'jam') {
            var billed = parseInt(duration, 10);
            var key = (isWeekend(new Date(checkin + 'T00:00:00')) ? 't' + billed + '_we' : 't' + billed + '_wd');
            total = parseFloat(p[key] || 0);
            detail = billed + ' jam · ' + time;
        } else if (unit === 'minggu') {
            total = parseFloat(p['weekly'] || 0) * duration;
            detail = duration + ' minggu (' + (duration * 7) + ' malam)';
        } else if (unit === 'bulan') {
            total = parseFloat(p['monthly'] || 0) * duration;
            detail = duration + ' bulan (' + (duration * 30) + ' malam)';
        } else {
            var start = new Date(checkin + 'T00:00:00');
            for (var i = 0; i < duration; i++) {
                var d = new Date(start.getTime() + i * 86400000);
                total += parseFloat(p[isWeekend(d) ? 'night_we' : 'night_wd'] || 0);
            }
            detail = duration + ' malam';
        }
        form.dataset.total = total;
        form.dataset.detail = detail;
        totalEl.textContent = fmt(total);
        detailEl.textContent = detail || '—';
    }

    function stylePills(form, activeType) {
        form.querySelectorAll('.bkf-room-pill').forEach(function (pill) {
            var isActive = pill.dataset.type === activeType;
            pill.classList.toggle('bkf-pill-active', isActive);
            pill.classList.toggle('bkf-pill-inactive', !isActive);
            pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            if (isActive) {
                pill.style.backgroundColor = '{{ $primaryColor }}';
                pill.style.color = '#fff';
                pill.style.borderColor = '{{ $primaryColor }}';
            } else {
                pill.style.backgroundColor = '';
                pill.style.color = '';
                pill.style.borderColor = '';
            }
        });
    }

    function bindForm(form) {
        var initialType = getRoomType(form);
        var initialSatuan = rebuildSatuan(form, initialType);
        rebuildDurasi(form, initialSatuan, initialType);
        stylePills(form, initialType);
        calc(form);

        // Room type pills
        form.querySelectorAll('.bkf-room-pill').forEach(function (pill) {
            pill.addEventListener('click', function () {
                var newType = pill.dataset.type;
                stylePills(form, newType);
                var newSatuan = rebuildSatuan(form, newType);
                rebuildDurasi(form, newSatuan, newType);
                calc(form);
            });
        });

        // Room type dropdown
        var rtSel = form.querySelector('.bkf-room-type');
        if (rtSel) {
            rtSel.addEventListener('change', function () {
                var newType = rtSel.value;
                var newSatuan = rebuildSatuan(form, newType);
                rebuildDurasi(form, newSatuan, newType);
                calc(form);
            });
        }

        // Satuan change
        form.querySelector('.bkf-unit').addEventListener('change', function () {
            var type = getRoomType(form);
            rebuildDurasi(form, form.querySelector('.bkf-unit').value, type);
            calc(form);
        });

        // Date/time/guests changes
        form.querySelectorAll('.bkf-checkin, .bkf-checkin-time, .bkf-guests').forEach(function (el) {
            el.addEventListener('change', function () { calc(form); });
            el.addEventListener('input', function () { calc(form); });
        });

        // "Lanjut Pemesanan"
        form.querySelector('.bkf-open').addEventListener('click', function () {
            var total = parseFloat(form.dataset.total || 0);
            if (total <= 0) {
                form.querySelector('.bkf-error').textContent = 'Silakan lengkapi pilihan tanggal & durasi terlebih dahulu.';
                form.querySelector('.bkf-error').classList.remove('hidden');
                return;
            }
            form.querySelector('.bkf-error').classList.add('hidden');
            var type = getRoomType(form);
            var typeLabel = unitTypeLabels[type] || type;
            var unit = form.querySelector('.bkf-unit').value;
            var duration = getDuration(form);
            var checkin = form.querySelector('.bkf-checkin').value;
            var time = form.querySelector('.bkf-checkin-time').value || '14:00';
            var guests = parseInt(form.querySelector('.bkf-guests').value, 10) || 1;
            var detail = form.dataset.detail;
            var satuanLabel = form.querySelector('.bkf-unit').options[form.querySelector('.bkf-unit').selectedIndex]
                ? form.querySelector('.bkf-unit').options[form.querySelector('.bkf-unit').selectedIndex].text : unit;
            $summary.innerHTML =
                '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Tipe kamar</span><span class="font-medium">' + typeLabel + '</span></div>' +
                '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Tipe sewa</span><span class="font-medium">' + satuanLabel + '</span></div>' +
                '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Check-in</span><span class="font-medium">' + checkin + ' · ' + time + '</span></div>' +
                '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Durasi</span><span class="font-medium">' + detail + '</span></div>' +
                '<div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-2 mt-2"><span class="font-medium">Total</span><span class="font-bold" style="color: {{ $primaryColor }}">' + fmt(total) + '</span></div>';
            window.__bkPayload = {
                property_id: propertyId,
                unit_type: type,
                booking_type: unit === 'jam' ? 'transit' : (unit === 'minggu' ? 'weekly' : (unit === 'bulan' ? 'monthly' : 'daily')),
                unit: unit,
                duration: duration,
                check_in: checkin,
                check_in_time: time,
                guests: guests
            };
            modal.classList.remove('hidden');
        });
    }

    document.querySelectorAll('[data-bkf]').forEach(bindForm);

    // Mobile bar opens modal with embedded form
    var mobOpen = document.getElementById('mob-bk-open');
    var contactFields = document.getElementById('bk-contact-fields');
    var submitBtnWrap = document.getElementById('bk-submit-btn-wrap');
    if (mobOpen) {
        mobOpen.addEventListener('click', function () {
            modal.classList.remove('hidden');
            // Reset: hide contact fields on mobile until form filled
            if (window.innerWidth < 1024) {
                contactFields.classList.add('hidden');
                submitBtnWrap.classList.add('hidden');
            }
        });
    }

    // Mobile form "Lanjut Pemesanan" shows contact fields
    var mobileForm = document.querySelector('#bk-modal-form-wrapper [data-bkf]');
    if (mobileForm) {
        var mobileOpenBtn = mobileForm.querySelector('.bkf-open');
        if (mobileOpenBtn) {
            mobileOpenBtn.addEventListener('click', function() {
                var total = parseFloat(mobileForm.dataset.total || 0);
                if (total <= 0) return; // bindForm handles error display

                // Show contact fields and submit button
                contactFields.classList.remove('hidden');
                submitBtnWrap.classList.remove('hidden');

                // Build summary for desktop (modal already has form visible on mobile)
                var type = getRoomType(mobileForm);
                var typeLabel = unitTypeLabels[type] || type;
                var unit = mobileForm.querySelector('.bkf-unit').value;
                var checkin = mobileForm.querySelector('.bkf-checkin').value;
                var time = mobileForm.querySelector('.bkf-checkin-time').value || '14:00';
                var guests = parseInt(mobileForm.querySelector('.bkf-guests').value, 10) || 1;
                var detail = mobileForm.dataset.detail;
                var satuanLabel = mobileForm.querySelector('.bkf-unit').options[mobileForm.querySelector('.bkf-unit').selectedIndex]
                    ? mobileForm.querySelector('.bkf-unit').options[mobileForm.querySelector('.bkf-unit').selectedIndex].text : unit;

                $summary.innerHTML =
                    '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Tipe kamar</span><span class="font-medium">' + typeLabel + '</span></div>' +
                    '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Tipe sewa</span><span class="font-medium">' + satuanLabel + '</span></div>' +
                    '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Check-in</span><span class="font-medium">' + checkin + ' · ' + time + '</span></div>' +
                    '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Durasi</span><span class="font-medium">' + detail + '</span></div>' +
                    '<div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-2 mt-2"><span class="text-gray-500 dark:text-gray-400 font-medium">Total</span><span class="font-bold text-lg">Rp ' + parseInt(total).toLocaleString('id-ID') + '</span></div>';

                window.__bkPayload = {
                    property_id: {{ $property->id }},
                    room_type: type,
                    booking_unit: unit,
                    check_in_date: checkin,
                    check_in_time: time,
                    guests: guests,
                    duration: getDuration(mobileForm),
                    total_price: total
                };
            });
        }
    }

    document.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', function () { modal.classList.add('hidden'); });
    });

    $submit.addEventListener('click', function () {
        var name  = $name.value.trim();
        var phone = document.getElementById('bk-phone').value.trim();
        var countryCode = document.getElementById('bk-country-code').textContent.trim(); // e.g. "+62"

        if (!name || !phone) {
            $error.textContent = 'Nama dan No. HP wajib diisi.';
            $error.classList.remove('hidden');
            return;
        }

        // Gabungkan kode negara + nomor (strip awalan 0 jika ada)
        var cleanPhone = phone.replace(/^0+/, '');
        var fullPhone  = countryCode + cleanPhone;

        $error.classList.add('hidden');

        var payload = Object.assign({}, window.__bkPayload, {
            customer_name:     name,
            customer_phone:    fullPhone,
            customer_whatsapp: fullPhone,
            customer_email:    $email.value.trim(),
            message:           $msg.value.trim()
        });

        fetch('{{ route('bookings.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
            if (result.ok && result.data.success) {
                $code.textContent = result.data.code;
                document.getElementById('bk-form-state').classList.add('hidden');
                document.getElementById('bk-success-state').classList.remove('hidden');
            } else {
                $error.textContent = result.data.message || 'Terjadi kesalahan. Coba lagi.';
                $error.classList.remove('hidden');
            }
        })
        .catch(function () {
            $error.textContent = 'Koneksi gagal. Coba lagi.';
            $error.classList.remove('hidden');
        });
    });

    // ================================================================
    // COUNTRY CODE PICKER — dengan validasi awalan 0
    // ================================================================
    (function () {
        var countries = [
            { flag: '🇮🇩', name: 'Indonesia',        code: '+62'  },
            { flag: '🇸🇬', name: 'Singapore',        code: '+65'  },
            { flag: '🇲🇾', name: 'Malaysia',         code: '+60'  },
            { flag: '🇦🇺', name: 'Australia',        code: '+61'  },
            { flag: '🇺🇸', name: 'United States',    code: '+1'   },
            { flag: '🇬🇧', name: 'United Kingdom',   code: '+44'  },
            { flag: '🇸🇦', name: 'Saudi Arabia',     code: '+966' },
            { flag: '🇦🇪', name: 'UAE',              code: '+971' },
            { flag: '🇯🇵', name: 'Japan',            code: '+81'  },
            { flag: '🇰🇷', name: 'South Korea',      code: '+82'  },
            { flag: '🇨🇳', name: 'China',            code: '+86'  },
            { flag: '🇮🇳', name: 'India',            code: '+91'  },
            { flag: '🇵🇭', name: 'Philippines',      code: '+63'  },
            { flag: '🇹🇭', name: 'Thailand',         code: '+66'  },
            { flag: '🇻🇳', name: 'Vietnam',          code: '+84'  },
            { flag: '🇳🇱', name: 'Netherlands',      code: '+31'  },
            { flag: '🇩🇪', name: 'Germany',          code: '+49'  },
            { flag: '🇫🇷', name: 'France',           code: '+33'  },
            { flag: '🇧🇷', name: 'Brazil',           code: '+55'  },
            { flag: '🇿🇦', name: 'South Africa',     code: '+27'  },
        ];

        var btn      = document.getElementById('bk-country-btn');
        var dropdown = document.getElementById('bk-country-dropdown');
        var flagEl   = document.getElementById('bk-country-flag');
        var codeEl   = document.getElementById('bk-country-code');
        var search   = document.getElementById('bk-country-search');
        var list     = document.getElementById('bk-country-list');
        var phoneIn  = document.getElementById('bk-phone');
        var warnEl   = document.getElementById('bk-phone-warn');

        if (!btn || !dropdown || !phoneIn) return;

        function renderList(filter) {
            var filtered = filter
                ? countries.filter(function (c) {
                    return c.name.toLowerCase().includes(filter.toLowerCase()) ||
                           c.code.includes(filter);
                  })
                : countries;
            list.innerHTML = '';
            filtered.forEach(function (c) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition text-left';
                item.innerHTML = '<span class="text-base">' + c.flag + '</span>' +
                                 '<span class="flex-1">' + c.name + '</span>' +
                                 '<span class="text-gray-400 font-mono text-xs">' + c.code + '</span>';
                item.addEventListener('click', function () {
                    flagEl.textContent = c.flag;
                    codeEl.textContent = c.code;
                    dropdown.classList.add('hidden');
                    phoneIn.focus();
                    // Re-check warning after country change
                    checkLeadingZero();
                });
                list.appendChild(item);
            });
        }

        // Toggle dropdown
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                search.value = '';
                renderList('');
                setTimeout(function () { search.focus(); }, 50);
            }
        });

        // Search filter
        search.addEventListener('input', function () {
            renderList(this.value);
        });

        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Validasi awalan 0
        function checkLeadingZero() {
            var val = phoneIn.value.trim();
            if (val.startsWith('0')) {
                warnEl.classList.remove('hidden');
            } else {
                warnEl.classList.add('hidden');
            }
        }

        phoneIn.addEventListener('input', checkLeadingZero);
        phoneIn.addEventListener('blur',  checkLeadingZero);

        // Init list
        renderList('');
    })();

})();
</script>
@endpush
