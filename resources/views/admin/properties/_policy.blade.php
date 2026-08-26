@php
    $checkinTime = old('checkin_time', $property->checkin_time ?? '');
    $checkoutTime = old('checkout_time', $property->checkout_time ?? '');
    $checkinMethod = old('checkin_method', $property->checkin_method ?? '');
    $maxDays = old('max_days', $property->max_days ?? '');
    $documents = old('required_documents', $property->required_documents ?? []);
    $places = old('nearby_places', $property->nearby_places ?? []);
    $categories = ['Nearby Places', 'Transportation', 'Entertainment/Attraction', 'Others'];
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
            <p class="text-xs text-gray-500 mt-1">Default: 2 tamu. Berlaku semua tipe kamar.</p>
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

    <!-- Nearby Places -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Tempat di Sekitar Properti</label>
        <p class="text-xs text-gray-500 mb-3">Tampil di section "What's Around". Jarak dalam km (opsional).</p>
        <div id="place-list" class="space-y-2 mb-2">
            @forelse((array) $places as $place)
                <div class="flex gap-2 items-center">
                    <input type="text" name="nearby_places[][name]" value="{{ $place['name'] ?? '' }}"
                           placeholder="Nama tempat"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <select name="nearby_places[][category]"
                            class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ ($place['category'] ?? 'Others') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="nearby_places[][distance_km]" step="0.01" min="0"
                           value="{{ $place['distance_km'] ?? '' }}" placeholder="km"
                           class="w-20 px-2 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" class="place-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>
                </div>
            @empty
                <div class="flex gap-2 items-center">
                    <input type="text" name="nearby_places[][name]" placeholder="Nama tempat"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <select name="nearby_places[][category]"
                            class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="nearby_places[][distance_km]" step="0.01" min="0" placeholder="km"
                           class="w-20 px-2 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" class="place-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>
                </div>
            @endforelse
        </div>
        <button type="button" id="place-add" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Tambah tempat</button>
    </div>
</div>

@push('scripts')
<script>
    // Turbo-safe: this block runs on every full load AND every Turbo body-swap
    // (Turbo re-executes <script> tags inside the swapped <body>). It must NOT
    // be wrapped in a DOMContentLoaded listener — that event does not fire again
    // after a Turbo navigation, which previously left the "+ Tambah dokumen" /
    // "×" (remove) buttons completely inert (the reported bug: Required Documents
    // could not be deleted on the Edit page).
    (function () {
        'use strict';

        var categories = @json($categories);

        function makeOptions(selected) {
            return categories.map(function (c) {
                return '<option value="' + c + '"' + (c === selected ? ' selected' : '') + '>' + c + '</option>';
            }).join('');
        }

        function addDocRow(container) {
            if (!container) return;
            var row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML = '<input type="text" name="required_documents[]" placeholder="Contoh: KTP, SIM" class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' +
                '<button type="button" class="doc-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>';
            container.appendChild(row);
        }

        function addPlaceRow(container) {
            if (!container) return;
            var row = document.createElement('div');
            row.className = 'flex gap-2 items-center';
            row.innerHTML = '<input type="text" name="nearby_places[][name]" placeholder="Nama tempat" class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' +
                '<select name="nearby_places[][category]" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' + makeOptions(null) + '</select>' +
                '<input type="number" name="nearby_places[][distance_km]" step="0.01" min="0" placeholder="km" class="w-20 px-2 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' +
                '<button type="button" class="place-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>';
            container.appendChild(row);
            reindexPlaces();
        }

        // Give every nearby-place row a SHARED explicit index across its 3 fields.
        // Blade renders the rows with name="nearby_places[][name]" etc.; the bare
        // "[]" makes PHP start a NEW array element for every field, so name,
        // category and distance land in DIFFERENT array entries and never group
        // into a single place. Reindexing to nearby_places[i][key] fixes that.
        function reindexPlaces() {
            var list = document.getElementById('place-list');
            if (!list) return;
            var rows = list.children;
            for (var i = 0; i < rows.length; i++) {
                var nameEl = rows[i].querySelector('input[name^="nearby_places"]:not([type="number"])');
                var catEl  = rows[i].querySelector('select[name^="nearby_places"]');
                var kmEl   = rows[i].querySelector('input[type="number"][name^="nearby_places"]');
                if (nameEl) nameEl.name = 'nearby_places[' + i + '][name]';
                if (catEl)  catEl.name  = 'nearby_places[' + i + '][category]';
                if (kmEl)   kmEl.name   = 'nearby_places[' + i + '][distance_km]';
            }
        }

        // Delegated listeners are attached to `document`, which PERSISTS across
        // Turbo body-swaps, so we bind them exactly once (guarded by a global
        // flag) to avoid stacking duplicate handlers on every navigation.
        if (!window.__propertyPolicyBound) {
            window.__propertyPolicyBound = true;

            document.addEventListener('click', function (e) {
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
                    var placeRow = e.target.closest('.flex');
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
