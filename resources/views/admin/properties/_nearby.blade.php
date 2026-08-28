@php
    // Phase 5: persistent Geoapify POIs for this property.
    // $property       — the Property model
    // $propertyPlaces — collection of PropertyPlace (with `place` eager loaded),
    //                   ordered nearest-first. Falls back to an empty collection.
    $propertyPlaces = $propertyPlaces ?? collect();
    $hasCoords      = $property->latitude !== null && $property->longitude !== null;
    $geoapifyKey    = config('services.geoapify.key');
    $geoapifyMapKey = config('services.geoapify.map_key');
    $hasApiKey      = ! empty($geoapifyKey);
    // SEC-003: config('services.geoapify.map_key') falls back to the server Places
    // key. When they are identical the Places key is shipped to every browser that
    // loads a property page, so surface that to the operator.
    $sharesMapKey   = $hasApiKey && $geoapifyMapKey === $geoapifyKey;
@endphp

<div class="border-b border-gray-200 pb-6">
    <div class="flex items-start justify-between gap-3 mb-2">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">{{ __('Nearby Places') }} (Geoapify)</h3>
            <p class="text-sm text-gray-500 mt-1">
                Titik Menarik (POI) di sekitar properti yang diambil otomatis dari Geoapify.
            </p>
        </div>

        {{-- Resync button — POST form, CSRF protected. Disabled when prerequisites are missing. --}}
        <form method="POST"
              action="{{ route('admin.properties.resync-nearby-places', $property) }}"
              data-turbo="false"
              class="shrink-0">
            @csrf
            {{-- NOTE: use a bound :disabled attribute, NOT the @disabled directive.
                 Inside an <x-component> tag @disabled() compiles to a standalone
                 if/endif block and breaks the component tag compilation. The
                 attribute bag drops `false` and renders `disabled` for `true`. --}}
            <x-secondary-button type="submit" :disabled="! $hasCoords || ! $hasApiKey">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ __('Resync POI') }}
            </x-secondary-button>
        </form>
    </div>

    {{-- Warnings: missing coordinates / missing API key --}}
    @unless($hasCoords)
        <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            Koordinat properti belum diisi. Tambahkan latitude dan longitude terlebih dahulu.
        </div>
    @endunless

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

    {{-- Found POIs table --}}
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">{{ __('Category') }}</th>
                    <th class="px-4 py-2">{{ __('Distance') }}</th>
                    <th class="px-4 py-2">Alamat</th>
                    <th class="px-4 py-2">Sumber</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($propertyPlaces as $propertyPlace)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-900">{{ $propertyPlace->place->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $propertyPlace->place->category ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-600 tabular-nums whitespace-nowrap">{{ $propertyPlace->distance_formatted }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ \Illuminate\Support\Str::limit($propertyPlace->place->address ?? '', 48) }}</td>
                        <td class="px-4 py-2">
                            @if($propertyPlace->source === 'geoapify')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">geoapify</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">manual</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        Data diperbarui setiap kali Resync dilakukan. Antrian berjalan secara sinkronus kecuali queue worker dikonfigurasi.
    </p>
</div>
