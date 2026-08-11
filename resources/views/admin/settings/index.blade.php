@extends('layouts.admin')

@section('page-title', 'CMS Settings')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Tabs Navigation -->
        <div x-data="{ activeTab: 'general' }" class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'general'"
                        :class="activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    General
                </button>
                <button @click="activeTab = 'footer'"
                        :class="activeTab === 'footer' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Footer
                </button>
                <button @click="activeTab = 'theme'"
                        :class="activeTab === 'theme' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Theme
                </button>
                <button @click="activeTab = 'seo'"
                        :class="activeTab === 'seo' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    SEO
                </button>
            </nav>

            <!-- Settings Form -->
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                @csrf

                <!-- General Tab -->
                <div x-show="activeTab === 'general'" class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">General Settings</h3>

                    <div class="space-y-6">
                        <!-- Site Name -->
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Site Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="site_name"
                                   id="site_name"
                                   value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('site_name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Site Description -->
                        <div>
                            <label for="site_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Site Description
                            </label>
                            <textarea name="site_description"
                                      id="site_description"
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
                            @error('site_description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Site Logo -->
                            <div>
                                <label for="site_logo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Site Logo
                                </label>
                                <input type="file"
                                       name="site_logo"
                                       id="site_logo"
                                       accept="image/*"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @if(!empty($settings['site_logo']))
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $settings['site_logo']) }}"
                                             alt="Site Logo"
                                             class="h-16 object-contain">
                                    </div>
                                @endif
                                @error('site_logo')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Site Favicon -->
                            <div>
                                <label for="site_favicon" class="block text-sm font-medium text-gray-700 mb-2">
                                    Site Favicon
                                </label>
                                <input type="file"
                                       name="site_favicon"
                                       id="site_favicon"
                                       accept="image/*"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @if(!empty($settings['site_favicon']))
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $settings['site_favicon']) }}"
                                             alt="Site Favicon"
                                             class="h-8 object-contain">
                                    </div>
                                @endif
                                @error('site_favicon')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Contact Email
                                </label>
                                <input type="email"
                                       name="contact_email"
                                       id="contact_email"
                                       value="{{ old('contact_email', $settings['contact_email'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('contact_email')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Contact Phone
                                </label>
                                <input type="tel"
                                       name="contact_phone"
                                       id="contact_phone"
                                       value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('contact_phone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Contact Address -->
                        <div>
                            <label for="contact_address" class="block text-sm font-medium text-gray-700 mb-2">
                                Contact Address
                            </label>
                            <textarea name="contact_address"
                                      id="contact_address"
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('contact_address', $settings['contact_address'] ?? '') }}</textarea>
                            @error('contact_address')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- System Settings -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Timezone
                                </label>
                                <select name="timezone"
                                        id="timezone"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="UTC" {{ ($settings['timezone'] ?? 'UTC') == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    <option value="Asia/Jakarta" {{ ($settings['timezone'] ?? '') == 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta</option>
                                    <option value="America/New_York" {{ ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : '' }}>America/New York</option>
                                    <option value="Europe/London" {{ ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                </select>
                                @error('timezone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="locale" class="block text-sm font-medium text-gray-700 mb-2">
                                    Locale
                                </label>
                                <select name="locale"
                                        id="locale"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="en" {{ ($settings['locale'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                                    <option value="id" {{ ($settings['locale'] ?? '') == 'id' ? 'selected' : '' }}>Indonesian</option>
                                </select>
                                @error('locale')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                    Currency
                                </label>
                                <select name="currency"
                                        id="currency"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="USD" {{ ($settings['currency'] ?? 'USD') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="IDR" {{ ($settings['currency'] ?? '') == 'IDR' ? 'selected' : '' }}>IDR (Rp)</option>
                                    <option value="EUR" {{ ($settings['currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                </select>
                                @error('currency')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Tab -->
                <div x-show="activeTab === 'footer'" class="p-6" style="display: none;">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Footer Settings</h3>

                    <div class="space-y-6">
                        <!-- Footer About Text -->
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

                        <!-- Footer Copyright -->
                        <div>
                            <label for="footer_copyright" class="block text-sm font-medium text-gray-700 mb-2">
                                Footer Copyright
                            </label>
                            <input type="text"
                                   name="footer_copyright"
                                   id="footer_copyright"
                                   value="{{ old('footer_copyright', $settings['footer_copyright'] ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            @error('footer_copyright')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Social Media Links -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-4">Social Media URLs</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="social_facebook" class="block text-sm font-medium text-gray-700 mb-2">
                                        Facebook
                                    </label>
                                    <input type="url"
                                           name="social_facebook"
                                           id="social_facebook"
                                           value="{{ old('social_facebook', $settings['social_facebook'] ?? '') }}"
                                           placeholder="https://facebook.com/yourpage"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="social_twitter" class="block text-sm font-medium text-gray-700 mb-2">
                                        Twitter
                                    </label>
                                    <input type="url"
                                           name="social_twitter"
                                           id="social_twitter"
                                           value="{{ old('social_twitter', $settings['social_twitter'] ?? '') }}"
                                           placeholder="https://twitter.com/yourhandle"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="social_instagram" class="block text-sm font-medium text-gray-700 mb-2">
                                        Instagram
                                    </label>
                                    <input type="url"
                                           name="social_instagram"
                                           id="social_instagram"
                                           value="{{ old('social_instagram', $settings['social_instagram'] ?? '') }}"
                                           placeholder="https://instagram.com/yourprofile"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="social_linkedin" class="block text-sm font-medium text-gray-700 mb-2">
                                        LinkedIn
                                    </label>
                                    <input type="url"
                                           name="social_linkedin"
                                           id="social_linkedin"
                                           value="{{ old('social_linkedin', $settings['social_linkedin'] ?? '') }}"
                                           placeholder="https://linkedin.com/company/yourcompany"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="social_youtube" class="block text-sm font-medium text-gray-700 mb-2">
                                        YouTube
                                    </label>
                                    <input type="url"
                                           name="social_youtube"
                                           id="social_youtube"
                                           value="{{ old('social_youtube', $settings['social_youtube'] ?? '') }}"
                                           placeholder="https://youtube.com/channel/yourchannel"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Theme Tab -->
                <div x-show="activeTab === 'theme'" class="p-6" style="display: none;">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Theme Settings</h3>

                    <div class="space-y-6">
                        <!-- Color Settings -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="theme_primary_color" class="block text-sm font-medium text-gray-700 mb-2">
                                    Primary Color
                                </label>
                                <input type="color"
                                       name="theme_primary_color"
                                       id="theme_primary_color"
                                       value="{{ old('theme_primary_color', $settings['theme_primary_color'] ?? '#3B82F6') }}"
                                       class="w-full h-12 px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('theme_primary_color')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="theme_secondary_color" class="block text-sm font-medium text-gray-700 mb-2">
                                    Secondary Color
                                </label>
                                <input type="color"
                                       name="theme_secondary_color"
                                       id="theme_secondary_color"
                                       value="{{ old('theme_secondary_color', $settings['theme_secondary_color'] ?? '#6B7280') }}"
                                       class="w-full h-12 px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('theme_secondary_color')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="theme_accent_color" class="block text-sm font-medium text-gray-700 mb-2">
                                    Accent Color
                                </label>
                                <input type="color"
                                       name="theme_accent_color"
                                       id="theme_accent_color"
                                       value="{{ old('theme_accent_color', $settings['theme_accent_color'] ?? '#10B981') }}"
                                       class="w-full h-12 px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('theme_accent_color')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Layout Settings -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="theme_header_layout" class="block text-sm font-medium text-gray-700 mb-2">
                                    Header Layout
                                </label>
                                <select name="theme_header_layout"
                                        id="theme_header_layout"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="default" {{ ($settings['theme_header_layout'] ?? 'default') == 'default' ? 'selected' : '' }}>Default</option>
                                    <option value="centered" {{ ($settings['theme_header_layout'] ?? '') == 'centered' ? 'selected' : '' }}>Centered</option>
                                    <option value="minimal" {{ ($settings['theme_header_layout'] ?? '') == 'minimal' ? 'selected' : '' }}>Minimal</option>
                                </select>
                                @error('theme_header_layout')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="theme_footer_layout" class="block text-sm font-medium text-gray-700 mb-2">
                                    Footer Layout
                                </label>
                                <select name="theme_footer_layout"
                                        id="theme_footer_layout"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="default" {{ ($settings['theme_footer_layout'] ?? 'default') == 'default' ? 'selected' : '' }}>Default</option>
                                    <option value="columns" {{ ($settings['theme_footer_layout'] ?? '') == 'columns' ? 'selected' : '' }}>Columns</option>
                                    <option value="minimal" {{ ($settings['theme_footer_layout'] ?? '') == 'minimal' ? 'selected' : '' }}>Minimal</option>
                                </select>
                                @error('theme_footer_layout')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Dark Mode -->
                        <div class="flex items-center">
                            <input type="checkbox"
                                   name="theme_dark_mode"
                                   id="theme_dark_mode"
                                   value="1"
                                   {{ old('theme_dark_mode', $settings['theme_dark_mode'] ?? false) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="theme_dark_mode" class="ml-2 text-sm font-medium text-gray-700">
                                Enable Dark Mode
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SEO Tab -->
                <div x-show="activeTab === 'seo'" class="p-6" style="display: none;">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">SEO Settings</h3>

                    <div class="space-y-6">
                        <!-- Meta Description -->
                        <div>
                            <label for="seo_meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Meta Description
                            </label>
                            <textarea name="seo_meta_description"
                                      id="seo_meta_description"
                                      rows="3"
                                      placeholder="A brief description of your website for search engines"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('seo_meta_description', $settings['seo_meta_description'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                            @error('seo_meta_description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Meta Keywords -->
                        <div>
                            <label for="seo_meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                                Meta Keywords
                            </label>
                            <input type="text"
                                   name="seo_meta_keywords"
                                   id="seo_meta_keywords"
                                   value="{{ old('seo_meta_keywords', $settings['seo_meta_keywords'] ?? '') }}"
                                   placeholder="keyword1, keyword2, keyword3"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Separate keywords with commas</p>
                            @error('seo_meta_keywords')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Google Analytics -->
                        <div>
                            <label for="seo_google_analytics" class="block text-sm font-medium text-gray-700 mb-2">
                                Google Analytics Code
                            </label>
                            <textarea name="seo_google_analytics"
                                      id="seo_google_analytics"
                                      rows="6"
                                      placeholder="<!-- Google Analytics tracking code -->"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">{{ old('seo_google_analytics', $settings['seo_google_analytics'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Paste your complete Google Analytics tracking code</p>
                            @error('seo_google_analytics')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Facebook Pixel -->
                        <div>
                            <label for="seo_facebook_pixel" class="block text-sm font-medium text-gray-700 mb-2">
                                Facebook Pixel Code
                            </label>
                            <textarea name="seo_facebook_pixel"
                                      id="seo_facebook_pixel"
                                      rows="6"
                                      placeholder="<!-- Facebook Pixel Code -->"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">{{ old('seo_facebook_pixel', $settings['seo_facebook_pixel'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Paste your complete Facebook Pixel code</p>
                            @error('seo_facebook_pixel')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
