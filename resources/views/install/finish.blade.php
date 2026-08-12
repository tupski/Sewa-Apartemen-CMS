<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 6: Finish Installation - Apartment CMS Installer</title>
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

            <!-- Error State: Migration Failed -->
            <div id="error-panel" class="mt-8 bg-white rounded-lg shadow-md p-6 hidden">
                <div class="text-center">
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold text-red-900 mb-4">Migration Failed</h2>

                    <div id="error-message" class="bg-red-50 border border-red-200 rounded p-4 mb-6 text-left">
                        <p class="text-sm text-red-800 font-mono break-all"></p>
                    </div>

                    <p class="text-gray-600 mb-2 text-sm">
                        Some database tables already exist from a previous installation.
                    </p>
                    <p class="text-gray-500 mb-6 text-sm">
                        You can reset the database and start fresh. <strong class="text-red-600">Warning: This will delete all existing data!</strong>
                    </p>

                    <!-- Fresh Install Button -->
                    <button id="fresh-btn" class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-red-700 transition flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Fresh Install (Reset Database)
                    </button>

                    <p class="text-xs text-gray-400 mt-4">
                        Or fix the database manually and reload this page.
                    </p>

                    <div id="fresh-status" class="hidden text-center mt-4">
                        <div id="fresh-spinner" class="inline-block w-8 h-8 border-4 border-red-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                        <p id="fresh-status-text" class="text-gray-700 font-medium"></p>
                    </div>
                </div>
            </div>

            <!-- Success State -->
            <div id="success-panel" class="mt-8 bg-white rounded-lg shadow-md p-6 hidden">
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
                    <form method="POST" action="{{ route('install.finish') }}" id="finish-form">
                        @csrf
                        <div id="action-buttons" class="grid grid-cols-2 gap-4">
                            <a href="{{ url('/') }}" class="flex items-center justify-center bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Open Website
                            </a>
                            <button type="submit" id="complete-btn" class="flex items-center justify-center bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                                Complete Installation
                            </button>
                        </div>
                        <div id="status-message" class="hidden text-center mt-4">
                            <div id="spinner" class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                            <p id="status-text" class="text-gray-700 font-medium"></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
    </style>

    <script>
    // On page load, attempt installation via AJAX
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '{{ csrf_token() }}';

    function doInstall() {
        fetch('{{ route("install.finish") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showSuccess(data);
            } else if (data.fresh_install_available) {
                showError(data);
            } else {
                showError(data);
            }
        })
        .catch(function() {
            // Network error — likely server restart killed the connection.
            // installed.lock was already written, retrying will redirect correctly.
            var spinner = document.getElementById('spinner');
            var statusText = document.getElementById('status-text');
            document.getElementById('success-panel').classList.remove('hidden');
            document.getElementById('action-buttons').classList.add('hidden');
            document.getElementById('status-message').classList.remove('hidden');
            statusText.textContent = 'Server is restarting. Redirecting in 5 seconds…';
            spinner.classList.add('hidden');
            setTimeout(function() {
                window.location.href = '{{ route("dashboard") }}';
            }, 5000);
        });
    }

    function showSuccess(data) {
        document.getElementById('success-panel').classList.remove('hidden');
        document.getElementById('action-buttons').classList.add('hidden');
        document.getElementById('status-message').classList.remove('hidden');
        document.getElementById('status-text').textContent = data.message;

        if (data.restarting) {
            setTimeout(function() {
                window.location.href = data.redirect;
            }, 4000);
        } else {
            window.location.href = data.redirect;
        }
    }

    function showError(data) {
        document.getElementById('error-panel').classList.remove('hidden');
        document.getElementById('error-message').querySelector('p').textContent = data.error || 'Unknown error';

        if (!data.fresh_install_available) {
            // Hide fresh button — not a table-exists error
            document.getElementById('fresh-btn').classList.add('hidden');
        }
    }

    // Fresh install button handler
    document.getElementById('fresh-btn').addEventListener('click', function() {
        if (!confirm('WARNING: This will delete ALL existing database tables and data. Continue?')) {
            return;
        }

        var btn = document.getElementById('fresh-btn');
        var status = document.getElementById('fresh-status');
        var statusText = document.getElementById('fresh-status-text');
        var errorMsg = document.getElementById('error-message');

        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        status.classList.remove('hidden');
        statusText.textContent = 'Resetting database…';

        fetch('{{ route("install.fresh") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('error-panel').classList.add('hidden');
                showSuccess(data);
            } else {
                statusText.textContent = 'Fresh install failed.';
                errorMsg.querySelector('p').textContent = data.error || 'Unknown error';
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        })
        .catch(function() {
            // Server restart — installed.lock written, navigate
            statusText.textContent = 'Server is restarting. Redirecting in 5 seconds…';
            document.getElementById('fresh-spinner').classList.add('hidden');
            setTimeout(function() {
                window.location.href = '{{ route("dashboard") }}';
            }, 5000);
        });
    });

    // Auto-trigger install on page load
    doInstall();
    </script>
</body>
</html>
