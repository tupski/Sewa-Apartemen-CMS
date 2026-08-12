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
            <h1 class="text-3xl md:text-4xl font-bold">Apartemen</h1>
            <p class="text-white/90 mt-2">Temukan apartemen sesuai kebutuhan Anda</p>
        </div>
    </section>

    <section class="py-10 bg-gray-50 min-h-[50vh]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Search / Filters -->
            <form action="{{ route('properties.public.index') }}" method="GET"
                  class="bg-white rounded-2xl shadow-sm p-4 flex flex-col md:flex-row gap-3 mb-8">
                <div class="flex-1">
                    <label for="search" class="sr-only">Nama apartemen</label>
                    <input type="text" name="search" id="search" placeholder="Cari nama apartemen…"
                           value="{{ request('search') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2"
                           style="--tw-ring-color: {{ $primaryColor }}">
                </div>
                <div class="md:w-56">
                    <label for="city" class="sr-only">Kota</label>
                    <input type="text" name="city" id="city" placeholder="Kota / area…"
                           value="{{ request('city') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2"
                           style="--tw-ring-color: {{ $primaryColor }}">
                </div>
                <button type="submit"
                        class="px-8 py-3 rounded-xl text-white font-semibold hover:opacity-90 transition"
                        style="background-color: {{ $primaryColor }}">
                    Cari
                </button>
                @if (request('search') || request('city'))
                    <a href="{{ route('properties.public.index') }}"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm font-medium">
                        Reset
                    </a>
                @endif
            </form>

            @if ($properties->isNotEmpty())
                <p class="text-sm text-gray-500 mb-6">
                    {{ $properties->total() }} apartemen ditemukan
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($properties as $property)
                        <a href="{{ route('properties.public.show', $property->slug) }}"
                           class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition">
                            <div class="relative aspect-[4/3] bg-gray-200">
                                @if ($property->featuredImage)
                                    <img src="{{ $property->featuredImage->url }}" alt="{{ $property->name }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400"
                                         style="background: linear-gradient(135deg, {{ $primaryColor }}22, {{ $secondaryColor }}22);">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    </div>
                                @endif
                                @if ($property->is_featured)
                                    <span class="absolute top-3 left-3 bg-white/95 text-xs font-bold px-3 py-1 rounded-full shadow" style="color: {{ $primaryColor }}">
                                        ★ Unggulan
                                    </span>
                                @endif
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:opacity-80 transition">{{ $property->name }}</h3>
                                <div class="flex items-center text-sm text-gray-500 mt-1">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $property->city ?: 'Tangerang' }}{{ $property->province ? ', ' . $property->province : '' }}
                                </div>
                                @php
                                    $cheapest = $property->units->min('price_per_night');
                                @endphp
                                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                    @if ($cheapest)
                                        <div>
                                            <p class="text-xs text-gray-500">Mulai dari</p>
                                            <p class="text-lg font-bold" style="color: {{ $primaryColor }}">
                                                Rp {{ number_format((float) $cheapest, 0, ',', '.') }}<span class="text-xs font-medium text-gray-500">/malam</span>
                                            </p>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">Hubungi untuk harga</span>
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
                <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
                    <p class="text-4xl mb-4">🏢</p>
                    <p class="text-gray-600">Tidak ada apartemen yang cocok dengan pencarian Anda.</p>
                    <a href="{{ route('properties.public.index') }}" class="inline-block mt-4 text-sm font-semibold hover:opacity-80" style="color: {{ $primaryColor }}">
                        Lihat semua apartemen
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection
