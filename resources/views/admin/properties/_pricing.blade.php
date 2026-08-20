@php
    $selectedTypes = old('unit_types', $property->unit_types ?? []);
    $selectedWeekend = old('weekend_days', $property?->weekendDays() ?? [6, 0]);
    $days = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    // Transit fields (split wd/we)
    $transitFields = [
        't3'  => '3 Jam',
        't6'  => '6 Jam',
        't9'  => '9 Jam',
        't12' => '12 Jam',
        't24' => '24 Jam',
    ];
@endphp

<div class="border-b border-gray-200 pb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Tipe Kamar & Harga</h3>
    <p class="text-sm text-gray-500 mb-5">
        Centang tipe kamar yang tersedia, lalu isi harga per tipe dan per periode.
        Harga yang dikosongkan tidak akan ditampilkan sebagai opsi di frontend.
    </p>

    {{-- ===== ROOM TYPES ===== --}}
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

    {{-- ===== WEEKEND DAYS ===== --}}
    <div class="mb-7">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Hari Weekend
            <span class="text-gray-400 font-normal">(harga weekend berlaku di hari-hari ini — bisa beda per properti)</span>
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
    </div>

    {{-- ===== PRICE TABLES ===== --}}
    @if(!empty($selectedTypes))

    {{-- ---- TRANSIT JAM ---- --}}
    <div class="mb-7">
        <div class="flex items-start justify-between mb-2">
            <div>
                <h4 class="text-sm font-semibold text-gray-700">🕐 Transit Jam</h4>
                <p class="text-xs text-gray-400 mt-0.5">
                    Harga flat per slot jam. Kosongkan slot yang tidak ditawarkan — tidak akan tampil di frontend.
                </p>
            </div>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        @foreach($transitFields as $slot => $slotLabel)
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" colspan="2">{{ $slotLabel }}</th>
                        @endforeach
                    </tr>
                    <tr class="bg-gray-50 border-t border-gray-100">
                        <th class="px-4 py-1.5 text-xs text-gray-400"></th>
                        @foreach($transitFields as $slot => $slotLabel)
                            <th class="px-3 py-1.5 text-center text-xs text-gray-400 font-normal">Weekday</th>
                            <th class="px-3 py-1.5 text-center text-xs text-gray-400 font-normal">Weekend</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'hidden' : '' }}" data-type="{{ $key }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $label }}</td>
                            @foreach($transitFields as $slot => $slotLabel)
                                <td class="px-3 py-3">
                                    <input type="number"
                                           name="prices[{{ $key }}][{{ $slot }}_wd]"
                                           value="{{ old("prices.{$key}.{$slot}_wd", $property?->prices[$key]["{$slot}_wd"] ?? '') }}"
                                           min="0" step="1000"
                                           placeholder="Rp"
                                           class="w-28 px-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm">
                                </td>
                                <td class="px-3 py-3">
                                    <input type="number"
                                           name="prices[{{ $key }}][{{ $slot }}_we]"
                                           value="{{ old("prices.{$key}.{$slot}_we", $property?->prices[$key]["{$slot}_we"] ?? '') }}"
                                           min="0" step="1000"
                                           placeholder="Rp"
                                           class="w-28 px-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---- HARIAN ---- --}}
    <div class="mb-7">
        <div class="mb-2">
            <h4 class="text-sm font-semibold text-gray-700">🌙 Harian (per malam)</h4>
            <p class="text-xs text-gray-400 mt-0.5">
                Dihitung per malam. Kosongkan jika tidak menawarkan sewa harian — tidak akan tampil di frontend.
            </p>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Weekday (per malam)</th>
                        <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Weekend (per malam)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'hidden' : '' }}" data-type="{{ $key }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $label }}</td>
                            <td class="px-3 py-3">
                                <input type="number"
                                       name="prices[{{ $key }}][night_wd]"
                                       value="{{ old("prices.{$key}.night_wd", $property?->prices[$key]['night_wd'] ?? '') }}"
                                       min="0" step="1000"
                                       placeholder="Rp"
                                       class="w-36 px-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm">
                            </td>
                            <td class="px-3 py-3">
                                <input type="number"
                                       name="prices[{{ $key }}][night_we]"
                                       value="{{ old("prices.{$key}.night_we", $property?->prices[$key]['night_we'] ?? '') }}"
                                       min="0" step="1000"
                                       placeholder="Rp"
                                       class="w-36 px-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---- MINGGUAN ---- --}}
    <div class="mb-7">
        <div class="mb-2">
            <h4 class="text-sm font-semibold text-gray-700">📅 Mingguan (flat per minggu)</h4>
            <p class="text-xs text-gray-400 mt-0.5">
                Harga flat untuk 1 minggu (7 malam). Tidak ada split weekday/weekend.
                Kosongkan jika tidak menawarkan sewa mingguan — tidak akan tampil di frontend.
            </p>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga per Minggu</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'hidden' : '' }}" data-type="{{ $key }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $label }}</td>
                            <td class="px-3 py-3">
                                <input type="number"
                                       name="prices[{{ $key }}][weekly]"
                                       value="{{ old("prices.{$key}.weekly", $property?->prices[$key]['weekly'] ?? '') }}"
                                       min="0" step="1000"
                                       placeholder="Kosongkan jika tidak tersedia"
                                       class="w-64 px-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---- BULANAN ---- --}}
    <div class="mb-2">
        <div class="mb-2">
            <h4 class="text-sm font-semibold text-gray-700">🗓️ Bulanan (flat per bulan)</h4>
            <p class="text-xs text-gray-400 mt-0.5">
                Harga flat untuk 1 bulan (30 malam). Tidak ada split weekday/weekend.
                Kosongkan jika tidak menawarkan sewa bulanan — tidak akan tampil di frontend.
            </p>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga per Bulan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'hidden' : '' }}" data-type="{{ $key }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $label }}</td>
                            <td class="px-3 py-3">
                                <input type="number"
                                       name="prices[{{ $key }}][monthly]"
                                       value="{{ old("prices.{$key}.monthly", $property?->prices[$key]['monthly'] ?? '') }}"
                                       min="0" step="10000"
                                       placeholder="Kosongkan jika tidak tersedia"
                                       class="w-64 px-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @else
        <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700">
            Centang minimal satu tipe kamar di atas untuk mengisi harga.
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.querySelectorAll('.type-check').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            document.querySelectorAll('.price-row[data-type="' + this.dataset.type + '"]').forEach(function (row) {
                row.classList.toggle('hidden', !checkbox.checked);
            });
        });
    });
</script>
@endpush
