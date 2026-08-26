{{-- Theme / Appearance Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Appearance Settings</h3>
<p class="text-sm text-gray-500 mb-6">Customize the look and feel of your site.</p>

<div class="space-y-6">
    {{-- Colors --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Color Scheme</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6"
             x-data="{
                 primary: '{{ old('primary_color', $settings['primary_color'] ?? '#3b82f6') }}',
                 secondary: '{{ old('secondary_color', $settings['secondary_color'] ?? '#10b981') }}',
                 accent: '{{ old('accent_color', $settings['accent_color'] ?? '#8b5cf6') }}'
             }">
            <div>
                <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-2">
                    Primary Color
                </label>
                <div class="flex items-center gap-3">
                    <input type="color"
                           x-model="primary"
                           class="w-10 h-10 rounded border border-gray-300 cursor-pointer">
                    <input type="text"
                           name="primary_color"
                           id="primary_color"
                           x-model="primary"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                </div>
                @error('primary_color')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="secondary_color" class="block text-sm font-medium text-gray-700 mb-2">
                    Secondary Color
                </label>
                <div class="flex items-center gap-3">
                    <input type="color"
                           x-model="secondary"
                           class="w-10 h-10 rounded border border-gray-300 cursor-pointer">
                    <input type="text"
                           name="secondary_color"
                           id="secondary_color"
                           x-model="secondary"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                </div>
                @error('secondary_color')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-2">
                    Accent Color
                </label>
                <div class="flex items-center gap-3">
                    <input type="color"
                           x-model="accent"
                           class="w-10 h-10 rounded border border-gray-300 cursor-pointer">
                    <input type="text"
                           name="accent_color"
                           id="accent_color"
                           x-model="accent"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                </div>
                @error('accent_color')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Layout Options --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Layout Options</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="header_layout" class="block text-sm font-medium text-gray-700 mb-2">
                    Header Layout
                </label>
                <select name="header_layout"
                        id="header_layout"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="default" {{ ($settings['header_layout'] ?? 'default') == 'default' ? 'selected' : '' }}>Default</option>
                    <option value="centered" {{ ($settings['header_layout'] ?? '') == 'centered' ? 'selected' : '' }}>Centered</option>
                    <option value="minimal" {{ ($settings['header_layout'] ?? '') == 'minimal' ? 'selected' : '' }}>Minimal</option>
                </select>
                @error('header_layout')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="footer_layout" class="block text-sm font-medium text-gray-700 mb-2">
                    Footer Layout
                </label>
                <select name="footer_layout"
                        id="footer_layout"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="default" {{ ($settings['footer_layout'] ?? 'default') == 'default' ? 'selected' : '' }}>Default</option>
                    <option value="columns" {{ ($settings['footer_layout'] ?? '') == 'columns' ? 'selected' : '' }}>Columns</option>
                    <option value="minimal" {{ ($settings['footer_layout'] ?? '') == 'minimal' ? 'selected' : '' }}>Minimal</option>
                </select>
                @error('footer_layout')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ darkMode: {{ old('enable_dark_mode', $settings['enable_dark_mode'] ?? false) ? 'true' : 'false' }} }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Dark Mode</label>
                <div class="flex items-center gap-3 mt-2">
                    <button type="button"
                            @click="darkMode = !darkMode"
                            :class="darkMode ? 'bg-blue-600' : 'bg-gray-300'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            role="switch"
                            :aria-checked="darkMode">
                        <span :class="darkMode ? 'translate-x-6' : 'translate-x-1'"
                              class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                    </button>
                    <input type="hidden" name="enable_dark_mode" :value="darkMode ? '1' : '0'">
                    <span class="text-sm text-gray-600" x-text="darkMode ? 'Enabled' : 'Disabled'"></span>
                </div>
                @error('enable_dark_mode')
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
