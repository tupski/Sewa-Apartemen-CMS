<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ config('app.name', 'Laravel') }} - Admin Panel</title>

    @php $adminEnableDark = \App\Services\SettingsService::get('enable_dark_mode', false); @endphp

    <!-- Apply dark mode before first paint (no flash) -->
    <script>
        (function () {
            var stored = localStorage.getItem('admin.theme');
            var dark = stored ? stored === 'dark' : {{ $adminEnableDark ? 'true' : 'false' }};
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome 6 (free) — loaded async so CDN slowness doesn't block render -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css"></noscript>

    <!-- Lucide Icons (MIT) -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Quill 2 WYSIWYG (free, MIT) — loaded async -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css"></noscript>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-950">
    @stack('body_start')

    <!-- Skip to content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
        Skip to content
    </a>

    <div x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('admin.sidebar') === 'collapsed', dark: document.documentElement.classList.contains('dark') }"
         x-init="$watch('sidebarCollapsed', v => localStorage.setItem('admin.sidebar', v ? 'collapsed' : 'expanded'));
                 $watch('dark', v => { document.documentElement.classList.toggle('dark', v); localStorage.setItem('admin.theme', v ? 'dark' : 'light'); })"
         class="min-h-screen lg:flex">
        <!-- Sidebar -->
        <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-20' : 'lg:w-64']"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 transform transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 lg:shrink-0 flex flex-col">
            <div class="flex items-center justify-between h-16 px-4 bg-gray-900 shrink-0">
                <a href="{{ route('dashboard') }}" class="text-white text-xl font-bold flex items-center gap-2 overflow-hidden">
                    <i class="fa-solid fa-building text-blue-400 shrink-0"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''" class="truncate">CMS Admin</span>
                </a>
                <div class="flex items-center gap-1">
                    <!-- Collapse toggle (desktop) -->
                    <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden lg:inline-flex text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-1.5 transition" aria-label="Toggle sidebar" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <svg class="w-5 h-5 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <!-- Close (mobile) -->
                    <button @click="sidebarOpen = false" class="text-gray-400 hover:text-white lg:hidden focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-1.5" aria-label="Close sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="py-4 flex-1 overflow-y-auto" role="navigation" aria-label="Sidebar navigation">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('dashboard') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-gauge-high w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Dashboard</span>
                </a>

                <!-- CMS Section -->
                <p class="px-4 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase" :class="sidebarCollapsed ? 'lg:hidden' : ''">Content</p>

                <a href="{{ route('admin.pages.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.pages.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-regular fa-file-lines w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Pages</span>
                </a>

                <a href="{{ route('admin.blocks.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.blocks.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-cubes w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Blocks</span>
                </a>

                <a href="{{ route('admin.media.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.media.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-regular fa-images w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Media</span>
                </a>

                <a href="{{ route('admin.navigations.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.navigations.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-bars-staggered w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Navigation</span>
                </a>

                <a href="{{ route('admin.properties.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.properties.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-building w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Properties</span>
                </a>

                <a href="{{ route('admin.amenities.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.amenities.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-spa w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Amenities</span>
                </a>

                <!-- Blog Section -->
                <p class="px-4 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase" :class="sidebarCollapsed ? 'lg:hidden' : ''">Blog</p>

                <a href="{{ route('admin.posts.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.posts.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-regular fa-newspaper w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Posts</span>
                </a>

                <a href="{{ route('admin.categories.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.categories.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-folder-tree w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Categories</span>
                </a>

                <a href="{{ route('admin.tags.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.tags.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-tags w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Tags</span>
                </a>

                <!-- Bookings -->
                <p class="px-4 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase" :class="sidebarCollapsed ? 'lg:hidden' : ''">Booking</p>

                <a href="{{ route('admin.bookings.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.bookings.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-regular fa-calendar-check w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Bookings</span>
                </a>

                <a href="{{ route('admin.vouchers.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.vouchers.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-ticket w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Voucher</span>
                </a>

                <!-- Users -->
                <a href="{{ route('admin.users.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.users.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-users w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Users</span>
                </a>

                <!-- SEO Section -->
                <p class="px-4 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase" :class="sidebarCollapsed ? 'lg:hidden' : ''">SEO</p>

                <a href="{{ route('admin.redirects.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.redirects.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-arrow-right-arrow-left w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Redirects</span>
                </a>

                <!-- Settings -->
                <a href="{{ route('admin.settings.index') }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                   class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-none transition {{ request()->routeIs('admin.settings.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-gear w-5 mr-3 text-center shrink-0" :class="sidebarCollapsed ? 'lg:mr-0' : ''"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:flex-1 flex flex-col min-h-screen min-w-0">
            <!-- Header -->
            <header class="bg-white shadow-sm sticky top-0 z-40 dark:bg-gray-900 dark:border-b dark:border-gray-800">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <!-- Mobile menu button -->
                    <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 lg:hidden focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-1 dark:text-gray-300 dark:hover:text-white" aria-label="Open sidebar menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Page Title -->
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        @yield('page-title', 'Admin Panel')
                    </h1>

                    <div class="flex items-center space-x-3">
                        <!-- Dark mode toggle -->
                        <button @click="dark = !dark"
                                class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800"
                                aria-label="Toggle dark mode" :title="dark ? 'Light mode' : 'Dark mode'">
                            <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <svg x-show="dark" class="w-5 h-5" style="display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </button>

                        <!-- User Dropdown -->
                        <div x-data="{ dropdownOpen: false }" class="relative">
                            <button @click="dropdownOpen = !dropdownOpen" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-1 dark:text-gray-200 dark:hover:text-white" aria-label="User menu" aria-expanded="false" :aria-expanded="dropdownOpen">
                                <span class="text-sm font-medium">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="dropdownOpen"
                                 @click.away="dropdownOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 dark:bg-gray-800"
                                 style="display: none;">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div class="px-4 sm:px-6 lg:px-8 py-4">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center justify-between">
                        <span>{{ session('success') }}</span>
                        <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center justify-between">
                        <span>{{ session('error') }}</span>
                        <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4 flex items-center justify-between">
                        <span>{{ session('info') }}</span>
                        <button onclick="this.parentElement.remove()" class="text-blue-700 hover:text-blue-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>

            <!-- Main Content -->
            <main id="main-content" class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-6" role="main">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-4 sm:px-6 lg:px-8 dark:bg-gray-900 dark:border-gray-800">
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                    <p>Version 1.0.0</p>
                </div>
            </footer>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 lg:hidden"
             style="display: none;">
        </div>
    </div>

    <!-- Quill WYSIWYG: auto-init on any textarea with .wysiwyg -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Quill === 'undefined') return;
            document.querySelectorAll('textarea.wysiwyg').forEach(function (ta) {
                var holder = document.createElement('div');
                holder.classList.add('wysiwyg-container', 'bg-white', 'rounded-md', 'border', 'border-gray-300');
                ta.parentNode.insertBefore(holder, ta);
                var form = ta.closest('form');

                var quill = new Quill(holder, {
                    theme: 'snow',
                    placeholder: 'Tulis konten di sini...'
                });

                // Load existing HTML content
                if (ta.value) {
                    try {
                        quill.clipboard.dangerouslyPasteHTML(0, ta.value);
                    } catch (e) { /* keep empty */ }
                }

                // Keep hidden textarea in sync (Quill outputs full HTML)
                function sync() { ta.value = quill.root.innerHTML; }
                quill.on('text-change', sync);
                if (form) form.addEventListener('submit', sync);
                ta.style.display = 'none';
            });
        });
    </script>

    <!-- Scroll to top -->
    <button id="scroll-top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="hidden fixed bottom-6 right-6 z-50 w-11 h-11 rounded-full shadow-lg items-center justify-center text-white hover:opacity-90 transition bg-blue-600"
            aria-label="Scroll to top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <script>
        window.addEventListener('scroll', function () {
            var btn = document.getElementById('scroll-top');
            if (!btn) return;
            if (window.scrollY > 300) {
                btn.classList.remove('hidden');
                btn.classList.add('inline-flex');
            } else {
                btn.classList.add('hidden');
                btn.classList.remove('inline-flex');
            }
        });
    </script>

    @stack('scripts')

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
