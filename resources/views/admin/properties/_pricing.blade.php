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

@push('head')
<style>
    /*
     * Room-type rows are rendered once and reflow between a horizontally
     * scrollable table (lg+) and stacked cards (< lg) using Tailwind's
     * responsive `display` utilities (block ↔ table-row). The existing
     * "hide unselected type" toggle flips the `.is-hidden` class; because the
     * reflow relies on `lg:table-row` (a media-query rule that would otherwise
     * win on desktop), we force the hidden state with !important so a
     * deselected room type stays hidden at every breakpoint.
     */
    .price-row.is-hidden { display: none !important; }
</style>
@endpush

<div class="border-b border-gray-200 pb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Tipe Kamar & Harga</h3>
    <p class="text-sm text-gray-500 mb-5">
        Centang tipe kamar yang tersedia, lalu isi harga per tipe dan per periode.
        Harga yang dikosongkan tidak akan ditampilkan sebagai opsi di frontend.
    </p>

    {{-- ===== ROOM TYPES ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 mb-6">
        @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
            <label class="flex items-center min-h-[44px] px-3 py-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition select-none">
                <input type="checkbox"
                       name="unit_types[]"
                       value="{{ $key }}"
                       class="type-check h-5 w-5 lg:h-4 lg:w-4 text-blue-600 rounded focus:ring-blue-500"
                       data-type="{{ $key }}"
                       {{ in_array($key, (array) $selectedTypes) ? 'checked' : '' }}>
                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
            </label>
        @endforeach
    </div>

    {{-- ===== WEEKEND DAYS ===== --}}
    <div class="mb-7">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Hari Weekend (Override per Properti)
        </label>
        <p class="text-xs text-gray-400 mb-2">
            Pilih hari weekend untuk properti ini. Jika tidak dipilih, sistem akan menggunakan konfigurasi global
            dari <strong>Settings → Pricing</strong>. Centang semua hari yang ingin dikenakan harga weekend.
        </p>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:flex md:flex-wrap gap-2">
            @foreach($days as $day => $dayLabel)
                <label class="inline-flex items-center justify-center md:justify-start min-h-[44px] px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition select-none">
                    <input type="checkbox"
                           name="weekend_days[]"
                           value="{{ $day }}"
                           class="h-5 w-5 md:h-4 md:w-4 text-blue-600 rounded focus:ring-blue-500"
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

        {{-- overflow-x scroll is scoped to THIS container on lg+; on mobile the
             table reflows to stacked cards so the page body never scrolls. --}}
        <div class="lg:overflow-x-auto lg:rounded-lg lg:border lg:border-gray-200">
            <table class="w-full block lg:table lg:min-w-full lg:divide-y lg:divide-gray-200">
                <thead class="hidden lg:table-header-group bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 border-r border-gray-100 px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        @foreach($transitFields as $slot => $slotLabel)
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" colspan="2">{{ $slotLabel }}</th>
                        @endforeach
                    </tr>
                    <tr class="bg-gray-50 border-t border-gray-100">
                        <th class="sticky left-0 z-10 bg-gray-50 border-r border-gray-100 px-4 py-1.5 text-xs text-gray-400"></th>
                        @foreach($transitFields as $slot => $slotLabel)
                            <th class="px-3 py-1.5 text-center text-xs text-gray-400 font-normal">Weekday</th>
                            <th class="px-3 py-1.5 text-center text-xs text-gray-400 font-normal">Weekend</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="block lg:table-row-group lg:bg-white lg:divide-y lg:divide-gray-100 space-y-3 lg:space-y-0">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'is-hidden' : '' }} block lg:table-row rounded-lg border border-gray-200 lg:border-0 overflow-hidden bg-white" data-type="{{ $key }}">
                            <td class="block lg:table-cell lg:sticky lg:left-0 lg:z-10 lg:border-r lg:border-gray-100 bg-gray-50 lg:bg-white px-4 py-2.5 lg:py-3 text-sm font-semibold lg:font-medium text-gray-800 lg:w-24">{{ $label }}</td>
                            @foreach($transitFields as $slot => $slotLabel)
                                <td class="block lg:table-cell px-4 lg:px-3 py-2 lg:py-3 border-t border-gray-100 lg:border-t-0">
                                    <div class="lg:hidden text-xs font-semibold text-gray-600 mb-1">{{ $slotLabel }}</div>
                                    <div class="flex items-center justify-between gap-3 lg:block">
                                        <span class="lg:hidden text-xs text-gray-500">Weekday</span>
                                        <x-money-input
                                            :name="'prices['.$key.']['.$slot.'_wd]'"
                                            :value="old('prices.'.$key.'.'.$slot.'_wd', $property?->prices[$key][$slot.'_wd'] ?? '')"
                                            wrapperClass="w-32 lg:w-auto shrink-0"
                                            inputClass="w-full lg:w-28 pr-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm" />
                                    </div>
                                </td>
                                <td class="block lg:table-cell px-4 lg:px-3 py-2 lg:py-3">
                                    <div class="flex items-center justify-between gap-3 lg:block">
                                        <span class="lg:hidden text-xs text-gray-500">Weekend</span>
                                        <x-money-input
                                            :name="'prices['.$key.']['.$slot.'_we]'"
                                            :value="old('prices.'.$key.'.'.$slot.'_we', $property?->prices[$key][$slot.'_we'] ?? '')"
                                            wrapperClass="w-32 lg:w-auto shrink-0"
                                            inputClass="w-full lg:w-28 pr-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm" />
                                    </div>
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
        <div class="lg:overflow-x-auto lg:rounded-lg lg:border lg:border-gray-200">
            <table class="w-full block lg:table lg:min-w-full lg:divide-y lg:divide-gray-200">
                <thead class="hidden lg:table-header-group bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 border-r border-gray-100 px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Weekday (per malam)</th>
                        <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Weekend (per malam)</th>
                    </tr>
                </thead>
                <tbody class="block lg:table-row-group lg:bg-white lg:divide-y lg:divide-gray-100 space-y-3 lg:space-y-0">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'is-hidden' : '' }} block lg:table-row rounded-lg border border-gray-200 lg:border-0 overflow-hidden bg-white" data-type="{{ $key }}">
                            <td class="block lg:table-cell lg:sticky lg:left-0 lg:z-10 lg:border-r lg:border-gray-100 bg-gray-50 lg:bg-white px-4 py-2.5 lg:py-3 text-sm font-semibold lg:font-medium text-gray-800 lg:w-24">{{ $label }}</td>
                            <td class="block lg:table-cell px-4 lg:px-3 py-2 lg:py-3 border-t border-gray-100 lg:border-t-0">
                                <div class="flex items-center justify-between gap-3 lg:block">
                                    <span class="lg:hidden text-xs text-gray-500">Weekday</span>
                                    <x-money-input
                                        :name="'prices['.$key.'][night_wd]'"
                                        :value="old('prices.'.$key.'.night_wd', $property?->prices[$key]['night_wd'] ?? '')"
                                        wrapperClass="w-40 lg:w-auto shrink-0"
                                        inputClass="w-full lg:w-36 pr-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm" />
                                </div>
                            </td>
                            <td class="block lg:table-cell px-4 lg:px-3 py-2 lg:py-3 border-t border-gray-100 lg:border-t-0">
                                <div class="flex items-center justify-between gap-3 lg:block">
                                    <span class="lg:hidden text-xs text-gray-500">Weekend</span>
                                    <x-money-input
                                        :name="'prices['.$key.'][night_we]'"
                                        :value="old('prices.'.$key.'.night_we', $property?->prices[$key]['night_we'] ?? '')"
                                        wrapperClass="w-40 lg:w-auto shrink-0"
                                        inputClass="w-full lg:w-36 pr-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm" />
                                </div>
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
        <div class="lg:overflow-x-auto lg:rounded-lg lg:border lg:border-gray-200">
            <table class="w-full block lg:table lg:min-w-full lg:divide-y lg:divide-gray-200">
                <thead class="hidden lg:table-header-group bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 border-r border-gray-100 px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga per Minggu</th>
                    </tr>
                </thead>
                <tbody class="block lg:table-row-group lg:bg-white lg:divide-y lg:divide-gray-100 space-y-3 lg:space-y-0">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'is-hidden' : '' }} block lg:table-row rounded-lg border border-gray-200 lg:border-0 overflow-hidden bg-white" data-type="{{ $key }}">
                            <td class="block lg:table-cell lg:sticky lg:left-0 lg:z-10 lg:border-r lg:border-gray-100 bg-gray-50 lg:bg-white px-4 py-2.5 lg:py-3 text-sm font-semibold lg:font-medium text-gray-800 lg:w-24">{{ $label }}</td>
                            <td class="block lg:table-cell px-4 lg:px-3 py-2 lg:py-3 border-t border-gray-100 lg:border-t-0">
                                <div class="flex items-center justify-between gap-3 lg:block">
                                    <span class="lg:hidden text-xs text-gray-500">Harga per Minggu</span>
                                    <x-money-input
                                        :name="'prices['.$key.'][weekly]'"
                                        :value="old('prices.'.$key.'.weekly', $property?->prices[$key]['weekly'] ?? '')"
                                        placeholder="Kosongkan jika tidak tersedia"
                                        wrapperClass="w-48 sm:w-64 lg:w-auto shrink-0"
                                        inputClass="w-full lg:w-64 pr-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm" />
                                </div>
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
        <div class="lg:overflow-x-auto lg:rounded-lg lg:border lg:border-gray-200">
            <table class="w-full block lg:table lg:min-w-full lg:divide-y lg:divide-gray-200">
                <thead class="hidden lg:table-header-group bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 border-r border-gray-100 px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Tipe</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga per Bulan</th>
                    </tr>
                </thead>
                <tbody class="block lg:table-row-group lg:bg-white lg:divide-y lg:divide-gray-100 space-y-3 lg:space-y-0">
                    @foreach(\App\Models\Property::UNIT_TYPES as $key => $label)
                        <tr class="price-row {{ !in_array($key, (array)$selectedTypes) ? 'is-hidden' : '' }} block lg:table-row rounded-lg border border-gray-200 lg:border-0 overflow-hidden bg-white" data-type="{{ $key }}">
                            <td class="block lg:table-cell lg:sticky lg:left-0 lg:z-10 lg:border-r lg:border-gray-100 bg-gray-50 lg:bg-white px-4 py-2.5 lg:py-3 text-sm font-semibold lg:font-medium text-gray-800 lg:w-24">{{ $label }}</td>
                            <td class="block lg:table-cell px-4 lg:px-3 py-2 lg:py-3 border-t border-gray-100 lg:border-t-0">
                                <div class="flex items-center justify-between gap-3 lg:block">
                                    <span class="lg:hidden text-xs text-gray-500">Harga per Bulan</span>
                                    <x-money-input
                                        :name="'prices['.$key.'][monthly]'"
                                        :value="old('prices.'.$key.'.monthly', $property?->prices[$key]['monthly'] ?? '')"
                                        placeholder="Kosongkan jika tidak tersedia"
                                        wrapperClass="w-48 sm:w-64 lg:w-auto shrink-0"
                                        inputClass="w-full lg:w-64 pr-2 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right text-sm" />
                                </div>
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

