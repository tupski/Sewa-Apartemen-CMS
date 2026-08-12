<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 4: Create Admin Account - Apartment CMS Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <div class="w-24 h-0.5 bg-blue-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                4
                            </div>
                            <span class="mt-2 text-sm text-blue-600">Admin</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Form -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Create Admin Account</h2>

                @if(session('error'))
                    <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                        <p class="text-red-700">{{ session('error') }}</p>
                    </div>
                @endif

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

                <form method="POST" action="{{ route('install.admin') }}">
                    @csrf

                    <!-- Name -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Name *
                        </label>
                        <input type="text"
                               name="name"
                               value="{{ old('name') }}"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('name') ? 'border-red-500' : '' }}"
                               placeholder="Admin User"
                               required>
                        @if($errors->has('name'))
                            <p class="text-red-600 text-sm mt-1">{{ $errors->first('name') }}</p>
                        @endif
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Email Address *
                        </label>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('email') ? 'border-red-500' : '' }}"
                               placeholder="admin@yourdomain.com"
                               required>
                        @if($errors->has('email'))
                            <p class="text-red-600 text-sm mt-1">{{ $errors->first('email') }}</p>
                        @endif
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password *
                        </label>
                        <input type="password"
                               name="password"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('password') ? 'border-red-500' : '' }}"
                               placeholder="Minimum 8 characters"
                               required>
                        @if($errors->has('password'))
                            <p class="text-red-600 text-sm mt-1">{{ $errors->first('password') }}</p>
                        @endif
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Confirm Password *
                        </label>
                        <input type="password"
                               name="password_confirmation"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('password_confirmation') ? 'border-red-500' : '' }}"
                               placeholder="Confirm your password"
                               required>
                        @if($errors->has('password_confirmation'))
                            <p class="text-red-600 text-sm mt-1">{{ $errors->first('password_confirmation') }}</p>
                        @endif
                    </div>

                    <!-- Note -->
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-blue-700 text-sm">
                            <strong>Note:</strong> Use a strong password with at least 8 characters including uppercase, lowercase, numbers, and symbols.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <a href="{{ route('install.step', 3) }}" class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 transition text-center">
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
