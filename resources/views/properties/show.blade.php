@extends('layouts.frontend')

@php
    $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
    $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
    $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
    $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
    $displayMode = \App\Services\SettingsService::get('booking_display_mode', 'both');
    $whatsappNumber = \App\Services\SettingsService::get('whatsapp_number', '') ?: \App\Services\SettingsService::get('whatsapp_default', '');
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
    $nearbyGroups = $property->nearbyByCategory();
    $hasMap = $property->latitude && $property->longitude;
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
                    <button type="button" data-photo="0" class="relative h-64 md:h-auto md:[grid-row:1/-1] group overflow-hidden text-left">
                        <img src="{{ $firstPhoto }}" alt="{{ $property->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    </button>
                    <!-- Overlay: view all photos -->
                    <button type="button" id="gal-open" class="absolute bottom-4 right-4 z-10 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/95 text-sm font-semibold text-gray-900 shadow hover:bg-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Lihat Semua Foto ({{ $allPhotoUrls->count() }})
                    </button>
                    @foreach ($restPhotos as $i => $url)
                        <button type="button" data-photo="{{ $i + 1 }}" class="relative hidden md:block h-36 lg:h-44 group overflow-hidden">
                            <img src="{{ $url }}" alt="{{ $property->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </button>
                    @endforeach
                </div>
                <!-- Mobile: first 3 photos row -->
                <div class="grid grid-cols-3 gap-2 md:hidden mt-2">
                    @foreach ($restPhotos->take(3) as $i => $url)
                        <button type="button" data-photo="{{ $i + 1 }}" class="relative group overflow-hidden rounded-xl h-28">
                            <img src="{{ $url }}" alt="{{ $property->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
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
                Kembali ke Daftar
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white">{{ $property->name }}</h1>
            @if ($property->city || $property->province)
                <p class="text-gray-500 dark:text-gray-400 mt-1.5 flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ $property->address ? $property->address . ', ' : '' }}{{ $property->city }}{{ $property->province ? ', ' . $property->province : '' }}
                </p>
            @endif
            @if ($property->lowestPrice())
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                    Mulai dari <span class="text-lg font-bold" style="color: {{ $primaryColor }}">Rp {{ number_format($property->lowestPrice(), 0, ',', '.') }}</span>
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
                                        // Resolve icon: try exact match first, then substring match, then fallback.
                                        // The fallback strips any non-ASCII/emoji values from $amenity->icon.
                                        $amenityKey = strtolower($amenity->name);
                                        $iconName = $iconMap[$amenityKey] ?? null;
                                        if (!$iconName) {
                                            foreach ($iconMap as $keyword => $icon) {
                                                if (str_contains($amenityKey, $keyword)) {
                                                    $iconName = $icon;
                                                    break;
                                                }
                                            }
                                        }
                                        if (!$iconName) {
                                            $rawIcon = $amenity->icon ?? '';
                                            // Only use $amenity->icon if it contains only ASCII printable characters (valid Lucide name)
                                            $iconName = (preg_match('/^[a-z0-9\-]+$/i', $rawIcon) && $rawIcon !== '') ? $rawIcon : 'check-circle';
                                        }
                                    @endphp
                                    <div class="relative group amenity-item"
                                         title="{{ $amenity->name }}"
                                         data-tooltip="{{ $amenity->name }}">
                                        <div class="flex flex-col items-center justify-center w-16 h-16 bg-white border border-gray-200 dark:bg-gray-700 dark:border-gray-600 rounded-xl shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-500 transition-all duration-200 cursor-default">
                                            <i data-lucide="{{ $iconName }}" class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-1"></i>
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
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Available Room Type</h2>
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
                                                @if ($night) <span>Rp {{ number_format($night, 0, ',', '.') }}/malam</span> @endif
                                                @if ($weekly) <span class="mx-2">•</span> <span>Rp {{ number_format($weekly, 0, ',', '.') }}/minggu</span> @endif
                                                @if ($monthly) <span class="mx-2">•</span> <span>Rp {{ number_format($monthly, 0, ',', '.') }}/bulan</span> @endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if ($hasTransit)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $secondaryColor }}15; color: {{ $secondaryColor }}">Sewa per Jam</span>
                                            @endif
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $primaryColor }}12; color: {{ $primaryColor }}">Sewa per Malam</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- ===== What's Around ===== -->
                    @if ($hasMap || $nearbyGroups)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">What's Around {{ $property->name }}</h2>

                            @if ($hasMap)
                                <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
                                    <div id="property-detail-map" style="height:300px;width:100%;"></div>
                                </div>
                            @endif

                            @if ($nearbyGroups)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach ($nearbyGroups as $category => $places)
                                        <div>
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide mb-3 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $primaryColor }}"></span>
                                                {{ $category }}
                                            </h3>
                                            <ul class="space-y-2.5">
                                                @foreach ($places as $place)
                                                    <li class="flex items-start justify-between text-sm">
                                                        <span class="text-gray-700 dark:text-gray-300">{{ $place['name'] ?? '' }}</span>
                                                        @if (!empty($place['distance_km']))
                                                            <span class="text-gray-400 dark:text-gray-500 text-xs ml-4 shrink-0">{{ number_format((float) $place['distance_km'], 1, ',', '.') }} km</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data tempat sekitar properti ini.</p>
                            @endif
                        </div>
                    @endif

                    <!-- ===== Accommodation Policy ===== -->
                    @if ($property->checkin_time || $property->checkout_time || $property->required_documents || $property->checkin_method)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-5">Accommodation Policy & General Information in {{ $property->name }}</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Check-in & Check-out</h3>
                                    </div>
                                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>Check-in: {{ $property->checkin_time ?: '—' }}</li>
                                        <li>Check-out: {{ $property->checkout_time ?: '—' }}</li>
                                    </ul>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Required Documents</h3>
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
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Metode Check-in</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $property->checkin_method ?: '—' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- ===== FAQ ===== -->
                    @if ($faqs)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-5">Pertanyaan Umum (FAQ)</h2>
                            <div class="space-y-3">
                                @foreach ($faqs as $faq)
                                    <details class="group border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                        <summary class="flex items-center justify-between gap-4 px-5 py-4 cursor-pointer text-sm font-semibold text-gray-900 dark:text-white list-none">
                                            {{ $faq['q'] }}
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </summary>
                                        <p class="px-5 pb-4 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $faq['a'] }}</p>
                                    </details>
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
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Tanya Dulu</h2>
                            @if ($whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode('Halo, saya ingin bertanya tentang ' . $property->name) }}"
                                   target="_blank" rel="noopener"
                                   class="w-full inline-flex items-center justify-center px-5 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition" style="background-color: #25d366">
                                    Chat WhatsApp
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- ======== RIGHT COLUMN: Booking (desktop sticky) ======== -->
                @if ($showBookingForm)
                    <div class="hidden lg:block lg:sticky lg:top-24 lg:self-start space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Pesan Apartemen</h2>
                                @include('properties._booking-form', ['prefix' => 'bkf-desktop'])
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Tanya Dulu</h2>
                                <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    @if ($contactPhone)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 shrink-0" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {{ $contactPhone }}
                                        </li>
                                    @endif
                                    @if ($whatsapp)
                                        <li>
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode('Halo, saya ingin bertanya tentang ' . $property->name) }}"
                                               target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-green-600 font-medium hover:underline">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                Chat WhatsApp
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

    <!-- ============ MOBILE FLOATING BOOKING BAR ============ -->
    @if ($showBookingForm)
        <div id="mob-bk-bar" class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-4 py-3">
            <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Mulai dari</p>
                    <p class="text-lg font-bold" style="color: {{ $primaryColor }}">
                        @if ($property->lowestPrice())
                            Rp {{ number_format($property->lowestPrice(), 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <button type="button" id="mob-bk-open" class="px-6 py-3 rounded-full text-white font-semibold text-sm hover:opacity-90 transition" style="background-color: {{ $primaryColor }}">
                    Booking
                </button>
            </div>
        </div>
        <div class="h-20 lg:hidden"></div>
    @endif

    <!-- ============ LIGHTBOX (all photos) with Category Sidebar ============ -->
    <div id="gal-lightbox" class="hidden fixed inset-0 z-[60] flex" role="dialog" aria-modal="true" aria-label="Photo gallery">
        <!-- Dark overlay (clickable to close) -->
        <div class="absolute inset-0 bg-black/90" data-gal-close></div>

        <!-- Close button (top-right) -->
        <button type="button" data-gal-close class="absolute top-3 right-3 z-20 text-white/70 hover:text-white text-3xl leading-none w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition">&times;</button>

        <!-- ===== LEFT SIDEBAR (categories) ===== -->
        <aside id="gal-sidebar" class="relative z-10 w-56 lg:w-64 bg-black/60 backdrop-blur-md border-r border-white/10 flex-shrink-0 flex flex-col overflow-y-auto">
            <div class="p-4 border-b border-white/10">
                <h3 class="text-white/90 text-xs font-semibold uppercase tracking-wider">Categories</h3>
            </div>
            <nav id="gal-cat-list" class="flex-1 p-3 space-y-1">
                <!-- Populated by JavaScript -->
            </nav>
        </aside>

        <!-- ===== RIGHT CONTENT (image viewer) ===== -->
        <div class="relative z-10 flex-1 flex items-center justify-center">
            <button type="button" id="gal-prev" class="absolute left-3 md:left-6 text-white/70 hover:text-white text-5xl z-10 transition">&lsaquo;</button>
            <img id="gal-lightbox-img" src="" alt="" class="relative max-h-[85vh] max-w-[calc(100vw-18rem)] lg:max-w-[calc(100vw-20rem)] rounded-xl shadow-2xl object-contain select-none">
            <button type="button" id="gal-next" class="absolute right-3 md:right-6 text-white/70 hover:text-white text-5xl z-10 transition">&rsaquo;</button>

            <!-- Photo counter (bottom-center) -->
            <div id="gal-counter" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/60 text-sm font-medium bg-black/40 px-3 py-1 rounded-full">
                1 / 1
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
        var mapLat = {{ $property->latitude }};
        var mapLng = {{ $property->longitude }};
        var map = L.map('property-detail-map', { scrollWheelZoom: false }).setView([mapLat, mapLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        L.marker([mapLat, mapLng]).addTo(map)
            .bindPopup({!! json_encode($popupHtml) !!})
            .openPopup();
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

        // State: active category filter ('*' = all photos), current index, current filtered list
        var activeCat = '*';
        var filtered  = photoGallery;   // currently visible photos
        var lbIdx     = 0;              // index within `filtered`

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

        // Render the desktop sidebar + mobile bar
        function renderCategories() {
            var data = buildCategories();

            var html = '';
            html += '<button type="button" class="gal-cat-btn flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm font-medium transition ' +
                    (activeCat === '*' ? 'bg-white/20 text-white' : 'text-white/60 hover:text-white hover:bg-white/10') + '" data-cat="*">' +
                    '<span>All Photos</span>' +
                    '<span class="text-xs ml-2 opacity-70">' + photoGallery.length + '</span>' +
                    '</button>';
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
            mhtml += '<button type="button" class="gal-cat-btn flex-shrink-0 px-4 py-1.5 rounded-full text-xs font-medium transition whitespace-nowrap ' +
                     (activeCat === '*' ? 'bg-white text-black' : 'bg-white/15 text-white/80 hover:bg-white/25') + '" data-cat="*">All (' + photoGallery.length + ')</button>';
            data.cats.forEach(function (c) {
                var isActive = (activeCat === c);
                mhtml += '<button type="button" class="gal-cat-btn flex-shrink-0 px-4 py-1.5 rounded-full text-xs font-medium transition whitespace-nowrap ' +
                         (isActive ? 'bg-white text-black' : 'bg-white/15 text-white/80 hover:bg-white/25') + '" data-cat="' + esc(c) + '">' +
                         esc(c) + ' (' + data.catCount[c] + ')</button>';
            });
            mCatEl.innerHTML = mhtml;
        }

        // Show the photo at `idx` (index within `filtered`)
        function showPhoto(idx) {
            if (!filtered.length) {
                lbImg.src = '';
                lbCnt.textContent = '0 / 0';
                return;
            }
            lbIdx = ((idx % filtered.length) + filtered.length) % filtered.length;
            lbImg.src = filtered[lbIdx].url;
            lbImg.alt = filtered[lbIdx].name || '';
            lbCnt.textContent = (lbIdx + 1) + ' / ' + filtered.length;
        }

        function moveLb(d) {
            showPhoto(lbIdx + d);
        }

        // Open the lightbox, showing the photo at GLOBAL index `globalIdx`
        function openLb(globalIdx) {
            activeCat = '*';
            filtered  = photoGallery;
            renderCategories();
            showPhoto(globalToFilteredIdx(globalIdx));
            lb.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeLb() {
            lb.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Filter to a category ('*' = all)
        function onCatClick(cat) {
            activeCat = cat;
            filtered  = (cat === '*') ? photoGallery.slice() : photoGallery.filter(function (p) {
                return (p.category || 'Other') === cat;
            });
            renderCategories();
            showPhoto(0);
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
