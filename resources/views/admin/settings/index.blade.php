@extends('layouts.admin')

@section('page-title', 'CMS Settings')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Tabs Navigation -->
        <div x-data="{ activeTab: 'general' }" class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'general'"
                        :class="activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    General
                </button>
                <button @click="activeTab = 'homepage'"
                        :class="activeTab === 'homepage' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Homepage
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
                <button @click="activeTab = 'pricing'"
                        :class="activeTab === 'pricing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Pricing
                </button>
                <button @click="activeTab = 'booking'"
                        :class="activeTab === 'booking' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Booking
                </button>
                <button @click="activeTab = 'integrations'"
                        :class="activeTab === 'integrations' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Integrations
                </button>
            </nav>

            <!-- Settings Form -->
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                @csrf

                <div class="px-6 pt-4">
                    <p class="text-sm text-gray-500">
                        Kolom bertanda <span class="text-red-500 font-bold">*</span> wajib diisi, sisanya opsional.
                    </p>
                </div>

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
                                    Contact Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email"
                                       name="contact_email"
                                       id="contact_email"
                                       value="{{ old('contact_email', $settings['contact_email'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                @error('contact_email')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Contact Phone <span class="text-red-500">*</span>
                                </label>
                                <input type="tel"
                                       name="contact_phone"
                                       id="contact_phone"
                                       value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                @error('contact_phone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="whatsapp_default" class="block text-sm font-medium text-gray-700 mb-2">
                                    WhatsApp Number
                                </label>
                                <input type="tel"
                                       name="whatsapp_default"
                                       id="whatsapp_default"
                                       value="{{ old('whatsapp_default', $settings['whatsapp_default'] ?? '') }}"
                                       placeholder="6281234567890"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Format internasional tanpa + atau spasi. Dipakai untuk tombol chat WhatsApp.</p>
                                @error('whatsapp_default')
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

                <!-- Homepage Tab -->
                <div x-show="activeTab === 'homepage'" class="p-6" style="display: none;">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Homepage Settings</h3>
                    <p class="text-sm text-gray-500 mb-6">Kosongkan untuk memakai teks bawaan halaman.</p>

                    <div class="space-y-6">
                        <!-- Hero -->
                        <div class="border-b border-gray-200 pb-6">
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
                                              rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}</textarea>
                                    @error('hero_subtitle')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-4">Features / Why Choose Us</h4>
                            <div class="space-y-4">
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
                                              rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('features_subtitle', $settings['features_subtitle'] ?? '') }}</textarea>
                                    @error('features_subtitle')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div>
                            <h4 class="text-md font-semibold text-gray-700 mb-4">CTA Section</h4>
                            <div class="space-y-4">
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
                                              rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('cta_text', $settings['cta_text'] ?? '') }}</textarea>
                                    @error('cta_text')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-2">
                                    Primary Color
                                </label>
                                <input type="color"
                                       name="primary_color"
                                       id="primary_color"
                                       value="{{ old('primary_color', $settings['primary_color'] ?? '#3B82F6') }}"
                                       class="w-full h-12 px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('primary_color')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="secondary_color" class="block text-sm font-medium text-gray-700 mb-2">
                                    Secondary Color
                                </label>
                                <input type="color"
                                       name="secondary_color"
                                       id="secondary_color"
                                       value="{{ old('secondary_color', $settings['secondary_color'] ?? '#6B7280') }}"
                                       class="w-full h-12 px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('secondary_color')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-2">
                                    Accent Color
                                </label>
                                <input type="color"
                                       name="accent_color"
                                       id="accent_color"
                                       value="{{ old('accent_color', $settings['accent_color'] ?? '#10B981') }}"
                                       class="w-full h-12 px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('accent_color')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Layout Settings -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                        </div>

                        <!-- Dark Mode -->
                        <div class="flex items-center">
                            <input type="checkbox"
                                   name="enable_dark_mode"
                                   id="enable_dark_mode"
                                   value="1"
                                   {{ old('enable_dark_mode', $settings['enable_dark_mode'] ?? false) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="enable_dark_mode" class="ml-2 text-sm font-medium text-gray-700">
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
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Meta Description
                            </label>
                            <textarea name="meta_description"
                                      id="meta_description"
                                      rows="3"
                                      placeholder="A brief description of your website for search engines"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                            @error('meta_description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Meta Keywords -->
                        <div>
                            <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                                Meta Keywords
                            </label>
                            <input type="text"
                                   name="meta_keywords"
                                   id="meta_keywords"
                                   value="{{ old('meta_keywords', $settings['meta_keywords'] ?? '') }}"
                                   placeholder="keyword1, keyword2, keyword3"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Separate keywords with commas</p>
                            @error('meta_keywords')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Google Analytics -->
                        <div>
                            <label for="google_analytics" class="block text-sm font-medium text-gray-700 mb-2">
                                Google Analytics Code
                            </label>
                            <textarea name="google_analytics"
                                      id="google_analytics"
                                      rows="6"
                                      placeholder="<!-- Google Analytics tracking code -->"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">{{ old('google_analytics', $settings['google_analytics'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Paste your complete Google Analytics tracking code</p>
                            @error('google_analytics')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Facebook Pixel -->
                        <div>
                            <label for="facebook_pixel" class="block text-sm font-medium text-gray-700 mb-2">
                                Facebook Pixel Code
                            </label>
                            <textarea name="facebook_pixel"
                                      id="facebook_pixel"
                                      rows="6"
                                      placeholder="<!-- Facebook Pixel Code -->"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">{{ old('facebook_pixel', $settings['facebook_pixel'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Paste your complete Facebook Pixel code</p>
                            @error('facebook_pixel')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Integrations Tab -->
                <div x-show="activeTab === 'integrations'" class="p-6" style="display: none;">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Analytics & Integrations</h3>
                    <p class="text-sm text-gray-500 mb-6">Enter your tracking IDs to enable analytics and marketing integrations. Leave blank to disable.</p>

                    <div class="space-y-6">
                        <!-- Google Analytics 4 -->
                        <div>
                            <label for="google_analytics_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Google Analytics 4 Measurement ID
                            </label>
                            <input type="text"
                                   name="google_analytics_id"
                                   id="google_analytics_id"
                                   value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}"
                                   placeholder="G-XXXXXXXXXX"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Format: G-XXXXXXXXXX</p>
                            @error('google_analytics_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Google Tag Manager -->
                        <div>
                            <label for="google_tag_manager_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Google Tag Manager Container ID
                            </label>
                            <input type="text"
                                   name="google_tag_manager_id"
                                   id="google_tag_manager_id"
                                   value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id'] ?? '') }}"
                                   placeholder="GTM-XXXXXXX"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Format: GTM-XXXXXXX</p>
                            @error('google_tag_manager_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Meta Pixel -->
                        <div>
                            <label for="meta_pixel_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Meta Pixel ID
                            </label>
                            <input type="text"
                                   name="meta_pixel_id"
                                   id="meta_pixel_id"
                                   value="{{ old('meta_pixel_id', $settings['meta_pixel_id'] ?? '') }}"
                                   placeholder="XXXXXXXXXXXXXXXX"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Your Facebook/Meta Pixel numeric ID</p>
                            @error('meta_pixel_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Search Console Verification -->
                        <div>
                            <label for="search_console_token" class="block text-sm font-medium text-gray-700 mb-2">
                                Google Search Console Verification Token
                            </label>
                            <input type="text"
                                   name="search_console_token"
                                   id="search_console_token"
                                   value="{{ old('search_console_token', $settings['search_console_token'] ?? '') }}"
                                   placeholder="Verification token from GSC"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">The content value from the HTML tag verification method</p>
                            @error('search_console_token')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Microsoft Clarity -->
                        <div>
                            <label for="microsoft_clarity_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Microsoft Clarity Project ID
                            </label>
                            <input type="text"
                                   name="microsoft_clarity_id"
                                   id="microsoft_clarity_id"
                                   value="{{ old('microsoft_clarity_id', $settings['microsoft_clarity_id'] ?? '') }}"
                                   placeholder="clarity-project-id"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Your Microsoft Clarity project ID</p>
                            @error('microsoft_clarity_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Google Maps API Key -->
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <h4 class="text-md font-semibold text-gray-700 mb-2">Google Maps</h4>
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

                    <!-- Owner Notification Webhook -->
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <h4 class="text-md font-semibold text-gray-700 mb-2">Owner Notification Webhook</h4>
                        <p class="text-sm text-gray-500 mb-4">
                            Kirim event booking (created / confirmed / cancelled / completed) ke URL eksternal.
                            Kosongkan untuk nonaktif. Cocok untuk Fonnte, Wablas, OneSender, n8n, Make, atau
                            endpoint JSON apa pun. Setiap event juga tercatat di log aplikasi.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label for="notification_webhook" class="block text-sm font-medium text-gray-700 mb-2">
                                    Webhook URL
                                </label>
                                <input type="url"
                                       name="notification_webhook"
                                       id="notification_webhook"
                                       value="{{ old('notification_webhook', $settings['notification_webhook'] ?? '') }}"
                                       placeholder="https://api.fonnte.com/send"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                                <p class="text-xs text-gray-500 mt-1">POST endpoint yang menerima JSON. Akan dipanggil dengan timeout 5 detik (1 retry pada 5xx).</p>
                                @error('notification_webhook')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="notification_webhook_secret" class="block text-sm font-medium text-gray-700 mb-2">
                                    Webhook Secret (opsional)
                                </label>
                                <input type="text"
                                       name="notification_webhook_secret"
                                       id="notification_webhook_secret"
                                       value="{{ old('notification_webhook_secret', $settings['notification_webhook_secret'] ?? '') }}"
                                       placeholder="shared-secret-string"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                                <p class="text-xs text-gray-500 mt-1">Jika diisi, payload akan dikirim bersama header <code>X-Webhook-Signature: sha256=...</code> (HMAC-SHA256). Receiver bisa verifikasi integritas.</p>
                                @error('notification_webhook_secret')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <details class="bg-gray-50 border border-gray-200 rounded-md p-3">
                                <summary class="text-sm font-medium text-gray-700 cursor-pointer">Contoh payload JSON</summary>
<pre class="mt-3 text-xs text-gray-700 overflow-x-auto"><code>{
  "event": "booking.created",
  "sent_at": "2026-08-12T19:30:00+07:00",
  "booking": {
    "id": 42,
    "code": "BK-20260812-0001",
    "status": "pending",
    "booking_type": "daily",
    "unit_type": "studio",
    "unit_label": "Studio",
    "check_in": "2026-08-15T14:00:00+07:00",
    "check_out": "2026-08-17T12:00:00+07:00",
    "guests": 2,
    "total_price": 850000,
    "deposit_amount": 255000,
    "currency": "IDR",
    "customer": {
      "name": "Budi",
      "phone": "6281234567890",
      "whatsapp": "6281234567890"
    },
    "property": { "id": 1, "name": "Kakarama Room Sudirman" },
    "admin_url": "/admin/bookings/42"
  }
}</code></pre>
                            </details>
                        </div>
                    </div>
                </div>

                <!-- ===== PRICING TAB ===== -->
                <div x-show="activeTab === 'pricing'" class="p-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Pengaturan Harga & Weekend</h3>
                        <p class="text-sm text-gray-500 mb-6">
                            Konfigurasi hari weekend secara global. Properti individual bisa meng-override pengaturan ini
                            di tab Harga pada halaman edit properti.
                        </p>

                        <!-- Weekend Days Mode -->
                        <div class="mb-6">
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
                        </div>

                        <!-- Custom weekend range (shown when mode = custom) -->
                        <div x-data="{ show: '{{ old('weekend_days_mode', $settings['weekend_days_mode'] ?? 'sat_sun') }}' === 'custom' }"
                             @weekend-mode-changed.window="show = $event.detail.value === 'custom'"
                             x-show="show"
                             class="ml-4 p-4 bg-amber-50 border border-amber-200 rounded-lg mb-6">
                            <p class="text-xs text-amber-700 font-medium mb-3">Pengaturan Custom Weekend</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="weekend_start_day" class="block text-xs font-medium text-gray-700 mb-1">Hari Awal Weekend</label>
                                    <select name="weekend_start_day"
                                            id="weekend_start_day"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        @foreach(['0'=>'Minggu','1'=>'Senin','2'=>'Selasa','3'=>'Rabu','4'=>'Kamis','5'=>'Jumat','6'=>'Sabtu'] as $dayVal => $dayName)
                                            <option value="{{ $dayVal }}" {{ old('weekend_start_day', $settings['weekend_start_day'] ?? '5') == $dayVal ? 'selected' : '' }}>
                                                {{ $dayName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1">Hari awal weekend (inklusif).</p>
                                </div>
                                <div>
                                    <label for="weekend_end_day" class="block text-xs font-medium text-gray-700 mb-1">Hari Akhir Weekend</label>
                                    <select name="weekend_end_day"
                                            id="weekend_end_day"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        @foreach(['0'=>'Minggu','1'=>'Senin','2'=>'Selasa','3'=>'Rabu','4'=>'Kamis','5'=>'Jumat','6'=>'Sabtu'] as $dayVal => $dayName)
                                            <option value="{{ $dayVal }}" {{ old('weekend_end_day', $settings['weekend_end_day'] ?? '0') == $dayVal ? 'selected' : '' }}>
                                                {{ $dayName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1">Hari akhir weekend (inklusif). Bisa wrap ke hari berikutnya.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== BOOKING TAB ===== -->
                <div x-show="activeTab === 'booking'" class="p-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Pengaturan Booking</h3>
                        <p class="text-sm text-gray-500 mb-6">Konfigurasi perilaku sistem booking yang berlaku untuk semua properti.</p>

                        <!-- Min Transit Hours -->
                        <div class="mb-5">
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
                        </div>

                        <!-- Default Check-in Time -->
                        <div class="mb-5">
                            <label for="booking_checkin_default_time" class="block text-sm font-medium text-gray-700 mb-1">
                                Jam Check-in Default
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="time"
                                       name="booking_checkin_default_time"
                                       id="booking_checkin_default_time"
                                       value="{{ old('booking_checkin_default_time', $settings['booking_checkin_default_time'] ?? '14:00') }}"
                                       class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <span class="text-sm text-gray-500">pre-filled di form booking frontend</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Default: 14:00. User bisa mengubahnya saat memesan.</p>
                        </div>

                        <!-- Auto Confirm -->
                        <div class="mb-5">
                            <div class="flex items-start gap-4">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input type="hidden" name="booking_auto_confirm" value="0">
                                    <input type="checkbox"
                                           name="booking_auto_confirm"
                                           id="booking_auto_confirm"
                                           value="1"
                                           {{ ($settings['booking_auto_confirm'] ?? '0') === '1' ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="booking_auto_confirm" class="block text-sm font-medium text-gray-700">
                                        Auto Confirm Booking
                                    </label>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Jika aktif, booking baru langsung berstatus <strong>confirmed</strong> tanpa perlu konfirmasi manual admin.
                                        Matikan jika ingin review setiap booking terlebih dahulu.
                                    </p>
                                </div>
                            </div>
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