{{-- ===== PROMO RATES SECTION ===== --}}
@if($property?->exists)
<div class="pt-8" id="promo-rates-section">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Harga Promosi</h3>
    <p class="text-sm text-gray-500 mb-5">
        Tambahkan harga promo yang aktif pada hari dan jam tertentu. Promo akan otomatis diterapkan
        di halaman booking jika kondisi cocok dengan pilihan tamu.
    </p>

    {{-- Existing promos table --}}
    <div id="promo-list" class="mb-4">
        @if($property->promoRates->isEmpty())
            <div id="promo-empty" class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-500">
                Belum ada harga promo untuk properti ini.
            </div>
        @else
            <div id="promo-empty" class="hidden rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-500">
                Belum ada harga promo untuk properti ini.
            </div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-gray-200 {{ $property->promoRates->isEmpty() ? 'hidden' : '' }}" id="promo-table-wrap">
            <table class="min-w-full divide-y divide-gray-200" id="promo-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Nama Promo</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Berlaku Untuk</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Jam</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Tipe Booking</th>
                        <th class="px-3 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Harga</th>
                        <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="promo-tbody">
                    @foreach($property->promoRates as $promo)
                    <tr id="promo-row-{{ $promo->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $promo->name }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">
                            @php
                                $appliesToLabel = [
                                    'all' => 'Semua Hari',
                                    'weekday' => 'Weekday',
                                    'weekend' => 'Weekend',
                                    'custom' => 'Hari Tertentu: ' . implode(', ', array_map(fn($d) => ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][$d] ?? $d, $promo->active_days ?? [])),
                                ];
                            @endphp
                            {{ $appliesToLabel[$promo->applies_to] ?? $promo->applies_to }}
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ substr($promo->start_time, 0, 5) }} – {{ substr($promo->end_time, 0, 5) }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">
                            {{ ['all'=>'Semua','night'=>'Malam','transit'=>'Transit','weekly'=>'Mingguan','monthly'=>'Bulanan'][$promo->booking_type] ?? $promo->booking_type }}
                            @if($promo->booking_type === 'transit' && $promo->duration_hours)
                                ({{ $promo->duration_hours }} jam)
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-800 text-right font-medium">Rp {{ number_format($promo->price, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $promo->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <button type="button"
                                    class="text-blue-600 hover:text-blue-800 text-xs mr-2 promo-edit-btn"
                                    data-promo='@json($promo)'
                                    data-id="{{ $promo->id }}">
                                Edit
                            </button>
                            <button type="button"
                                    class="text-red-600 hover:text-red-800 text-xs promo-delete-btn"
                                    data-id="{{ $promo->id }}"
                                    data-name="{{ $promo->name }}">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add promo button --}}
    <button type="button"
            id="promo-add-btn"
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
        + Tambah Promo
    </button>

    {{-- Promo form (hidden by default) --}}
    <div id="promo-form-wrap" class="hidden mt-5 p-5 border border-indigo-200 rounded-lg bg-indigo-50">
        <h4 class="text-sm font-semibold text-indigo-800 mb-4" id="promo-form-title">Tambah Harga Promo</h4>
        <input type="hidden" id="promo-edit-id">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Promo <span class="text-red-500">*</span></label>
                <input type="text" id="promo-name" placeholder="cth. Promo Malam"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Harga Promo (Rp) <span class="text-red-500">*</span></label>
                <x-money-input
                    id="promo-price"
                    name="promo_price_display"
                    placeholder="0"
                    inputClass="w-full py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 text-right" />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Berlaku Untuk <span class="text-red-500">*</span></label>
                <select id="promo-applies-to"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">Semua Hari</option>
                    <option value="weekday">Hari Weekday</option>
                    <option value="weekend">Hari Weekend</option>
                    <option value="custom">Hari Tertentu</option>
                </select>
            </div>

            <div id="promo-active-days-wrap" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Hari</label>
                <div class="flex flex-wrap gap-2">
                    @foreach(['0'=>'Min','1'=>'Sen','2'=>'Sel','3'=>'Rab','4'=>'Kam','5'=>'Jum','6'=>'Sab'] as $dayVal => $dayShort)
                    <label class="inline-flex items-center gap-1 px-2 py-1.5 border border-gray-200 rounded cursor-pointer hover:bg-white">
                        <input type="checkbox" class="promo-day-check h-3.5 w-3.5 text-blue-600" value="{{ $dayVal }}">
                        <span class="text-xs text-gray-700">{{ $dayShort }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Mulai Jam <span class="text-red-500">*</span></label>
                <input type="time" id="promo-start-time"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Hingga Jam <span class="text-red-500">*</span></label>
                <input type="time" id="promo-end-time"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tipe Booking <span class="text-red-500">*</span></label>
                <select id="promo-booking-type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">Semua</option>
                    <option value="night">Per Malam</option>
                    <option value="transit">Transit</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                </select>
            </div>

            <div id="promo-duration-wrap" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">Durasi Transit</label>
                <select id="promo-duration"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Durasi</option>
                    <option value="3">3 Jam</option>
                    <option value="6">6 Jam</option>
                    <option value="9">9 Jam</option>
                    <option value="12">12 Jam</option>
                    <option value="24">24 Jam</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-gray-700">Status Aktif</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="promo-is-active" class="sr-only peer" checked>
                    <div class="w-10 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>

        <p id="promo-form-error" class="hidden text-sm text-red-600 mt-2"></p>

        <div class="flex gap-2 mt-4">
            <button type="button" id="promo-save-btn"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
                Simpan Promo
            </button>
            <button type="button" id="promo-cancel-btn"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
                Batal
            </button>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    document.querySelectorAll('.type-check').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            document.querySelectorAll('.price-row[data-type="' + this.dataset.type + '"]').forEach(function (row) {
                row.classList.toggle('is-hidden', !checkbox.checked);
            });
        });
    });

    // ===== PROMO RATES JS =====
    (function () {
        var propertyId = {{ $property?->id ?? 'null' }};
        var csrfToken  = document.querySelector('meta[name="csrf-token"]').content;

        var addBtn       = document.getElementById('promo-add-btn');
        var formWrap     = document.getElementById('promo-form-wrap');
        var cancelBtn    = document.getElementById('promo-cancel-btn');
        var saveBtn      = document.getElementById('promo-save-btn');
        var formTitle    = document.getElementById('promo-form-title');
        var editIdInput  = document.getElementById('promo-edit-id');
        var errorP       = document.getElementById('promo-form-error');
        var appliesTo    = document.getElementById('promo-applies-to');
        var activeDaysWrap = document.getElementById('promo-active-days-wrap');
        var bookingType  = document.getElementById('promo-booking-type');
        var durationWrap = document.getElementById('promo-duration-wrap');
        var tableWrap    = document.getElementById('promo-table-wrap');
        var emptyMsg     = document.getElementById('promo-empty');
        var tbody        = document.getElementById('promo-tbody');

        if (!addBtn) return; // Not on edit page

        addBtn.addEventListener('click', function () {
            resetForm();
            formTitle.textContent = 'Tambah Harga Promo';
            formWrap.classList.remove('hidden');
            addBtn.classList.add('hidden');
        });

        cancelBtn.addEventListener('click', function () {
            formWrap.classList.add('hidden');
            addBtn.classList.remove('hidden');
            resetForm();
        });

        appliesTo.addEventListener('change', function () {
            activeDaysWrap.classList.toggle('hidden', this.value !== 'custom');
        });

        bookingType.addEventListener('change', function () {
            durationWrap.classList.toggle('hidden', this.value !== 'transit');
        });

        saveBtn.addEventListener('click', function () {
            var id = editIdInput.value;
            var payload = buildPayload();
            if (!payload) return;

            var url    = id
                ? '/admin/properties/' + propertyId + '/promos/' + id
                : '/admin/properties/' + propertyId + '/promos';
            var method = id ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    showError(data.message || 'Terjadi kesalahan.');
                }
            })
            .catch(function () { showError('Gagal menyimpan promo. Coba lagi.'); });
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('promo-edit-btn')) {
                var promo = JSON.parse(e.target.dataset.promo);
                populateForm(promo);
                formTitle.textContent = 'Edit Harga Promo';
                formWrap.classList.remove('hidden');
                addBtn.classList.add('hidden');
                formWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            if (e.target.classList.contains('promo-delete-btn')) {
                var id   = e.target.dataset.id;
                var name = e.target.dataset.name;
                if (!confirm('Hapus promo "' + name + '"?')) return;

                fetch('/admin/properties/' + propertyId + '/promos/' + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('promo-row-' + id)?.remove();
                        if (!tbody.querySelector('tr')) {
                            tableWrap.classList.add('hidden');
                            emptyMsg.classList.remove('hidden');
                        }
                    }
                });
            }
        });

        function buildPayload() {
            var name      = document.getElementById('promo-name').value.trim();
            // Strip thousand separators added by the [data-money] handler so we
            // send a plain integer (e.g. "150.000" → 150000).
            var price     = document.getElementById('promo-price').value.replace(/\D/g, '');
            var startTime = document.getElementById('promo-start-time').value;
            var endTime   = document.getElementById('promo-end-time').value;
            var applies   = appliesTo.value;
            var bType     = bookingType.value;
            var duration  = document.getElementById('promo-duration').value;
            var isActive  = document.getElementById('promo-is-active').checked;

            if (!name || !price || !startTime || !endTime) {
                showError('Nama, harga, dan jam wajib diisi.');
                return null;
            }

            var activeDays = null;
            if (applies === 'custom') {
                activeDays = Array.from(document.querySelectorAll('.promo-day-check:checked')).map(function (c) {
                    return parseInt(c.value);
                });
            }

            hideError();
            return {
                name: name,
                applies_to: applies,
                active_days: activeDays,
                start_time: startTime,
                end_time: endTime,
                price: parseInt(price),
                booking_type: bType,
                duration_hours: (bType === 'transit' && duration) ? parseInt(duration) : null,
                is_active: isActive,
            };
        }

        function populateForm(promo) {
            editIdInput.value = promo.id;
            document.getElementById('promo-name').value       = promo.name;
            // Set raw value then trigger the money formatter to add separators.
            var promoPriceEl = document.getElementById('promo-price');
            promoPriceEl.value = promo.price;
            promoPriceEl.dispatchEvent(new Event('input', { bubbles: true }));
            document.getElementById('promo-start-time').value = promo.start_time ? promo.start_time.substring(0, 5) : '';
            document.getElementById('promo-end-time').value   = promo.end_time ? promo.end_time.substring(0, 5) : '';
            appliesTo.value = promo.applies_to || 'all';
            bookingType.value = promo.booking_type || 'all';
            document.getElementById('promo-is-active').checked = promo.is_active;
            activeDaysWrap.classList.toggle('hidden', promo.applies_to !== 'custom');
            durationWrap.classList.toggle('hidden', promo.booking_type !== 'transit');

            document.querySelectorAll('.promo-day-check').forEach(function (c) {
                c.checked = promo.active_days && promo.active_days.includes(parseInt(c.value));
            });

            if (promo.booking_type === 'transit' && promo.duration_hours) {
                document.getElementById('promo-duration').value = promo.duration_hours;
            }
        }

        function resetForm() {
            editIdInput.value = '';
            document.getElementById('promo-name').value      = '';
            document.getElementById('promo-price').value     = '';
            document.getElementById('promo-start-time').value = '';
            document.getElementById('promo-end-time').value  = '';
            appliesTo.value  = 'all';
            bookingType.value = 'all';
            document.getElementById('promo-is-active').checked = true;
            document.getElementById('promo-duration').value  = '';
            activeDaysWrap.classList.add('hidden');
            durationWrap.classList.add('hidden');
            document.querySelectorAll('.promo-day-check').forEach(function (c) { c.checked = false; });
            hideError();
        }

        function showError(msg) {
            errorP.textContent = msg;
            errorP.classList.remove('hidden');
        }

        function hideError() {
            errorP.textContent = '';
            errorP.classList.add('hidden');
        }
    })();
</script>
@endpush
