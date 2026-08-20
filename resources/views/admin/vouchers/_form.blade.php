{{-- Shared form fields for voucher create/edit --}}

<div class="space-y-6">
    {{-- Code + Name row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                Kode Voucher <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="code"
                   id="code"
                   value="{{ old('code', $voucher->code ?? '') }}"
                   maxlength="50"
                   placeholder="cth. PROMO2026"
                   style="text-transform: uppercase"
                   oninput="this.value = this.value.toUpperCase()"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm font-mono tracking-wider"
                   required>
            <p class="text-xs text-gray-400 mt-1">Otomatis diubah ke huruf kapital. Harus unik.</p>
            @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                Nama / Keterangan Internal <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="name"
                   id="name"
                   value="{{ old('name', $voucher->name ?? '') }}"
                   maxlength="255"
                   placeholder="cth. Promo Agustus 2026"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                   required>
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Discount type --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Tipe Diskon <span class="text-red-500">*</span>
        </label>
        <div class="flex gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio"
                       name="discount_type"
                       value="percent"
                       class="h-4 w-4 text-blue-600"
                       x-model="discountType"
                       {{ old('discount_type', $voucher->discount_type ?? 'percent') === 'percent' ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Persen (%)</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio"
                       name="discount_type"
                       value="fixed"
                       class="h-4 w-4 text-blue-600"
                       x-model="discountType"
                       {{ old('discount_type', $voucher->discount_type ?? '') === 'fixed' ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Nominal (Rp)</span>
            </label>
        </div>
        @error('discount_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Discount value + max cap row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-1">
                Nilai Diskon <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" x-text="discountType === 'percent' ? '%' : 'Rp'">%</span>
                <input type="number"
                       name="discount_value"
                       id="discount_value"
                       value="{{ old('discount_value', $voucher->discount_value ?? '') }}"
                       min="0"
                       step="0.01"
                       placeholder="0"
                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm text-right"
                       required>
            </div>
            <p class="text-xs text-gray-400 mt-1" x-show="discountType === 'percent'">Masukkan 10 untuk diskon 10%.</p>
            <p class="text-xs text-gray-400 mt-1" x-show="discountType !== 'percent'">Masukkan nominal rupiah yang akan dipotong.</p>
            @error('discount_value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div x-show="discountType === 'percent'">
            <label for="max_discount_amount" class="block text-sm font-medium text-gray-700 mb-1">
                Maksimal Diskon (Rp)
            </label>
            <input type="number"
                   name="max_discount_amount"
                   id="max_discount_amount"
                   value="{{ old('max_discount_amount', $voucher->max_discount_amount ?? '') }}"
                   min="0"
                   step="1000"
                   placeholder="Kosongkan = tanpa batas"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm text-right">
            <p class="text-xs text-gray-400 mt-1">Batas atas diskon persen. Kosongkan untuk tanpa batas.</p>
            @error('max_discount_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Min booking + usage limit --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="min_booking_amount" class="block text-sm font-medium text-gray-700 mb-1">
                Minimum Total Booking (Rp)
            </label>
            <input type="number"
                   name="min_booking_amount"
                   id="min_booking_amount"
                   value="{{ old('min_booking_amount', $voucher->min_booking_amount ?? '') }}"
                   min="0"
                   step="1000"
                   placeholder="Kosongkan = tanpa minimum"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm text-right">
            <p class="text-xs text-gray-400 mt-1">Voucher hanya bisa dipakai jika total booking ≥ nilai ini.</p>
            @error('min_booking_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-1">
                Batas Penggunaan
            </label>
            <input type="number"
                   name="usage_limit"
                   id="usage_limit"
                   value="{{ old('usage_limit', $voucher->usage_limit ?? '') }}"
                   min="1"
                   placeholder="Kosongkan = tidak terbatas"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm text-right">
            <p class="text-xs text-gray-400 mt-1">Jumlah maksimal penggunaan. Kosongkan untuk tidak terbatas.</p>
            @error('usage_limit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Valid dates --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-1">
                Berlaku Mulai
            </label>
            <input type="date"
                   name="valid_from"
                   id="valid_from"
                   value="{{ old('valid_from', isset($voucher->valid_from) ? $voucher->valid_from->format('Y-m-d') : '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            <p class="text-xs text-gray-400 mt-1">Kosongkan jika langsung aktif sejak dibuat.</p>
            @error('valid_from') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">
                Berlaku Hingga
            </label>
            <input type="date"
                   name="valid_until"
                   id="valid_until"
                   value="{{ old('valid_until', isset($voucher->valid_until) ? $voucher->valid_until->format('Y-m-d') : '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ada batas kadaluarsa.</p>
            @error('valid_until') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Is active toggle --}}
    <div class="flex items-center gap-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox"
                   name="is_active"
                   id="is_active"
                   value="1"
                   class="sr-only peer"
                   {{ old('is_active', $voucher->is_active ?? true) ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
        </label>
        <div>
            <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Status Aktif</label>
            <p class="text-xs text-gray-400">Voucher hanya bisa dipakai jika aktif.</p>
        </div>
    </div>
</div>
