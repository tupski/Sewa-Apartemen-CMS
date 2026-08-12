@php
    $selectedTypes = old('unit_types', $property->unit_types ?? []);
    $selectedWeekend = old('weekend_days', $property?->weekendDays() ?? [6, 0]);
    $days = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
    $priceFields = [
        'night_wd' => 'Harian Weekday',
        'night_we' => 'Harian Weekend',
        't3_wd' => 'Transit 3j Weekday',
        't3_we' => 'Transit 3j Weekend',
        't6_wd' => 'Transit 6j Weekday',
        't6_we' => 'Transit 6j Weekend',
        't9_wd' => 'Transit 9j Weekday',
        't9_we' => 'Transit 9j Weekend',
        't12_wd' => 'Transit 12j Weekday',
        't12_we' => 'Transit 12j Weekend',
        't24_wd' => 'Transit 24j Weekday',
        't24_we' => 'Transit 24j Weekend',
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
    ];
@endphp

<div class="border-b border-gray-200 pb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-2">Tipe Kamar & Harga</h3>
    <p class="text-sm text-gray-500 mb-4">Centang tipe kamar yang tersedia, lalu isi harga per tipe. Kosongkan jika belum tahu / hubungi admin.</p>

    <!-- Room Types -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
            <label class="flex items-center p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer">
                <input type="checkbox"
                       name="unit_types[]"
                       value="{{ $key }}"
                       class="type-check h-4 w-4 text-blue-600 rounded focus:ring-blue-500"
                       data-type="{{ $key }}"
                       {{ in_array($key, (array) $selectedTypes) ? 'checked' : '' }}>
                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <!-- Weekend Days -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Hari Weekend <span class="text-gray-400 font-normal">(harga weekend berlaku di hari ini — bisa beda per properti)</span>
        </label>
        <div class="flex flex-wrap gap-2">
            @foreach($days as $day => $dayLabel)
                <label class="inline-flex items-center px-3 py-2 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox"
                           name="weekend_days[]"
                           value="{{ $day }}"
                           class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500"
                           {{ in_array($day, (array) $selectedWeekend) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-700">{{ $dayLabel }}</span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-1">Contoh: klien yang weekend-nya mulai Jumat centang Jumat + Sabtu; yang mulai Sabtu centang Sabtu + Minggu.</p>
    </div>

    <!-- Price Grid -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    @foreach($priceFields as $fieldKey => $fieldLabel)
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ $fieldLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                    <tr class="price-row" data-type="{{ $key }}" style="{{ in_array($key, (array) $selectedTypes) ? '' : 'display: none;' }}">
                        <td class="px-3 py-2 font-medium text-gray-800 whitespace-nowrap">{{ $label }}</td>
                        @foreach($priceFields as $fieldKey => $fieldLabel)
                            <td class="px-2 py-2">
                                <input type="number"
                                       name="prices[{{ $key }}][{{ $fieldKey }}]"
                                       value="{{ old("prices.{$key}.{$fieldKey}", $property?->prices[$key][$fieldKey] ?? '') }}"
                                       min="0" step="1000"
                                       placeholder="Rp"
                                       class="w-28 px-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.type-check').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var row = document.querySelector('.price-row[data-type="' + this.dataset.type + '"]');
            if (row) {
                row.style.display = this.checked ? '' : 'none';
            }
        });
    });
</script>
@endpush
