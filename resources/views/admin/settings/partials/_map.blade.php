<div class="space-y-4">
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-2">{{ __('settings.map_style_heading') }}</h4>
        <p class="text-sm text-gray-500 mb-4">
            {{ __('settings.map_style_hint') }}
        </p>

        <div class="space-y-4">
            <div>
                <label for="map_theme_mode" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('settings.map_theme_mode') }}
                </label>
                <select name="map_theme_mode" id="map_theme_mode"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    @foreach (['follow' => __('settings.map_theme_follow'), 'light' => __('settings.map_theme_light'), 'dark' => __('settings.map_theme_dark')] as $mode => $label)
                        <option value="{{ $mode }}" @selected(old('map_theme_mode', $settings['map_theme_mode']) === $mode)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">{{ __('settings.map_theme_mode_hint') }}</p>
                @error('map_theme_mode')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            @php
                $styles = \App\Services\MapSettingsService::STYLES;
                $lightStyles = array_filter($styles, fn ($s) => ! $s['dark']);
                $darkStyles = array_filter($styles, fn ($s) => $s['dark']);
            @endphp

            <div>
                <label for="map_style_light" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('settings.map_style_light') }}
                </label>
                <select name="map_style_light" id="map_style_light"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    @foreach ($lightStyles as $key => $style)
                        <option value="{{ $key }}" @selected(old('map_style_light', $settings['map_style_light']) === $key)>{{ __("map_style.{$key}") }}</option>
                    @endforeach
                </select>
                @error('map_style_light')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="map_style_dark" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('settings.map_style_dark') }}
                </label>
                <select name="map_style_dark" id="map_style_dark"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    @foreach ($darkStyles as $key => $style)
                        <option value="{{ $key }}" @selected(old('map_style_dark', $settings['map_style_dark']) === $key)>{{ __("map_style.{$key}") }}</option>
                    @endforeach
                </select>
                @error('map_style_dark')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Custom colors: NOT technically supported on raster tiles — say so
                 instead of faking controls that would do nothing (AGENTS.md §19.6). --}}
            <div class="rounded-md bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-600">
                <strong>{{ __('settings.map_custom_colors_title') }}</strong>
                <p class="mt-1">{{ __('settings.map_custom_colors_unsupported') }}</p>
            </div>
        </div>
    </div>
</div>
