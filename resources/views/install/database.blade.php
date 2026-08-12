<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Step 3: Database Configuration - Apartment CMS Installer</title>
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
                        <div class="w-24 h-0.5 bg-blue-600"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                3
                            </div>
                            <span class="mt-2 text-sm text-blue-600">Database</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Config Form -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Database Configuration</h2>

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

                <form method="POST" action="{{ route('install.database') }}" id="database-form">
                    @csrf

                    <div class="mb-6">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700">
                                Database Host
                            </label>
                            <button type="button"
                                    class="text-blue-600 hover:text-blue-800 text-sm"
                                    id="test-connection-btn">
                                Test Connection
                            </button>
                        </div>
                        <input type="text"
                               id="db_host"
                               name="db_host"
                               value="{{ old('db_host', 'localhost') }}"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('db_host') ? 'border-red-500' : '' }}"
                               placeholder="localhost">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Database Port
                        </label>
                        <input type="number"
                               id="db_port"
                               name="db_port"
                               value="{{ old('db_port', '3306') }}"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('db_port') ? 'border-red-500' : '' }}"
                               placeholder="3306"
                               min="1" max="65535">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Database Name *
                        </label>
                        <input type="text"
                               id="db_database"
                               name="db_database"
                               value="{{ old('db_database') }}"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('db_database') ? 'border-red-500' : '' }}"
                               placeholder="apartment_cms"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Username *
                        </label>
                        <input type="text"
                               id="db_username"
                               name="db_username"
                               value="{{ old('db_username') }}"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('db_username') ? 'border-red-500' : '' }}"
                               placeholder="root"
                               required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <input type="password"
                               id="db_password"
                               name="db_password"
                               value="{{ old('db_password') }}"
                               class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm {{ $errors->has('db_password') ? 'border-red-500' : '' }}"
                               placeholder="Password">
                    </div>

                    <!-- Connection Status -->
                    <div id="connection-status" class="mb-6 hidden p-4 rounded-lg"></div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <a href="{{ route('install.step', 2) }}" class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 transition text-center">
                            Back
                        </a>
                        <button type="submit"
                                class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition"
                                id="proceed-btn"
                                disabled>
                            Proceed to Next Step
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testBtn = document.getElementById('test-connection-btn');
            const statusDiv = document.getElementById('connection-status');
            const proceedBtn = document.getElementById('proceed-btn');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            testBtn.addEventListener('click', function() {
                const data = {
                    db_host: document.getElementById('db_host').value,
                    db_port: document.getElementById('db_port').value,
                    db_database: document.getElementById('db_database').value,
                    db_username: document.getElementById('db_username').value,
                    db_password: document.getElementById('db_password').value,
                    test: true
                };

                statusDiv.className = 'mb-6 p-4 rounded-lg';
                statusDiv.innerHTML = '<p class="text-blue-600">Testing connection...</p>';
                statusDiv.classList.add('bg-blue-50');

                fetch('{{ route("install.database.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.className = 'mb-6 bg-green-50 border-l-4 border-green-500 p-4';
                        statusDiv.innerHTML = '<p class="text-green-700"><strong>' + data.message + '</strong></p>';
                        proceedBtn.disabled = false;
                        proceedBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        statusDiv.className = 'mb-6 bg-red-50 border-l-4 border-red-500 p-4';
                        statusDiv.innerHTML = '<p class="text-red-700"><strong>Connection failed:</strong> ' + data.message + '</p>';
                        proceedBtn.disabled = true;
                    }
                })
                .catch(err => {
                    statusDiv.className = 'mb-6 bg-red-50 border-l-4 border-red-500 p-4';
                    statusDiv.innerHTML = '<p class="text-red-700"><strong>Network error:</strong> Please check your connection</p>';
                    proceedBtn.disabled = true;
                });
            });
        });
    </script>
</body>
</html>
