@php
    // Phase 5: persistent Geoapify POIs for this property.
    // $property       — the Property model, or null on the create screen
    // $propertyPlaces — collection of PropertyPlace (with `place` eager loaded),
    //                   ordered nearest-first. Falls back to an empty collection.
    $propertyPlaces = $propertyPlaces ?? collect();
    $property = $property ?? null;
    $exists = (bool) $property?->exists;
    $hasCoords = $exists && $property->latitude !== null && $property->longitude !== null;
    $geoapifyKey = config('services.geoapify.key');
    $geoapifyMapKey = config('services.geoapify.map_key');
    $hasApiKey = ! empty($geoapifyKey);
    // SEC-003: config('services.geoapify.map_key') falls back to the server Places
    // key. When they are identical the Places key is shipped to every browser that
    // loads a property page, so surface that to the operator.
    $sharesMapKey = $hasApiKey && $geoapifyMapKey === $geoapifyKey;
@endphp

<div class="border-b border-gray-200 pb-6">
    <div class="flex items-start justify-between gap-3 mb-2">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">{{ __('Nearby Places') }} (Geoapify)</h3>
            <p class="text-sm text-gray-500 mt-1">
                Titik Menarik (POI) di sekitar properti yang diambil otomatis dari Geoapify.
            </p>
        </div>

        {{-- Resync trigger.

             NOT a <form>: this partial is included INSIDE the main property
             <form> on the edit screen, and nested forms are invalid HTML — the
             browser discards the inner form, so the button submitted the OUTER
             property-update form instead and navigated away to the property
             list (no POI sync ever ran). It is now a plain button that POSTs via
             fetch() and swaps the table in place, so the page never navigates. --}}
        @if($exists)
            <x-secondary-button type="button"
                                id="poi-resync-btn"
                                onclick="window.propertyPoiResync(this)"
                                data-url="{{ route('admin.properties.resync-nearby-places', $property) }}"
                                :disabled="! $hasCoords || ! $hasApiKey"
                                class="shrink-0">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-poi-spinner>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span data-poi-label>{{ __('Resync POI') }}</span>
            </x-secondary-button>
        @else
            {{-- Create screen: there is no property id yet, so no route to call. --}}
            <x-secondary-button type="button" disabled class="shrink-0">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ __('Resync POI') }}
            </x-secondary-button>
        @endif
    </div>

    {{-- Create screen: explain why syncing is unavailable instead of pretending it works --}}
    @unless($exists)
        <div class="mt-3 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
            Simpan properti terlebih dahulu (dengan pin lokasi di peta) untuk bisa menyinkronkan POI dari Geoapify.
        </div>
    @endunless

    {{-- Warnings: missing coordinates / missing API key --}}
    @if($exists && ! $hasCoords)
        <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            Koordinat properti belum diisi. Tambahkan latitude dan longitude terlebih dahulu.
        </div>
    @endif

    @unless($hasApiKey)
        <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            GEOAPIFY_API_KEY belum dikonfigurasi.
        </div>
    @endunless

    {{-- SEC-003: browser map key is the same as the server Places key --}}
    @if($sharesMapKey)
        <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            GEOAPIFY_MAP_KEY belum diatur, sehingga GEOAPIFY_API_KEY (kunci Places API di server) ikut dikirim ke browser pengunjung pada halaman properti. Setel GEOAPIFY_MAP_KEY terpisah yang dibatasi per domain/referrer agar kunci Places API tidak terekspos.
        </div>
    @endif

    {{-- Inline result banner, filled by the resync fetch (no page navigation) --}}
    <div id="poi-resync-message" class="mt-3 hidden rounded-md px-4 py-3 text-sm"></div>

    {{-- Found POIs table — replaced wholesale by the resync response --}}
    <div id="poi-table-wrap" class="mt-4">
        @include('admin.properties._nearby-table', ['propertyPlaces' => $propertyPlaces])
    </div>

    <p class="text-xs text-gray-400 mt-3">
        Data diperbarui setiap kali Resync dilakukan. Antrian berjalan secara sinkronus kecuali queue worker dikonfigurasi.
    </p>
</div>

@push('scripts')
<script>
// Resync POI without leaving the page.
//
// Defined as a single global (idempotent across Turbo body-swaps, since the
// script re-runs and simply reassigns the same function) and wired through an
// inline onclick, so no listener is ever stacked twice.
window.propertyPoiResync = function (btn) {
    'use strict';

    if (!btn || btn.disabled) return;

    var url     = btn.dataset.url;
    var label   = btn.querySelector('[data-poi-label]');
    var spinner = btn.querySelector('[data-poi-spinner]');
    var banner  = document.getElementById('poi-resync-message');
    var wrap    = document.getElementById('poi-table-wrap');
    var tokenEl = document.querySelector('meta[name="csrf-token"]');

    if (!url || !tokenEl) return;

    var original = label ? label.textContent : '';

    function showBanner(message, ok) {
        if (!banner) return;
        banner.textContent = message;
        banner.className = 'mt-3 rounded-md px-4 py-3 text-sm border ' + (ok
            ? 'bg-green-50 border-green-200 text-green-800'
            : 'bg-red-50 border-red-200 text-red-800');
    }

    btn.disabled = true;
    if (label) label.textContent = 'Menyinkronkan…';
    if (spinner) spinner.classList.add('animate-spin');

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': tokenEl.content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function (res) {
        return res.json().then(function (data) {
            return { ok: res.ok, status: res.status, data: data };
        }).catch(function () {
            return { ok: false, status: res.status, data: {} };
        });
    })
    .then(function (result) {
        var data = result.data || {};
        var message = data.message
            || (result.status === 429
                ? 'Terlalu banyak permintaan sinkronisasi. Coba lagi beberapa menit lagi.'
                : 'Gagal menyinkronkan POI.');
        var ok = !!(result.ok && data.success);

        if (ok && typeof data.html === 'string' && wrap) {
            wrap.innerHTML = data.html;
        }

        showBanner(message, ok);
        if (typeof window.toast === 'function') {
            window.toast(message, ok ? 'success' : 'error');
        }
    })
    .catch(function () {
        var message = 'Gagal menghubungi server. Periksa koneksi lalu coba lagi.';
        showBanner(message, false);
        if (typeof window.toast === 'function') window.toast(message, 'error');
    })
    .finally(function () {
        btn.disabled = false;
        if (label) label.textContent = original;
        if (spinner) spinner.classList.remove('animate-spin');
    });
};
</script>
@endpush
