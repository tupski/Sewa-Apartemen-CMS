{{-- Pricing & Booking Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Pricing & Booking Settings</h3>
<p class="text-sm text-gray-500 mb-6">
    Konfigurasi hari weekend secara global. Properti individual bisa meng-override pengaturan ini
    di tab Harga pada halaman edit properti.
</p>

<div class="space-y-6">
    {{-- Weekend Days Mode --}}
    <div>
        <label for="weekend_days_mode" class="block text-sm font-medium text-gray-700 mb-1">
            Konfigurasi Hari Weekend Default
        </label>
        <select name="weekend_days_mode"
                id="weekend_days_mode"
                x-data
                @change="$dispatch('weekend-mode-changed', { value: $event.target.value })"
                class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            @foreach(['sat_sun' => 'Sabtu–Minggu', 'fri_sun' => 'Jumat–Minggu', 'custom' => 'Custom (pakai pengaturan di bawah)'] as $optVal => $optLabel)
                <option value="{{ $optVal }}" {{ old('weekend_days_mode', $settings['weekend_days_mode'] ?? 'sat_sun') === $optVal ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">
            Menentukan hari mana yang dianggap "weekend" saat menghitung harga. Pilih <strong>Custom</strong> untuk mengatur sendiri.
        </p>
        @error('weekend_days_mode')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Custom weekend range (shown when mode = custom) --}}
    <div x-data="{ show: '{{ old('weekend_days_mode', $settings['weekend_days_mode'] ?? 'sat_sun') }}' === 'custom' }"
         @weekend-mode-changed.window="show = $event.detail.value === 'custom'"
         x-show="show"
         x-cloak
         class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-blue-50 border border-blue-100 rounded-lg">
        @php
            $days = ['0' => 'Minggu', '1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu'];
        @endphp
        <div>
            <label for="weekend_start_day" class="block text-sm font-medium text-gray-700 mb-2">
                Weekend Start Day
            </label>
            <select name="weekend_start_day"
                    id="weekend_start_day"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @foreach($days as $val => $label)
                    <option value="{{ $val }}" {{ old('weekend_start_day', $settings['weekend_start_day'] ?? '5') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="weekend_end_day" class="block text-sm font-medium text-gray-700 mb-2">
                Weekend End Day
            </label>
            <select name="weekend_end_day"
                    id="weekend_end_day"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @foreach($days as $val => $label)
                    <option value="{{ $val }}" {{ old('weekend_end_day', $settings['weekend_end_day'] ?? '0') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Booking Display Mode --}}
    <div>
        <label for="booking_display_mode" class="block text-sm font-medium text-gray-700 mb-1">
            Mode Tampilan Booking
        </label>
        <select name="booking_display_mode"
                id="booking_display_mode"
                class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            <option value="form_only" {{ ($settings['booking_display_mode'] ?? 'both') === 'form_only' ? 'selected' : '' }}>Form Booking Saja</option>
            <option value="pricing_only" {{ ($settings['booking_display_mode'] ?? 'both') === 'pricing_only' ? 'selected' : '' }}>Tabel Harga + Tombol WhatsApp Saja</option>
            <option value="both" {{ ($settings['booking_display_mode'] ?? 'both') === 'both' ? 'selected' : '' }}>Keduanya (Form + Tabel Harga)</option>
        </select>
        <p class="text-xs text-gray-400 mt-1">Mengatur apa yang ditampilkan di sidebar kanan halaman detail properti.</p>
        @error('booking_display_mode')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- WhatsApp Number for Pricing Table --}}
    <div>
        <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">
            Nomor WhatsApp (Tabel Harga)
        </label>
        <input type="text"
               name="whatsapp_number"
               id="whatsapp_number"
               value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}"
               placeholder="6281234567890"
               class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
        <p class="text-xs text-gray-400 mt-1">Sertakan kode negara, contoh: 6281234567890. Dipakai untuk tombol WhatsApp di tabel harga.</p>
        @error('whatsapp_number')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Min Transit Hours --}}
    <div>
        <label for="booking_min_transit_hours" class="block text-sm font-medium text-gray-700 mb-1">
            Minimal Jam Transit
        </label>
        <div class="flex items-center gap-3">
            <input type="number"
                   name="booking_min_transit_hours"
                   id="booking_min_transit_hours"
                   value="{{ old('booking_min_transit_hours', $settings['booking_min_transit_hours'] ?? '3') }}"
                   min="1" max="24" step="1"
                   class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            <span class="text-sm text-gray-500">jam (slot transit terkecil yang bisa dipesan)</span>
        </div>
        <p class="text-xs text-gray-400 mt-1">Default: 3 jam. Hanya berlaku sebagai referensi — slot aktual ditentukan oleh harga yang diisi per properti.</p>
        @error('booking_min_transit_hours')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Default Check-in Time --}}
        <div>
            <label for="booking_checkin_default_time" class="block text-sm font-medium text-gray-700 mb-1">
                Jam Check-in Default
            </label>
            <input type="time"
                   name="booking_checkin_default_time"
                   id="booking_checkin_default_time"
                   value="{{ old('booking_checkin_default_time', $settings['booking_checkin_default_time'] ?? '14:00') }}"
                   class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            @error('booking_checkin_default_time')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Default Check-out Time --}}
        <div>
            <label for="booking_checkout_default_time" class="block text-sm font-medium text-gray-700 mb-1">
                Jam Check-out Default
            </label>
            <input type="time"
                   name="booking_checkout_default_time"
                   id="booking_checkout_default_time"
                   value="{{ old('booking_checkout_default_time', $settings['booking_checkout_default_time'] ?? '12:00') }}"
                   class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            @error('booking_checkout_default_time')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

{{-- Submit Button --}}
<div class="pt-6 border-t border-gray-200 mt-6 flex justify-end">
    <button type="submit"
            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Save Settings
    </button>
</div>
