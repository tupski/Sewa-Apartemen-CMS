@props([
    'items' => [],
    'current' => null,
])

@if(count($items) > 0 || $current)
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            @foreach($items as $item)
                <li class="flex items-center gap-1.5">
                    @if(!empty($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="transition hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif
                    <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">›</span>
                </li>
            @endforeach
            @if($current)
                <li class="font-medium text-gray-900 dark:text-gray-100" aria-current="page">{{ $current }}</li>
            @endif
        </ol>
    </nav>
@endif
