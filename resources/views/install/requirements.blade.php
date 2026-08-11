<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 1: Requirements - Apartment CMS Installer</title>
    @vite('resources/css/app.css')
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
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                1
                            </div>
                            <span class="mt-2 text-sm text-blue-600">Requirements</span>
                        </div>
                        <div class="w-24 h-0.5 bg-gray-300"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 font-bold">
                                2
                            </div>
                            <span class="mt-2 text-sm text-gray-500">Application</span>
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

            <!-- Requirements Check -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">System Requirements</h2>

                <!-- PHP Version -->
                <div class="mb-4">
                    <div class="flex items-center">
                        <span class="text-gray-700 w-32">PHP Version</span>
                        <span class="ml-auto font-semibold {{ $phpVersion ? 'text-green-600' : 'text-red-600' }}">
                            {{ $phpVersion ? '✓ ' . PHP_VERSION : '✗ ' . PHP_VERSION . ' (Required: 8.3+)' }}
                        </span>
                    </div>
                </div>

                <!-- Extensions -->
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Required Extensions</h3>
                    <div class="space-y-1">
                        @foreach($extensions as $ext => $loaded)
                            <div class="flex items-center">
                                <span class="text-gray-700 w-32">{{ $ext }}</span>
                                <span class="ml-auto {{ $loaded ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $loaded ? '✓' : '✗' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Permissions -->
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Directory Permissions</h3>
                    <div class="space-y-1">
                        @foreach($permissions as $dir => $writable)
                            <div class="flex items-center">
                                <span class="text-gray-700 w-32">{{ $dir }}</span>
                                <span class="ml-auto {{ $writable ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $writable ? '✓' : '✗' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Action -->
                <div class="mt-8">
                    <form method="POST" action="{{ route('install.requirements') }}" id="requirements-form">
                        @csrf
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition"
                                id="proceed-btn"
                                disabled>
                            Proceed to Next Step
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const proceedBtn = document.getElementById('proceed-btn');
            
            // Check requirements
            fetch('/install/requirements', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.passed) {
                    proceedBtn.disabled = false;
                    proceedBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            })
            .catch(err => console.error(err));
        });
    </script>
</body>
</html>
