<x-guest-layout>
    <div class="min-h-screen flex">
        <!-- Left Side: Login Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 py-12 lg:px-16 bg-white">
            <!-- Logo -->
            <div class="mb-10">
                <x-application-logo class="w-16 h-16 text-blue-600" />
            </div>

            <div class="max-w-md mx-auto w-full">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('Welcome back') }}</h1>
                <p class="text-gray-600 mb-8">{{ __('Sign in to your account') }}</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div class="mt-6">
                        <x-input-label for="password" :value="__('Password')" />

                        <x-text-input id="password" class="block mt-1 w-full"
                                        type="password"
                                        name="password"
                                        required autocomplete="current-password" />

                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between mt-6">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="underline text-sm text-blue-600 hover:text-blue-900" href="{{ route('password.request') }}">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif
                    </div>

                    <div class="mt-8">
                        <x-primary-button class="w-full">
                            {{ __('Log in') }}
                        </x-primary-button>
                    </div>
                </form>

                <!-- Register Link -->
                @if (Route::has('register'))
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            {{ __('Don\'t have an account?') }}
                            <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-500 underline ms-1">
                                {{ __('Register') }}
                            </a>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Background Image -->
        <div class="hidden lg:block lg:w-1/2 relative min-h-screen">
            <!-- Random background image from Picsum -->
            <img src="https://picsum.photos/1200/800.random?grayscale" alt="{{ __('Background') }}" class="absolute inset-0 w-full h-full object-cover">

            <!-- Dark overlay for readability -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-900/80 via-blue-800/70 to-indigo-900/80"></div>

            <!-- Content overlay -->
            <div class="absolute inset-0 flex flex-col items-center justify-center p-12 text-white">
                <div class="max-w-md text-center">
                    <h2 class="text-4xl lg:text-5xl font-bold mb-6 leading-tight">
                        {{ __('Find Your Perfect Stay') }}
                    </h2>
                    <p class="text-lg lg:text-xl text-blue-100 mb-8 leading-relaxed">
                        {{ __('Discover beautiful apartments and book your next getaway with ease.') }}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <x-primary-button class="w-full sm:w-auto bg-white text-blue-700 hover:bg-blue-50 px-8 py-3">
                            {{ __('Browse Properties') }}
                        </x-primary-button>
                        <x-secondary-button class="w-full sm:w-auto border-white text-white hover:bg-white/10 px-8 py-3">
                            {{ __('Learn More') }}
                        </x-secondary-button>
                    </div>
                </div>

                <!-- Decorative elements -->
                <div class="absolute bottom-12 left-1/2 -translate-x-1/2 flex gap-3">
                    <div class="w-2 h-2 rounded-full bg-white/50"></div>
                    <div class="w-2 h-2 rounded-full bg-white/30"></div>
                    <div class="w-2 h-2 rounded-full bg-white/30"></div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
