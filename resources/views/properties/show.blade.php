@extends('layouts.frontend')

@section('content')
    @php
        $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
        $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
        $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
    @endphp

    <!-- Hero Image -->
    <section class="relative bg-gray-900">
        @if ($property->featuredImage)
            <img src="{{ $property->featuredImage->url }}" alt="{{ $property->name }}"
                 class="w-full h-[40vh] md:h-[55vh] object-cover opacity-80">
        @else
            <div class="w-full h-[40vh] md:h-[55vh] opacity-80"
                 style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
        <div class="absolute bottom-0 inset-x-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
                <a href="{{ route('properties.public.index') }}" class="text-sm text-white/80 hover:text-white inline-flex items-center mb-3">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali ke daftar
                </a>
                <h1 class="text-3xl md:text-5xl font-extrabold text-white">{{ $property->name }}</h1>
                @if ($property->city || $property->province)
                    <p class="text-white/90 mt-2 flex items-center">
                        <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $property->city }}{{ $property->province ? ', ' . $property->province : '' }}
                    </p>
                @endif
            </div>
        </div>
    </section>

    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    @if ($property->description)
                        <div class="bg-white rounded-2xl shadow-sm p-8 mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Tentang Apartemen</h2>
                            <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
                                {!! nl2br(e($property->description)) !!}
                            </div>
                        </div>
                    @endif

                    @if ($property->amenities->isNotEmpty())
                        <div class="bg-white rounded-2xl shadow-sm p-8 mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Fasilitas</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($property->amenities as $amenity)
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium"
                                          style="background-color: {{ $primaryColor }}12; color: {{ $primaryColor }}">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ $amenity->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Units -->
                    @if ($property->units->isNotEmpty())
                        <div class="bg-white rounded-2xl shadow-sm p-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Unit Tersedia</h2>
                            <div class="space-y-6">
                                @foreach ($property->units as $unit)
                                    <div class="flex flex-col md:flex-row gap-6 border border-gray-100 rounded-2xl p-5 hover:shadow-md transition">
                                        <div class="md:w-48 shrink-0">
                                            <div class="aspect-[4/3] rounded-xl overflow-hidden bg-gray-100">
                                                @if ($unit->featuredImage)
                                                    <img src="{{ $unit->featuredImage->url }}" alt="{{ $unit->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-gray-300"
                                                         style="background: linear-gradient(135deg, {{ $secondaryColor }}22, {{ $primaryColor }}22);">
                                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900">{{ $unit->name }}</h3>
                                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 mt-2">
                                                        @if ($unit->size_sqm)
                                                            <span class="inline-flex items-center">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                                                {{ (float) $unit->size_sqm }} m²
                                                            </span>
                                                        @endif
                                                        @if ($unit->bedrooms)
                                                            <span>{{ $unit->bedrooms }} Kamar Tidur</span>
                                                        @endif
                                                        @if ($unit->bathrooms)
                                                            <span>{{ $unit->bathrooms }} Kamar Mandi</span>
                                                        @endif
                                                        @if ($unit->floor)
                                                            <span>Lantai {{ $unit->floor }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if ($unit->status === 'available')
                                                    <span class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-full bg-green-100 text-green-700">Tersedia</span>
                                                @else
                                                    <span class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-full bg-gray-100 text-gray-500">{{ ucfirst($unit->status) }}</span>
                                                @endif
                                            </div>

                                            @if ($unit->description)
                                                <p class="text-sm text-gray-600 mt-3 leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($unit->description), 160) }}</p>
                                            @endif

                                            @if ($unit->amenities->isNotEmpty())
                                                <div class="flex flex-wrap gap-2 mt-3">
                                                    @foreach ($unit->amenities->take(5) as $amenity)
                                                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">{{ $amenity->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                                <div>
                                                    @if ($unit->price_per_night)
                                                        <p class="text-xs text-gray-500">Harga / malam</p>
                                                        <p class="text-xl font-bold" style="color: {{ $primaryColor }}">
                                                            Rp {{ number_format((float) $unit->price_per_night, 0, ',', '.') }}
                                                        </p>
                                                    @endif
                                                    @if ($unit->price_per_month)
                                                        <p class="text-xs text-gray-500 mt-1">Bulanan: Rp {{ number_format((float) $unit->price_per_month, 0, ',', '.') }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    @if ($whatsapp)
                                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode('Halo, saya tertarik dengan unit ' . $unit->name . ' di ' . $property->name) }}"
                                                           target="_blank" rel="noopener"
                                                           class="inline-flex items-center px-4 py-2.5 rounded-full text-sm font-semibold text-white hover:opacity-90 transition"
                                                           style="background-color: #25d366">
                                                            WA
                                                        </a>
                                                    @endif
                                                    @if ($unit->status === 'available')
                                                        <a href="{{ route('bookings.create', $unit->slug) }}"
                                                           class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-semibold text-white hover:opacity-90 transition"
                                                           style="background-color: {{ $primaryColor }}">
                                                            Booking
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm p-8">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Hubungi Kami</h2>
                        <ul class="space-y-4 text-sm text-gray-600">
                            @if ($property->city)
                                <li class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 shrink-0 mt-0.5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ $property->address ? $property->address . ', ' : '' }}{{ $property->city }}{{ $property->province ? ', ' . $property->province : '' }}</span>
                                </li>
                            @endif
                            @if ($contactPhone)
                                <li class="flex items-center space-x-3">
                                    <svg class="w-5 h-5 shrink-0" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span>{{ $contactPhone }}</span>
                                </li>
                            @endif
                            @if ($whatsapp)
                                <li class="flex items-center space-x-3">
                                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener" class="text-green-600 font-medium hover:underline">
                                        Chat WhatsApp
                                    </a>
                                </li>
                            @endif
                        </ul>
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode('Halo, saya ingin bertanya tentang ' . $property->name) }}"
                               target="_blank" rel="noopener"
                               class="mt-6 w-full inline-flex items-center justify-center px-5 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition"
                               style="background-color: #25d366">
                                Tanya via WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
