@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
])

<div {{ $attributes->class('mb-6') }}>
    @if ($eyebrow)
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-1">{{ $eyebrow }}</p>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            @if ($title)
                <flux:heading level="1" size="lg" class="text-zinc-900 dark:text-white">{{ $title }}</flux:heading>
            @endif

            @if ($description)
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 max-w-2xl">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
