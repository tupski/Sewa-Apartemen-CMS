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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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
    document.addEventListener('DOMContentLoaded', function () {
        var catOptions = '<?php echo json_encode($categories); ?>';

        function addDocRow(container) {
            var row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML = '<input type="text" name="required_documents[]" placeholder="Contoh: KTP, SIM" class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' +
                '<button type="button" class="doc-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>';
            container.appendChild(row);
        }

        function addPlaceRow(container) {
            var row = document.createElement('div');
            row.className = 'flex gap-2 items-center';
            var opts = JSON.parse(catOptions).map(function (c) { return '<option value="' + c + '">' + c + '</option>'; }).join('');
            row.innerHTML = '<input type="text" name="nearby_places[][name]" placeholder="Nama tempat" class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' +
                '<select name="nearby_places[][category]" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' + opts + '</select>' +
                '<input type="number" name="nearby_places[][distance_km]" step="0.01" min="0" placeholder="km" class="w-20 px-2 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">' +
                '<button type="button" class="place-remove px-3 py-2 text-red-500 border border-gray-300 rounded-md hover:bg-red-50">&times;</button>';
            container.appendChild(row);
        }

        document.getElementById('doc-add').addEventListener('click', function () { addDocRow(document.getElementById('doc-list')); });
        document.getElementById('place-add').addEventListener('click', function () { addPlaceRow(document.getElementById('place-list')); });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('doc-remove')) e.target.closest('.flex').remove();
            if (e.target.classList.contains('place-remove')) e.target.closest('.flex').remove();
        });
    });
</script>
@endpush
