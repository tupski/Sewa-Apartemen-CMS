@php
    $maxDays = $property->maxBookingDays();

    // Build active types: only those with at least one price > 0
    $activeTypes = collect($property->unit_types ?? [])
        ->filter(function ($type) use ($property) {
            $prices = $property->prices[$type] ?? [];
            return collect($prices)->contains(fn($v) => $v !== null && $v !== '' && (float)$v > 0);
        })
        ->values();

    $usePills = $activeTypes->count() <= 4;
@endphp

<div data-bkf class="space-y-4">

    {{-- ====== ROOM TYPE SELECTOR ====== --}}
    @if ($activeTypes->count() > 1)
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Tipe Kamar</label>

            @if ($usePills)
                {{-- Pills for ≤4 types --}}
                <div class="flex flex-wrap gap-2" role="group" aria-label="Pilih tipe kamar">
                    @foreach ($activeTypes as $i => $type)
                        <button type="button"
                                class="bkf-room-pill px-3 py-1.5 rounded-full text-sm font-medium border transition
                                       {{ $i === 0 ? 'bkf-pill-active' : 'bkf-pill-inactive' }}"
                                data-type="{{ $type }}"
                                aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
                            {{ $property->typeLabel($type) }}
                        </button>
                    @endforeach
                </div>
            @else
                {{-- Dropdown for >4 types --}}
                <select id="{{ $prefix }}-room-type"
                        class="bkf-room-type w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                        aria-label="Tipe kamar">
                    @foreach ($activeTypes as $type)
                        <option value="{{ $type }}">{{ $property->typeLabel($type) }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    @else
        {{-- Single type: hidden input, no selector shown --}}
        <input type="hidden" class="bkf-room-type-hidden" value="{{ $activeTypes->first() ?? '' }}">
    @endif

    {{-- ====== CHECK-IN DATE + TIME ====== --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="{{ $prefix }}-checkin" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Tanggal Check-in
            </label>
            <input type="date"
                   id="{{ $prefix }}-checkin"
                   class="bkf-checkin w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                   min="{{ now()->format('Y-m-d') }}"
                   aria-required="true">
        </div>
        <div>
            <label for="{{ $prefix }}-checkin-time" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Waktu Check-in
            </label>
            <input type="time"
                   id="{{ $prefix }}-checkin-time"
                   class="bkf-checkin-time w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                   value="{{ \App\Services\SettingsService::get('booking_checkin_default_time', '14:00') }}">
        </div>
    </div>

    {{-- ====== DURATION + UNIT ====== --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="{{ $prefix }}-duration" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 bkf-duration-label">
                Durasi
            </label>
            {{-- JS will replace this with the correct input/select based on satuan --}}
            <div id="{{ $prefix }}-duration-wrap">
                <input type="number"
                       id="{{ $prefix }}-duration"
                       class="bkf-duration w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                       value="1" min="1" max="{{ $maxDays ?: 365 }}">
            </div>
        </div>
        <div>
            <label for="{{ $prefix }}-unit" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Tipe Sewa
            </label>
            <select id="{{ $prefix }}-unit"
                    class="bkf-unit w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2">
                {{-- Options populated by JS on load & room-type change --}}
            </select>
        </div>
    </div>

    {{-- ====== GUESTS ====== --}}
    <div>
        <label for="{{ $prefix }}-guests" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
            Jumlah Tamu
        </label>
        <input type="number"
               id="{{ $prefix }}-guests"
               class="bkf-guests w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
               value="1" min="1" max="20"
               aria-label="Jumlah tamu">
    </div>

    {{-- ====== PRICE SUMMARY ====== --}}
    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400 bkf-detail">—</p>
        <p class="text-xl font-bold bkf-total" style="color: {{ $primaryColor }}">Rp 0</p>
    </div>

    <button type="button"
            class="bkf-open w-full py-3.5 rounded-full text-white font-semibold hover:opacity-90 transition"
            style="background-color: {{ $primaryColor }}">
        Lanjut Pemesanan
    </button>
    <p class="bkf-error hidden text-sm text-red-600 dark:text-red-400 text-center" role="alert"></p>
</div>

@if ($maxDays)
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
        Maksimal durasi sewa: {{ $maxDays }} malam.
    </p>
@endif
