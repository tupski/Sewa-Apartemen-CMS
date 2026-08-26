<x-guest-layout>
    @php
        $siteName    = \App\Services\SettingsService::get('site_name', config('app.name', 'Sewa Apartemen'));
        $siteLogo    = \App\Services\SettingsService::get('site_logo', '');
        $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
    @endphp

    <div class="min-h-screen flex">

        {{-- ======================================================
             LEFT PANEL — Login Form
        ======================================================= --}}
        <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 py-12 lg:px-16 bg-white">

            {{-- Logo --}}
            <div class="mb-8">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}"
                             alt="{{ $siteName }}"
                             class="h-12 w-auto object-contain">
                    @else
                        <x-application-logo class="w-12 h-12 fill-current"
                                            style="color: {{ $primaryColor }}" />
                        <span class="text-xl font-bold text-gray-800 leading-tight">{{ $siteName }}</span>
                    @endif
                </a>
            </div>

            <div class="max-w-md mx-auto w-full">

                {{-- Heading --}}
                <h1 class="text-3xl font-bold text-gray-900 mb-1">{{ __('Welcome back') }}</h1>
                <p class="text-gray-500 mb-8">{{ __('Sign in to your account to continue.') }}</p>

                {{-- Session Status --}}
                <x-auth-session-status class="mb-4" :status="session('status')" />

                {{-- CAPTCHA / general validation errors (e.g. failed captcha) --}}
                @if ($errors->has('captcha'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first('captcha') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    {{-- Email --}}
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email"
                                      class="block mt-1 w-full"
                                      type="email"
                                      name="email"
                                      :value="old('email')"
                                      required
                                      autofocus
                                      autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Password --}}
                    <div class="mt-5">
                        <x-input-label for="password" :value="__('Password')" />
                        <x-password-input id="password"
                                          class="mt-1"
                                          name="password"
                                          autocomplete="current-password"
                                          required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center mt-5">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer">
                            <input id="remember_me"
                                   type="checkbox"
                                   class="rounded border-gray-300 shadow-sm focus:ring-2"
                                   style="accent-color: {{ $primaryColor }}"
                                   name="remember">
                            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                        </label>
                    </div>

                    {{-- CAPTCHA (rendered only when enabled & configured) --}}
                    <x-captcha action="login" />

                    {{-- Submit --}}
                    <div class="mt-6">
                        <button type="submit"
                                class="w-full flex justify-center items-center gap-2 px-4 py-3 rounded-lg text-white text-sm font-semibold shadow-sm transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="background-color: {{ $primaryColor }}; --tw-ring-color: {{ $primaryColor }}">
                            {{ __('Sign In') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Footer note --}}
            <p class="mt-10 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ $siteName }}. {{ __('All rights reserved.') }}
            </p>
        </div>

        {{-- ======================================================
             RIGHT PANEL — Decorative (hidden on mobile)
        ======================================================= --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden flex-col items-center justify-center p-12 text-white"
             style="background-color: {{ $primaryColor }}">

            {{-- Background pattern --}}
            <div class="absolute inset-0 opacity-10"
                 style="background-image: radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px),
                                          radial-gradient(circle at 80% 80%, #fff 1px, transparent 1px);
                        background-size: 48px 48px;">
            </div>

            {{-- Floating card decorations --}}
            <div class="absolute top-16 right-16 w-24 h-24 rounded-2xl bg-white/10 backdrop-blur-sm rotate-12"></div>
            <div class="absolute bottom-20 left-14 w-16 h-16 rounded-xl bg-white/10 backdrop-blur-sm -rotate-6"></div>
            <div class="absolute top-1/3 left-10 w-10 h-10 rounded-lg bg-white/15 rotate-45"></div>

            {{-- Content --}}
            <div class="relative z-10 text-center max-w-sm">
                {{-- Large logo mark --}}
                @if($siteLogo)
                    <img src="{{ asset('storage/' . $siteLogo) }}"
                         alt="{{ $siteName }}"
                         class="h-20 w-auto object-contain mx-auto mb-8 brightness-0 invert opacity-90">
                @else
                    <x-application-logo class="w-20 h-20 fill-current text-white/80 mx-auto mb-8" />
                @endif

                <h2 class="text-3xl font-bold mb-4 leading-tight">
                    {{ $siteName }}
                </h2>
                <p class="text-base text-white/80 leading-relaxed">
                    {{ __('Discover beautiful apartments and book your next stay with ease.') }}
                </p>

                {{-- Decorative dots --}}
                <div class="flex justify-center gap-2 mt-10">
                    <div class="w-2 h-2 rounded-full bg-white/70"></div>
                    <div class="w-2 h-2 rounded-full bg-white/40"></div>
                    <div class="w-2 h-2 rounded-full bg-white/40"></div>
                </div>
            </div>
        </div>

    </div>
</x-guest-layout>
