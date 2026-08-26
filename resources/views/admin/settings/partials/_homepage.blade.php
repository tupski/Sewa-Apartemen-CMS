{{-- Homepage Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Homepage Settings</h3>
<p class="text-sm text-gray-500 mb-6">Kosongkan untuk memakai teks bawaan halaman.</p>

<div class="space-y-6">
    {{-- Hero Section --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Hero Section</h4>
        <div class="space-y-4">
            <div>
                <label for="hero_title" class="block text-sm font-medium text-gray-700 mb-2">
                    Hero Title
                </label>
                <input type="text"
                       name="hero_title"
                       id="hero_title"
                       value="{{ old('hero_title', $settings['hero_title'] ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('hero_title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="hero_subtitle" class="block text-sm font-medium text-gray-700 mb-2">
                    Hero Subtitle
                </label>
                <textarea name="hero_subtitle"
                          id="hero_subtitle"
                          rows="2"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}</textarea>
                @error('hero_subtitle')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- CTA Section --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Call-to-Action Section</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="cta_title" class="block text-sm font-medium text-gray-700 mb-2">
                    CTA Title
                </label>
                <input type="text"
                       name="cta_title"
                       id="cta_title"
                       value="{{ old('cta_title', $settings['cta_title'] ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('cta_title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="cta_text" class="block text-sm font-medium text-gray-700 mb-2">
                    CTA Text
                </label>
                <textarea name="cta_text"
                          id="cta_text"
                          rows="2"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('cta_text', $settings['cta_text'] ?? '') }}</textarea>
                @error('cta_text')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="cta_button_label" class="block text-sm font-medium text-gray-700 mb-2">
                    CTA Button Label
                </label>
                <input type="text"
                       name="cta_button_label"
                       id="cta_button_label"
                       value="{{ old('cta_button_label', $settings['cta_button_label'] ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('cta_button_label')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="cta_button_url" class="block text-sm font-medium text-gray-700 mb-2">
                    CTA Button URL
                </label>
                <input type="url"
                       name="cta_button_url"
                       id="cta_button_url"
                       value="{{ old('cta_button_url', $settings['cta_button_url'] ?? '') }}"
                       placeholder="https://..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('cta_button_url')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Features Section --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Features Section</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="features_title" class="block text-sm font-medium text-gray-700 mb-2">
                    Features Title
                </label>
                <input type="text"
                       name="features_title"
                       id="features_title"
                       value="{{ old('features_title', $settings['features_title'] ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('features_title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="features_subtitle" class="block text-sm font-medium text-gray-700 mb-2">
                    Features Subtitle
                </label>
                <textarea name="features_subtitle"
                          id="features_subtitle"
                          rows="2"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('features_subtitle', $settings['features_subtitle'] ?? '') }}</textarea>
                @error('features_subtitle')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
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
