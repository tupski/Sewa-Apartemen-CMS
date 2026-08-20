@php
    $maxDays = $property->maxBookingDays();
    $hasJam = $property->hasBookingType('transit');
    $hasMalam = $property->hasBookingType('daily');
@endphp
<div data-bkf class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="{{ $prefix }}-checkin" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal Check-in</label>
            <input type="date" id="{{ $prefix }}-checkin" class="bkf-checkin w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2" min="{{ now()->format('Y-m-d') }}">
        </div>
        <div>
            <label for="{{ $prefix }}-checkin-time" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Waktu Check-in</label>
            <input type="time" id="{{ $prefix }}-checkin-time" class="bkf-checkin-time w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2" value="14:00">
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="{{ $prefix }}-duration" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Durasi</label>
            <input type="number" id="{{ $prefix }}-duration" class="bkf-duration w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2" value="1" min="1" max="{{ $maxDays ?: 365 }}">
        </div>
        <div>
            <label for="{{ $prefix }}-unit" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Satuan</label>
            <select id="{{ $prefix }}-unit" class="bkf-unit w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2">
                @if ($hasJam)
                    <option value="jam">Jam</option>
                @endif
                @if ($hasMalam)
                    <option value="malam" {{ $hasJam ? '' : 'selected' }}>Malam</option>
                @endif
            </select>
        </div>
    </div>
    <div>
        <label for="{{ $prefix }}-guests" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Jumlah Tamu</label>
        <input type="number" id="{{ $prefix }}-guests" class="bkf-guests w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2" value="1" min="1" max="20">
    </div>

    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400 bkf-detail">—</p>
        <p class="text-xl font-bold bkf-total" style="color: {{ $primaryColor }}">Rp 0</p>
    </div>

    <button type="button" class="bkf-open w-full py-3.5 rounded-full text-white font-semibold hover:opacity-90 transition" style="background-color: {{ $primaryColor }}">
        Lanjut Pemesanan
    </button>
    <p class="bkf-error hidden text-sm text-red-600 dark:text-red-400 text-center"></p>
</div>

@if ($maxDays)
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Maksimal durasi sewa: {{ $maxDays }} malam.</p>
@endif
