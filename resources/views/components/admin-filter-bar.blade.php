@props([
    'action',
    'method' => 'GET',
    'resetRoute' => null,
])

<form action="{{ $action }}"
      method="{{ strtoupper($method) }}"
      class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    {{ $slot }}

    @if($resetRoute)
        <a href="{{ $resetRoute }}"
           class="rounded-lg px-3 py-2 text-sm text-gray-600 underline transition hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:text-gray-200">
            Reset
        </a>
    @endif
</form>
