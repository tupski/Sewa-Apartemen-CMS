{{-- Footer Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Footer Settings</h3>
<p class="text-sm text-gray-500 mb-6">Configure footer content and social media links.</p>

<div class="space-y-6">
    {{-- Footer About Text --}}
    <div>
        <label for="footer_about" class="block text-sm font-medium text-gray-700 mb-2">
            Footer About Text
        </label>
        <textarea name="footer_about"
                  id="footer_about"
                  rows="4"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('footer_about', $settings['footer_about'] ?? '') }}</textarea>
        @error('footer_about')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Footer Copyright --}}
    <div>
        <label for="footer_copyright" class="block text-sm font-medium text-gray-700 mb-2">
            Footer Copyright
        </label>
        <input type="text"
               name="footer_copyright"
               id="footer_copyright"
               value="{{ old('footer_copyright', $settings['footer_copyright'] ?? '') }}"
               placeholder="&copy; 2026 Your Company. All rights reserved."
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        @error('footer_copyright')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Social Media Links --}}
    <div class="border-t border-gray-200 pt-6">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Social Media URLs</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="social_facebook" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-brands fa-facebook text-blue-600 mr-1"></i> Facebook
                </label>
                <input type="url"
                       name="social_facebook"
                       id="social_facebook"
                       value="{{ old('social_facebook', $settings['social_facebook'] ?? '') }}"
                       placeholder="https://facebook.com/yourpage"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('social_facebook')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="social_twitter" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-brands fa-twitter text-blue-400 mr-1"></i> Twitter
                </label>
                <input type="url"
                       name="social_twitter"
                       id="social_twitter"
                       value="{{ old('social_twitter', $settings['social_twitter'] ?? '') }}"
                       placeholder="https://twitter.com/yourhandle"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('social_twitter')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="social_instagram" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-brands fa-instagram text-pink-600 mr-1"></i> Instagram
                </label>
                <input type="url"
                       name="social_instagram"
                       id="social_instagram"
                       value="{{ old('social_instagram', $settings['social_instagram'] ?? '') }}"
                       placeholder="https://instagram.com/yourprofile"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('social_instagram')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="social_linkedin" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-brands fa-linkedin text-blue-700 mr-1"></i> LinkedIn
                </label>
                <input type="url"
                       name="social_linkedin"
                       id="social_linkedin"
                       value="{{ old('social_linkedin', $settings['social_linkedin'] ?? '') }}"
                       placeholder="https://linkedin.com/company/yourpage"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('social_linkedin')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="social_youtube" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-brands fa-youtube text-red-600 mr-1"></i> YouTube
                </label>
                <input type="url"
                       name="social_youtube"
                       id="social_youtube"
                       value="{{ old('social_youtube', $settings['social_youtube'] ?? '') }}"
                       placeholder="https://youtube.com/@yourchannel"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('social_youtube')
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
