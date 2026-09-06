@props([
    'disabled' => false,
    'errorId' => null,
])

<input
    @disabled($disabled)
    @if($errorId && ! $attributes->has('aria-describedby')) aria-describedby="{{ $errorId }}" @endif
    {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100']) }}
>
