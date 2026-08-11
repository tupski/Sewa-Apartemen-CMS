<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2: Application Configuration - Apartment CMS Installer</title>
    <link rel="stylesheet" href="{{ asset('build/assets/app-DciKFhTV.css') }}">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-extrabold text-gray-900">Apartment CMS</h1>
                <p class="mt-2 text-sm text-gray-600">Web Installation Wizard</p>
            </div>

            <!-- Progress -->
            <div class="mt-8">
                <div class="flex justify-center">
                    <div class="flex items-center">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
                                1
                            </div>
                            <span class="mt-2 text-sm text-green-600">Requirements</span>
                        </div>
                        <div class="w-24 h-0.5 bg-blue-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                2
                            </div>
                            <span class="mt-2 text-sm text-blue-600">Application</span>
                        </div>
                        <div class="w-24 h-0.5 bg-gray-300"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 font-bold">
                                3
                            </div>
                            <span class="mt-2 text-sm text-gray-500">Database</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Config Form -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Application Configuration</h2>

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                        <p class="text-red-700"><strong>Please fix the following errors:</strong></p>
                        <ul class="list-disc list-inside mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('install.application') }}">
                    @csrf

                    <!-- Application Name -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Application Name *
                        </label>
                        <input type="text"
                               name="app_name"
                               value="{{ old('app_name', config('app.name', 'My Apartment')) }}"
                               class="form-input {{ $errors->has('app_name') ? 'border-red-500' : '' }}"
                               placeholder="e.g., My Apartment"
                               required>
                    </div>

                    <!-- Application URL -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Application URL *
                        </label>
                        <input type="url"
                               name="app_url"
                               value="{{ old('app_url', config('app.url', 'http://localhost')) }}"
                               class="form-input {{ $errors->has('app_url') ? 'border-red-500' : '' }}"
                               placeholder="https://yourdomain.com"
                               required>
                    </div>

                    <!-- Timezone -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Timezone *
                        </label>
                        <select name="timezone" class="form-select {{ $errors->has('timezone') ? 'border-red-500' : '' }}" required>
                            <option value="Asia/Jakarta" {{ old('timezone', 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB/WITA/WIT)</option>
                            <option value="Asia/Makassar" {{ old('timezone') == 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                            <option value="Asia/Jayapura" {{ old('timezone') == 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                            <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ old('timezone') == 'America/New_York' ? 'selected' : '' }}>America/New_York (EST/EDT)</option>
                        </select>
                    </div>

                    <!-- Locale -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Locale *
                        </label>
                        <select name="locale" class="form-select {{ $errors->has('locale') ? 'border-red-500' : '' }}" required>
                            <option value="id" {{ old('locale', 'id') == 'id' ? 'selected' : '' }}>Indonesian (id)</option>
                            <option value="en" {{ old('locale') == 'en' ? 'selected' : '' }}>English (en)</option>
                            <option value="ja" {{ old('locale') == 'ja' ? 'selected' : '' }}>Japanese (ja)</option>
                            <option value="ko" {{ old('locale') == 'ko' ? 'selected' : '' }}>Korean (ko)</option>
                            <option value="zh" {{ old('locale') == 'zh' ? 'selected' : '' }}>Chinese (zh)</option>
                        </select>
                    </div>

                    <!-- Currency -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Currency *
                        </label>
                        <select name="currency" class="form-select {{ $errors->has('currency') ? 'border-red-500' : '' }}" required>
                            <option value="IDR" {{ old('currency', 'IDR') == 'IDR' ? 'selected' : '' }}>Indonesian Rupiah (IDR)</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>US Dollar (USD)</option>
                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                            <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>British Pound (GBP)</option>
                            <option value="JPY" {{ old('currency') == 'JPY' ? 'selected' : '' }}>Japanese Yen (JPY)</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <a href="{{ route('install.step', 1) }}" class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 transition text-center">
                            Back
                        </a>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition">
                            Proceed to Next Step
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
