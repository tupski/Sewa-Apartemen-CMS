@props([
    'status',
    'label' => null,
])

@php
    $statusMap = match($status) {
        'published' => ['icon' => '●', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'label' => 'Published'],
        'draft' => ['icon' => '○', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', 'label' => 'Draft'],
        'pending' => ['icon' => '●', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400', 'label' => 'Pending'],
        'confirmed' => ['icon' => '●', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 'label' => 'Confirmed'],
        'cancelled' => ['icon' => '○', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'label' => 'Cancelled'],
        'completed' => ['icon' => '●', 'class' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400', 'label' => 'Completed'],
        default => ['icon' => '○', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', 'label' => ucfirst($status)],
    };
@endphp

<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusMap['class'] }}">
    <span aria-hidden="true">{{ $statusMap['icon'] }}</span>
    {{ $label ?? $statusMap['label'] }}
</span>
