@php
    $checkinTime = old('checkin_time', $property->checkin_time ?? '');
    $checkoutTime = old('checkout_time', $property->checkout_time ?? '');
    $checkinMethod = old('checkin_method', $property->checkin_method ?? '');
    $maxDays = old('max_days', $property->max_days ?? '');
    $documents = old('required_documents', $property->required_documents ?? []);
    $places = old('nearby_places', $property->nearby_places ?? []);
    // Single source of truth shared with PropertyRequest validation.
    $categoryKeys = array_keys(\App\Models\Property::NEARBY_CATEGORIES);
    $categories = array_combine($categoryKeys, $categoryKeys);
@endphp

<div class="border-b border-gray-200 pb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-2">Kebijakan & Lokasi</h3>
    <p class="text-sm text-gray-500 mb-4">Check-in/out, dokumen, metode check-in, batas maksimal durasi, dan tempat di sekitar properti.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div>
            <label for="checkin_time" class="block text-sm font-medium text-gray-700 mb-2">Check-in Time</label>
            <input type="time" name="checkin_time" id="checkin_time" value="{{ $checkinTime }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="checkout_time" class="block text-sm font-medium text-gray-700 mb-2">Check-out Time</label>
            <input type="time" name="checkout_time" id="checkout_time" value="{{ $checkoutTime }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="max_days" class="block text-sm font-medium text-gray-700 mb-2">Maks. Durasi (malam)</label>
            <input type="number" name="max_days" id="max_days" value="{{ $maxDays }}" min="1"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-500 mt-1">Kosongkan = tanpa batas.</p>
        </div>
        <div>
            <label for="max_guests" class="block text-sm font-medium text-gray-700 mb-2">Maks. Tamu (orang)</label>
            <input type="number" name="max_guests" id="max_guests"
                   value="{{ old('max_guests', $property->max_guests ?? 2) }}" min="1" max="20"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-500 mt-1">Kosongkan = tanpa batas.</p>
        </div>
    </div>

    <div class="mb-6">
        <label for="checkin_method" class="block text-sm font-medium text-gray-700 mb-2">Metode Check-in</label>
        <input type="text" name="checkin_method" id="checkin_method" value="{{ $checkinMethod }}"
               placeholder="Contoh: Self check-in dengan kode pintu yang dikirim via WhatsApp"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
    </div>

    <!-- Required Documents -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Dokumen yang Diperlukan</label>
        <div id="doc-list" class="space-y-2 mb-2">
            @forelse((array) $documents as $doc)
                <div class="flex gap-2">
                    <input type="text" name="required_documents[]" value="{{ $doc }}"
                           placeholder="Contoh: KTP, SIM"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" class="doc-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>
                </div>
            @empty
                <div class="flex gap-2">
                    <input type="text" name="required_documents[]" placeholder="Contoh: KTP, SIM"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" class="doc-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>
                </div>
            @endforelse
        </div>
        <button type="button" id="doc-add" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Tambah dokumen</button>
    </div>

    <!-- Nearby Places / What's Around -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tempat di Sekitar Properti <span class="text-gray-400 font-normal">(What's Around)</span></label>
        <p class="text-xs text-gray-500 mb-3">
            Tambahkan tempat-tempat di sekitar apartemen. Koordinat (lat/lng) digunakan untuk menampilkan pin di peta dan menghitung jarak otomatis — opsional, tapi direkomendasikan.
        </p>

        {{-- Column headers --}}
        <div class="hidden md:grid md:grid-cols-[1fr_160px_90px_90px_36px] gap-2 mb-1 px-1">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Tempat</span>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kategori</span>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Latitude</span>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Longitude</span>
            <span></span>
        </div>

        <div id="place-list" class="space-y-2 mb-3">
            @forelse((array) $places as $i => $place)
                <div class="place-row grid grid-cols-1 md:grid-cols-[1fr_160px_90px_90px_36px] gap-2 items-start">
                    <input type="text"
                           name="nearby_places[{{ $i }}][name]"
                           value="{{ $place['name'] ?? '' }}"
                           placeholder="Nama tempat"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    <select name="nearby_places[{{ $i }}][category]"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                        @foreach($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" {{ ($place['category'] ?? 'Others') === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                    <input type="number"
                           name="nearby_places[{{ $i }}][lat]"
                           value="{{ $place['lat'] ?? '' }}"
                           placeholder="Lat"
                           step="any"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                           title="Latitude (contoh: -6.2088)">
                    <input type="number"
                           name="nearby_places[{{ $i }}][lng]"
                           value="{{ $place['lng'] ?? '' }}"
                           placeholder="Lng"
                           step="any"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                           title="Longitude (contoh: 106.8456)">
                    <button type="button" class="place-remove flex items-center justify-center w-9 h-9 text-red-500 border border-gray-300 rounded-md hover:bg-red-50 shrink-0" aria-label="Hapus">&times;</button>
                </div>
            @empty
                <div class="place-row grid grid-cols-1 md:grid-cols-[1fr_160px_90px_90px_36px] gap-2 items-start">
                    <input type="text"
                           name="nearby_places[0][name]"
                           placeholder="Nama tempat"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    <select name="nearby_places[0][category]"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                        @foreach($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}">{{ $catLabel }}</option>
                        @endforeach
                    </select>
                    <input type="number"
                           name="nearby_places[0][lat]"
                           placeholder="Lat"
                           step="any"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                           title="Latitude">
                    <input type="number"
                           name="nearby_places[0][lng]"
                           placeholder="Lng"
                           step="any"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                           title="Longitude">
                    <button type="button" class="place-remove flex items-center justify-center w-9 h-9 text-red-500 border border-gray-300 rounded-md hover:bg-red-50 shrink-0" aria-label="Hapus">&times;</button>
                </div>
            @endforelse
        </div>
        <button type="button" id="place-add" class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-800 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Tempat
        </button>
    </div>
</div>

@once
@push('scripts')
<script>
    (function () {
        // ── Nearby Places ─────────────────────────────────────────────────────
        var categoryOptions = @json(array_keys($categories));
        var categoryLabels  = @json(array_values($categories));

        function buildCategoryOptions(selectedValue) {
            var html = '';
            for (var i = 0; i < categoryOptions.length; i++) {
                var val = categoryOptions[i];
                var lbl = categoryLabels[i];
                html += '<option value="' + val + '"' + (val === selectedValue ? ' selected' : '') + '>' + lbl + '</option>';
            }
            return html;
        }

        function reindexPlaces() {
            var rows = document.querySelectorAll('#place-list .place-row');
            rows.forEach(function (row, idx) {
                row.querySelectorAll('input, select').forEach(function (el) {
                    if (el.name) {
                        el.name = el.name.replace(/nearby_places\[\d+\]/, 'nearby_places[' + idx + ']');
                    }
                });
            });
        }

        function addPlaceRow(list) {
            var idx = list.querySelectorAll('.place-row').length;
            var row = document.createElement('div');
            row.className = 'place-row grid grid-cols-1 md:grid-cols-[1fr_160px_90px_90px_36px] gap-2 items-start';
            row.innerHTML =
                '<input type="text" name="nearby_places[' + idx + '][name]" placeholder="Nama tempat"' +
                ' class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">' +
                '<select name="nearby_places[' + idx + '][category]"' +
                ' class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">' +
                buildCategoryOptions('Mall/Shopping') +
                '</select>' +
                '<input type="number" name="nearby_places[' + idx + '][lat]" placeholder="Lat" step="any"' +
                ' title="Latitude" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">' +
                '<input type="number" name="nearby_places[' + idx + '][lng]" placeholder="Lng" step="any"' +
                ' title="Longitude" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">' +
                '<button type="button" class="place-remove flex items-center justify-center w-9 h-9 text-red-500 border border-gray-300 rounded-md hover:bg-red-50 shrink-0" aria-label="Hapus">&times;</button>';
            list.appendChild(row);
            row.querySelector('input').focus();
        }

        // ── Required Documents ────────────────────────────────────────────────
        function addDocRow(list) {
            var row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML =
                '<input type="text" name="required_documents[]" placeholder="Contoh: KTP, SIM"' +
                ' class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' +
                '<button type="button" class="doc-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>';
            list.appendChild(row);
            row.querySelector('input').focus();
        }

        // ── Event Delegation ──────────────────────────────────────────────────
        var policySection = document.querySelector('#place-list') && document.querySelector('#place-list').closest('.border-b');
        if (policySection) {
            policySection.addEventListener('click', function (e) {
                if (e.target.closest('#doc-add')) {
                    addDocRow(document.getElementById('doc-list'));
                    return;
                }
                if (e.target.closest('#place-add')) {
                    addPlaceRow(document.getElementById('place-list'));
                    return;
                }
                if (e.target.closest('.doc-remove')) {
                    var docRow = e.target.closest('.flex');
                    if (docRow) docRow.remove();
                    return;
                }
                if (e.target.closest('.place-remove')) {
                    var placeRow = e.target.closest('.place-row');
                    if (placeRow) placeRow.remove();
                    reindexPlaces();
                    return;
                }
            });

            // Reindex nearby-place rows right before the property form submits.
            document.addEventListener('submit', function (e) {
                if (e.target && e.target.id === 'property-form') {
                    reindexPlaces();
                }
            }, true);
        }

        // Normalise any server-rendered nearby-place rows on initial parse.
        reindexPlaces();
    })();
</script>
@endpush
@endonce
