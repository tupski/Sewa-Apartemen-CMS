<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 5: Website Configuration - Apartment CMS Installer</title>
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
                        <div class="w-24 h-0.5 bg-green-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
                                2
                            </div>
                            <span class="mt-2 text-sm text-green-600">Application</span>
                        </div>
                        <div class="w-24 h-0.5 bg-green-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
                                3
                            </div>
                            <span class="mt-2 text-sm text-green-600">Database</span>
                        </div>
                        <div class="w-24 h-0.5 bg-green-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
                                4
                            </div>
                            <span class="mt-2 text-sm text-green-600">Admin</span>
                        </div>
                        <div class="w-24 h-0.5 bg-blue-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                5
                            </div>
                            <span class="mt-2 text-sm text-blue-600">Website</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Website Config Form -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Website Configuration</h2>

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

                <form method="POST" action="{{ route('install.website') }}">
                    @csrf

                    <!-- Website Name -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Website Name *
                        </label>
                        <input type="text"
                               name="site_name"
                               value="{{ old('site_name') }}"
                               class="form-input {{ $errors->has('site_name') ? 'border-red-500' : '' }}"
                               placeholder="My Apartment Website"
                               required>
                    </div>

                    <!-- Tagline -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tagline (Optional)
                        </label>
                        <input type="text"
                               name="site_tagline"
                               value="{{ old('site_tagline') }}"
                               class="form-input {{ $errors->has('site_tagline') ? 'border-red-500' : '' }}"
                               placeholder="Quality Living in Premium Location">
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Contact Email
                        </label>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="form-input {{ $errors->has('email') ? 'border-red-500' : '' }}"
                               placeholder="info@yourdomain.com">
                    </div>

                    <!-- Phone -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Contact Phone
                        </label>
                        <input type="tel"
                               name="phone"
                               value="{{ old('phone') }}"
                               class="form-input {{ $errors->has('phone') ? 'border-red-500' : '' }}"
                               placeholder="081234567890">
                    </div>

                    <!-- WhatsApp -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            WhatsApp Number
                        </label>
                        <input type="tel"
                               name="whatsapp"
                               value="{{ old('whatsapp') }}"
                               class="form-input {{ $errors->has('whatsapp') ? 'border-red-500' : '' }}"
                               placeholder="6281234567890">
                        <p class="text-sm text-gray-500 mt-1">Format: 6281234567890 (with country code)</p>
                    </div>

                    <!-- Address -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Address (Optional)
                        </label>
                        <textarea name="address"
                                  rows="3"
                                  class="form-input {{ $errors->has('address') ? 'border-red-500' : '' }}"
                                  placeholder="Jalan Example No 123, City, Province">{{ old('address') }}</textarea>
                    </div>

                    <!-- Colors -->
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Brand Colors</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Primary Color *
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color"
                                           name="primary_color"
                                           value="{{ old('primary_color', '#3B82F6') }}"
                                           class="w-12 h-10 rounded cursor-pointer">
                                    <input type="text"
                                           name="primary_color"
                                           value="{{ old('primary_color', '#3B82F6') }}"
                                           class="form-input {{ $errors->has('primary_color') ? 'border-red-500' : '' }}"
                                           placeholder="#3B82F6">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Secondary Color *
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color"
                                           name="secondary_color"
                                           value="{{ old('secondary_color', '#10B981') }}"
                                           class="w-12 h-10 rounded cursor-pointer">
                                    <input type="text"
                                           name="secondary_color"
                                           value="{{ old('secondary_color', '#10B981') }}"
                                           class="form-input {{ $errors->has('secondary_color') ? 'border-red-500' : '' }}"
                                           placeholder="#10B981">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Accent Color *
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color"
                                           name="accent_color"
                                           value="{{ old('accent_color', '#F59E0B') }}"
                                           class="w-12 h-10 rounded cursor-pointer">
                                    <input type="text"
                                           name="accent_color"
                                           value="{{ old('accent_color', '#F59E0B') }}"
                                           class="form-input {{ $errors->has('accent_color') ? 'border-red-500' : '' }}"
                                           placeholder="#F59E0B">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <a href="{{ route('install.step', 4) }}" class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 transition text-center">
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
