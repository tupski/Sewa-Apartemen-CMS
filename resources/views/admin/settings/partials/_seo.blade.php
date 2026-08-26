{{-- SEO Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">SEO Settings</h3>
<p class="text-sm text-gray-500 mb-6">Configure search engine optimization and analytics.</p>

<div class="space-y-6">
    {{-- Meta Tags --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Meta Tags</h4>
        <div class="space-y-4">
            <div>
                <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Meta Description
                </label>
                <textarea name="meta_description"
                          id="meta_description"
                          rows="3"
                          maxlength="160"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="A brief description of your site for search engines (max 160 characters)">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
                @error('meta_description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                    Meta Keywords
                </label>
                <input type="text"
                       name="meta_keywords"
                       id="meta_keywords"
                       value="{{ old('meta_keywords', $settings['meta_keywords'] ?? '') }}"
                       placeholder="apartment, rental, sewa apartemen"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Separated by commas</p>
                @error('meta_keywords')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Analytics --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Analytics & Tracking</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="google_analytics" class="block text-sm font-medium text-gray-700 mb-2">
                    Google Analytics (Legacy)
                </label>
                <input type="text"
                       name="google_analytics"
                       id="google_analytics"
                       value="{{ old('google_analytics', $settings['google_analytics'] ?? '') }}"
                       placeholder="UA-XXXXX-Y"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('google_analytics')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="google_analytics_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Google Analytics ID (G-)
                </label>
                <input type="text"
                       name="google_analytics_id"
                       id="google_analytics_id"
                       value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}"
                       placeholder="G-XXXXXXXXXX"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('google_analytics_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="google_tag_manager_id" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('settings.gtm_container_id') }}
                </label>
                <input type="text"
                       name="google_tag_manager_id"
                       id="google_tag_manager_id"
                       value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id'] ?? '') }}"
                       placeholder="GTM-XXXXXXX"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                <p class="text-xs text-gray-500 mt-1">{{ __('settings.gtm_help') }}</p>
                @error('google_tag_manager_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="facebook_pixel" class="block text-sm font-medium text-gray-700 mb-2">
                    Facebook Pixel ID
                </label>
                <input type="text"
                       name="facebook_pixel"
                       id="facebook_pixel"
                       value="{{ old('facebook_pixel', $settings['facebook_pixel'] ?? '') }}"
                       placeholder="1234567890"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('facebook_pixel')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="meta_pixel_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Meta Pixel ID
                </label>
                <input type="text"
                       name="meta_pixel_id"
                       id="meta_pixel_id"
                       value="{{ old('meta_pixel_id', $settings['meta_pixel_id'] ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('meta_pixel_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="microsoft_clarity_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Microsoft Clarity ID
                </label>
                <input type="text"
                       name="microsoft_clarity_id"
                       id="microsoft_clarity_id"
                       value="{{ old('microsoft_clarity_id', $settings['microsoft_clarity_id'] ?? '') }}"
                       placeholder="abc123xyz"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('microsoft_clarity_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="search_console_token" class="block text-sm font-medium text-gray-700 mb-2">
                    Search Console Token
                </label>
                <input type="text"
                       name="search_console_token"
                       id="search_console_token"
                       value="{{ old('search_console_token', $settings['search_console_token'] ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('search_console_token')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Google Maps --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Google Maps</h4>
        <p class="text-sm text-gray-500 mb-4">
            Opsional. Biarkan kosong untuk memakai embed peta tanpa kunci + daftar tempat manual per properti.
            Isi kunci (dengan Places API diaktifkan) untuk otomatis mengambil tempat sekitar properti.
        </p>
        <div>
            <label for="google_maps_api_key" class="block text-sm font-medium text-gray-700 mb-2">
                Google Maps API Key
            </label>
            <input type="text"
                   name="google_maps_api_key"
                   id="google_maps_api_key"
                   value="{{ old('google_maps_api_key', $settings['google_maps_api_key'] ?? '') }}"
                   placeholder="AIza..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            @error('google_maps_api_key')
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
