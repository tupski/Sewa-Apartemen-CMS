@extends('layouts.admin')

@section('page-title', 'Create New Property')

{{-- ── Leaflet CSS ── --}}
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    /* Map container */
    #property-map { height: 350px; border-radius: 0.5rem; z-index: 1; }
    /* Prevent Leaflet tiles from bleeding outside rounded corners */
    .leaflet-container { border-radius: 0.5rem; }
    /* FAB hover ring */
    #fab-save:focus { outline: 2px solid #3b82f6; outline-offset: 2px; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-24">

    {{-- ── Page Header ── --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.properties.index') }}" class="hover:text-gray-900">Properties</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900">Create New Property</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Create New Property</h2>
    </div>

    {{-- ── Main Form ── --}}
    <form id="property-form"
          method="POST"
          action="{{ route('admin.properties.store') }}"
          enctype="multipart/form-data">
        @csrf

        {{-- ═══════════════════════════════════════════════════════
             Responsive two-column layout: lg = 2/3 + 1/3 sidebar
        ════════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col lg:flex-row gap-6">

            {{-- ──────────────────────────────────────────
                 LEFT COLUMN  (main content)
            ─────────────────────────────────────────── --}}
            <div class="flex-1 min-w-0 space-y-6">

                {{-- ── 1. Basic Information ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-semibold text-gray-900">Basic Information</h3>
                    </div>
                    <div class="p-6 space-y-5">

                        {{-- Property Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Property Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Apartemen Green Bay Pluit"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">The public-facing name of the property.</p>
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Slug <span class="text-red-500">*</span>
                            </label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-300 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition">
                                <span class="inline-flex items-center px-3 bg-gray-50 text-gray-500 text-sm border-r border-gray-300">/p/</span>
                                <input type="text"
                                       name="slug"
                                       id="slug"
                                       value="{{ old('slug') }}"
                                       class="flex-1 px-3 py-2.5 text-sm bg-white focus:outline-none"
                                       required>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Auto-generated from name. Used in the property URL.</p>
                            @error('slug')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Description
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="5"
                                      placeholder="Describe the property..."
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ── 2. Location ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-semibold text-gray-900">Location</h3>
                    </div>
                    <div class="p-6 space-y-5">

                        {{-- Address (full) --}}
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Full Address
                            </label>
                            <input type="text"
                                   name="address"
                                   id="address"
                                   value="{{ old('address') }}"
                                   placeholder="Jl. Pluit Indah Raya, No. 2"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            @error('address')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- City + Province side-by-side on md+ --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                                <input type="text"
                                       name="city"
                                       id="city"
                                       value="{{ old('city') }}"
                                       placeholder="Jakarta"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                @error('city')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="province" class="block text-sm font-medium text-gray-700 mb-1.5">Province</label>
                                <input type="text"
                                       name="province"
                                       id="province"
                                       value="{{ old('province') }}"
                                       placeholder="DKI Jakarta"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                @error('province')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Postal Code --}}
                        <div class="md:w-1/2">
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1.5">Postal Code</label>
                            <input type="text"
                                   name="postal_code"
                                   id="postal_code"
                                   value="{{ old('postal_code') }}"
                                   placeholder="14450"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            @error('postal_code')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ── Leaflet Map Pin Picker ── --}}
                        <div class="pt-2">
                            <p class="text-sm font-medium text-gray-700 mb-2">
                                Pin Lokasi di Peta
                                <span class="text-xs font-normal text-gray-500 ml-1">(klik peta untuk meletakkan pin)</span>
                            </p>

                            {{-- Geocode search --}}
                            <div class="flex gap-2 mb-3">
                                <input type="text"
                                       id="map-search-input"
                                       placeholder="Cari alamat atau nama tempat…"
                                       class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <button type="button"
                                        id="map-search-btn"
                                        class="px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 active:bg-blue-800 transition flex items-center gap-1.5 whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Cari
                                </button>
                            </div>

                            {{-- Map div --}}
                            <div id="property-map" class="w-full border border-gray-200 shadow-inner"></div>

                            {{-- Coordinates display --}}
                            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                                <span id="map-coords-display">Belum ada pin — klik peta atau gunakan pencarian</span>
                            </div>

                            {{-- Hidden lat/lng submitted with form --}}
                            <input type="hidden" name="latitude"  id="latitude"  value="{{ old('latitude') }}">
                            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                        </div>

                    </div>
                </div>

                {{-- ── 3. Pricing ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-semibold text-gray-900">Tipe Kamar &amp; Harga</h3>
                    </div>
                    <div class="p-6">
                        @include('admin.properties._pricing', ['property' => $property ?? null])
                    </div>
                </div>

                {{-- ── 4. Photos ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-semibold text-gray-900">Foto Properti</h3>
                    </div>
                    <div class="p-6">
                        @include('admin.properties._photos', ['property' => $property ?? null])
                    </div>
                </div>

                {{-- ── 5. Policy ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-semibold text-gray-900">Kebijakan &amp; Lokasi</h3>
                    </div>
                    <div class="p-6">
                        @include('admin.properties._policy', ['property' => $property ?? null])
                    </div>
                </div>

                {{-- ── 6. SEO ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-semibold text-gray-900">SEO</h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Meta Title
                            </label>
                            <input type="text"
                                   name="meta_title"
                                   id="meta_title"
                                   value="{{ old('meta_title') }}"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <p class="text-xs text-gray-500 mt-1">Recommended: 50–60 characters.</p>
                            @error('meta_title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Meta Description
                            </label>
                            <textarea name="meta_description"
                                      id="meta_description"
                                      rows="3"
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">{{ old('meta_description') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Recommended: 150–160 characters.</p>
                            @error('meta_description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ── Bottom form actions (desktop convenience) ── --}}
                <div class="flex items-center justify-end gap-3 pt-2 pb-6">
                    <a href="{{ route('admin.properties.index') }}"
                       class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Properti
                    </button>
                </div>

            </div>{{-- /left column --}}


            {{-- ──────────────────────────────────────────
                 RIGHT SIDEBAR  (sticky on lg+)
            ─────────────────────────────────────────── --}}
            <div class="w-full lg:w-80 xl:w-96 shrink-0 space-y-6 lg:sticky lg:top-6 lg:self-start">

                {{-- ── Publish / Status card ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-5 bg-green-500 rounded-full"></div>
                        <h3 class="text-sm font-semibold text-gray-900">Publikasi</h3>
                    </div>
                    <div class="p-5 space-y-4">

                        {{-- Status --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select name="status"
                                    id="status"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    required>
                                <option value="draft"     {{ old('status', 'draft') == 'draft'     ? 'selected' : '' }}>⚫ Draft</option>
                                <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>🟢 Published</option>
                            </select>
                            @error('status')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Is Featured --}}
                        <div class="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-100 rounded-lg">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox"
                                   name="is_featured"
                                   id="is_featured"
                                   value="1"
                                   {{ old('is_featured') ? 'checked' : '' }}
                                   class="h-5 w-5 text-yellow-500 rounded focus:ring-yellow-400 shrink-0">
                            <label for="is_featured" class="text-sm text-gray-700 cursor-pointer">
                                <span class="font-medium">Properti Unggulan</span>
                                <p class="text-xs text-gray-500 mt-0.5">Tampilkan di halaman utama</p>
                            </label>
                        </div>

                    </div>
                </div>

                {{-- ── Amenities card ── --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
                        <h3 class="text-sm font-semibold text-gray-900">Fasilitas (Amenities)</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 gap-2">
                            @forelse($amenities as $amenity)
                                <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                    <input type="checkbox"
                                           name="amenities[]"
                                           value="{{ $amenity->id }}"
                                           class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500 shrink-0">
                                    <span class="text-xs text-gray-700 leading-tight">{{ $amenity->name }}</span>
                                </label>
                            @empty
                                <div class="col-span-3 py-4 text-center text-sm text-gray-500">
                                    Belum ada fasilitas.<br>
                                    <a href="{{ route('admin.amenities.create') }}" class="text-blue-600 underline text-xs">Tambah fasilitas</a>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>{{-- /sidebar --}}

        </div>{{-- /flex layout --}}

    </form>

</div>{{-- /max-w-7xl --}}

{{-- ── Floating Action Button ── --}}
<button type="button"
        id="fab-save"
        onclick="document.getElementById('property-form').submit()"
        title="Simpan Properti"
        class="fixed bottom-20 right-5 z-50 flex items-center gap-2 px-4 py-3 bg-blue-600 text-white text-sm font-semibold rounded-full shadow-lg hover:bg-blue-700 active:scale-95 transition-all duration-150 min-h-[48px]">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
    </svg>
    <span class="hidden sm:inline">Simpan Properti</span>
</button>

@push('scripts')
{{-- ── Leaflet JS ── --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    'use strict';

    // ── Slug auto-generation ──────────────────────────────────────
    document.getElementById('name').addEventListener('input', function () {
        document.getElementById('slug').value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });

    // ── Leaflet map setup ─────────────────────────────────────────
    var initLat = parseFloat('{{ old('latitude', '') }}') || null;
    var initLng = parseFloat('{{ old('longitude', '') }}') || null;

    var defaultCenter = [-2.5, 118.0];
    var defaultZoom  = 5;
    var pinZoom      = 14;

    var map = L.map('property-map').setView(
        (initLat && initLng) ? [initLat, initLng] : defaultCenter,
        (initLat && initLng) ? pinZoom : defaultZoom
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    var marker = null;

    function setPin(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function () {
                var pos = marker.getLatLng();
                updateCoords(pos.lat, pos.lng);
            });
        }
        updateCoords(lat, lng);
    }

    function updateCoords(lat, lng) {
        var latFixed = parseFloat(lat).toFixed(6);
        var lngFixed = parseFloat(lng).toFixed(6);
        document.getElementById('latitude').value  = latFixed;
        document.getElementById('longitude').value = lngFixed;
        document.getElementById('map-coords-display').textContent =
            'Lat: ' + latFixed + '  |  Lng: ' + lngFixed;
    }

    // Click on map to drop pin
    map.on('click', function (e) {
        setPin(e.latlng.lat, e.latlng.lng);
    });

    // Pre-populate if old() values exist
    if (initLat && initLng) {
        setPin(initLat, initLng);
    }

    // ── Geocode search (Nominatim) ────────────────────────────────
    document.getElementById('map-search-btn').addEventListener('click', function () {
        var q = document.getElementById('map-search-input').value.trim();
        if (!q) return;

        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Mencari…';

        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q), {
            headers: { 'Accept-Language': 'id,en' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.length > 0) {
                var lat = parseFloat(data[0].lat);
                var lng = parseFloat(data[0].lon);
                map.setView([lat, lng], pinZoom);
                setPin(lat, lng);
            } else {
                alert('Lokasi tidak ditemukan. Coba kata kunci lain.');
            }
        })
        .catch(function () {
            alert('Gagal menghubungi layanan pencarian. Periksa koneksi internet.');
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> Cari';
        });
    });

    // Allow pressing Enter in search box
    document.getElementById('map-search-input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('map-search-btn').click();
        }
    });
})();
</script>
@endpush

@endsection
