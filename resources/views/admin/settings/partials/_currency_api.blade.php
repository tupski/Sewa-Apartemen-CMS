{{-- Currency API Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Currency API Settings</h3>
<p class="text-sm text-gray-500 mb-6">Configure automatic currency rate fetching.</p>

<div class="space-y-6">
    {{-- Currency API URL --}}
    <div>
        <label for="currency_api_url" class="block text-sm font-medium text-gray-700 mb-2">
            Currency API URL
        </label>
        <input type="url"
               name="currency_api_url"
               id="currency_api_url"
               value="{{ old('currency_api_url', $settings['currency_api_url'] ?? '') }}"
               placeholder="https://api.exchangerate-api.com/v4/latest/USD"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        @error('currency_api_url')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Currency API Key --}}
    <div>
        <label for="currency_api_key" class="block text-sm font-medium text-gray-700 mb-2">
            Currency API Key
        </label>
        <input type="text"
               name="currency_api_key"
               id="currency_api_key"
               value="{{ old('currency_api_key', $settings['currency_api_key'] ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
        @error('currency_api_key')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Target List --}}
    <div>
        <label for="currency_target_list" class="block text-sm font-medium text-gray-700 mb-2">
            Target Currency List
        </label>
        <input type="text"
               name="currency_target_list"
               id="currency_target_list"
               value="{{ old('currency_target_list', $settings['currency_target_list'] ?? 'USD,SGD,MYR,EUR,AUD,GBP,JPY') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">Kode ISO 4217. Contoh: USD,SGD,MYR,EUR</p>
        @error('currency_target_list')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Submit Button --}}
<div class="pt-6 border-t border-gray-200 mt-6 flex justify-end">
    <button type="submit"
            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Save Settings
    </button>
</div>
