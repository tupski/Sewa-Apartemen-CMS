<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kode Bahasa <span class="text-red-500">*</span></label>
            <input type="text" name="code" value="{{ old('code', $language->code ?? '') }}"
                   placeholder="id, en, zh, ..." maxlength="10"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" required>
            <p class="text-xs text-gray-400 mt-1">ISO 639-1: id, en, zh, ja, ko, dll.</p>
            @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kode Bendera (ISO 3166-1) <span class="text-gray-400 text-xs">(opsional)</span></label>
            <input type="text" name="flag_code" value="{{ old('flag_code', $language->flag_code ?? '') }}"
                   placeholder="ID, GB, US, ..." maxlength="10"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-400 mt-1">Kode negara 2 huruf untuk ikon bendera.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama (Bahasa Indonesia) <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $language->name ?? '') }}"
                   placeholder="Indonesia, Inggris, ..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" required>
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lokal <span class="text-red-500">*</span></label>
            <input type="text" name="native_name" value="{{ old('native_name', $language->native_name ?? '') }}"
                   placeholder="Bahasa Indonesia, English, ..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" required>
            @error('native_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Emoji Bendera <span class="text-gray-400 text-xs">(opsional)</span></label>
            <input type="text" name="flag_emoji" value="{{ old('flag_emoji', $language->flag_emoji ?? '') }}"
                   placeholder="🇮🇩" maxlength="10"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Urutan Tampil</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $language->sort_order ?? 0) }}" min="0"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="flex items-center gap-8">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $language->is_active ?? true) ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-blue-600">
            <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_default" value="0">
            <input type="checkbox" name="is_default" value="1" {{ old('is_default', $language->is_default ?? false) ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-blue-600">
            <span class="text-sm text-gray-700 dark:text-gray-300">Bahasa Default</span>
        </label>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('admin.languages.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">Batal</a>
        <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Simpan</button>
    </div>
</div>
