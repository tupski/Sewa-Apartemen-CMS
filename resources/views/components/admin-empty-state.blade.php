@props([
    'title',
    'description' => null,
    'actionRoute' => null,
    'actionLabel' => null,
    'icon' => 'fa-solid fa-inbox',
])

<div class="px-4 py-12 text-center">
    <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
        <i class="{{ $icon }} text-3xl text-gray-400 dark:text-gray-500" aria-hidden="true"></i>
    </div>
    <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
    @if($description)
        <p class="mx-auto mb-4 max-w-md text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @if($actionRoute && $actionLabel)
        <a href="{{ $actionRoute }}"
           class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ $actionLabel }}
        </a>
    @endif
</div>
