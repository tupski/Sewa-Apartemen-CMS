@props([
    'title',
    'description' => null,
    'createRoute' => null,
    'createLabel' => 'Tambah Baru',
])

<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h2>
        @if($description)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>

    @if($createRoute)
        <a href="{{ $createRoute }}"
           class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ $createLabel }}
        </a>
    @endif
</header>
