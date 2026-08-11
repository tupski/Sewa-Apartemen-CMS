<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 6: Finish Installation - Apartment CMS Installer</title>
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
                        <div class="w-24 h-0.5 bg-green-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
                                5
                            </div>
                            <span class="mt-2 text-sm text-green-600">Website</span>
                        </div>
                        <div class="w-24 h-0.5 bg-blue-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                6
                            </div>
                            <span class="mt-2 text-sm text-blue-600">Finish</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Installation Complete!</h2>

                    <p class="text-gray-600 mb-6">
                        Your Apartment Rental CMS has been successfully installed.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-6">
                        <h3 class="font-semibold text-blue-900 mb-2">Next Steps:</h3>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>1. Login to your admin panel</li>
                            <li>2. Add your first property</li>
                            <li>3. Configure your website settings</li>
                            <li>4. Start managing your apartment rentals</li>
                        </ul>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('install.finish') }}">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <a href="{{ url('/') }}" class="flex items-center justify-center bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Open Website
                            </a>
                            <button type="submit" class="flex items-center justify-center bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                                Open Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
