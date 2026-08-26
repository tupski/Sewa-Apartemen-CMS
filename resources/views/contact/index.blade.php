@extends('layouts.frontend')

@php
    $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
    $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
    $contactEmail = \App\Services\SettingsService::get('contact_email', '');
    $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
    $contactAddress = \App\Services\SettingsService::get('contact_address', '');
    $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
    $businessHours = \App\Services\SettingsService::get('business_hours', '');
    $mapEmbed = \App\Services\SettingsService::get('contact_map_embed', '');
@endphp

@section('content')
    <!-- Page Header -->
    <section class="py-14 md:py-20 text-white"
             style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-sm text-white/80 hover:text-white inline-flex items-center mb-3">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('nav.home') }}
            </a>
            <h1 class="text-3xl md:text-5xl font-extrabold">{{ __('contact.title') }}</h1>
            <p class="mt-3 text-white/90 max-w-2xl">{{ __('contact.subtitle') }}</p>
        </div>
    </section>

    <section class="py-12 bg-gray-50 dark:bg-gray-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Contact Info -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">{{ __('contact.info_title') }}</h2>
                        <ul class="space-y-5 text-sm text-gray-700 dark:text-gray-300">
                            @if ($contactAddress)
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ __('contact.address') }}</p>
                                        <p class="mt-0.5 whitespace-pre-line">{{ $contactAddress }}</p>
                                    </div>
                                </li>
                            @endif
                            @if ($contactPhone)
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ __('contact.phone') }}</p>
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}" class="mt-0.5 block hover:underline">{{ $contactPhone }}</a>
                                    </div>
                                </li>
                            @endif
                            @if ($contactEmail)
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ __('contact.email') }}</p>
                                        <a href="mailto:{{ $contactEmail }}" class="mt-0.5 block hover:underline">{{ $contactEmail }}</a>
                                    </div>
                                </li>
                            @endif
                            @if ($whatsapp)
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white" style="background-color: #25d366">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">WhatsApp</p>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener" class="mt-0.5 block hover:underline">{{ $whatsapp }}</a>
                                    </div>
                                </li>
                            @endif
                            @if ($businessHours)
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ __('contact.hours') }}</p>
                                        <p class="mt-0.5 whitespace-pre-line">{{ $businessHours }}</p>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>

                    @if ($mapEmbed)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-2 overflow-hidden">
                            <div class="rounded-xl overflow-hidden aspect-video">
                                {!! $mapEmbed !!}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Contact Form -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">{{ __('contact.form_title') }}</h2>

                    @if (session('success'))
                        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('contact.store') }}" method="POST" class="space-y-5">
                        @csrf

                        {{-- Honeypot: hidden from humans, tempting to bots --}}
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('contact.name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2"
                                   style="--tw-ring-color: {{ $primaryColor }}">
                            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('contact.email') }}</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2"
                                   style="--tw-ring-color: {{ $primaryColor }}">
                            @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('contact.subject') }}</label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2"
                                   style="--tw-ring-color: {{ $primaryColor }}">
                            @error('subject') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('contact.message') }}</label>
                            <textarea name="message" id="message" rows="6" required
                                      class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2"
                                      style="--tw-ring-color: {{ $primaryColor }}">{{ old('message') }}</textarea>
                            @error('message') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- CAPTCHA (rendered only when enabled & configured) --}}
                        <x-captcha action="contact" />

                        @error('captcha') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                        <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-2.5 rounded-full text-sm font-semibold text-white transition hover:opacity-90"
                                style="background-color: {{ $primaryColor }}">
                            {{ __('contact.send') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
