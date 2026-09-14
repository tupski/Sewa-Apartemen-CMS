@use(\App\Support\AdminMenu)
@use(\App\Services\SettingsService)

@php
    $siteName = SettingsService::get('site_name', config('app.name'));
    $siteLogo = SettingsService::get('site_logo', '');
@endphp

<flux:sidebar.brand href="{{ route('dashboard') }}" class="border-b border-zinc-200 dark:border-white/10 pb-3">
    @if ($siteLogo)
        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-8 w-auto max-w-40 object-contain shrink-0 dark:brightness-0 dark:invert" />
    @else
        <span class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
            <i class="fa-solid fa-building text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
            <span class="truncate">{{ $siteName }}</span>
        </span>
    @endif
</flux:sidebar.brand>

<flux:sidebar.nav class="flex-1 overflow-y-auto" aria-label="{{ __('admin.sidebar_navigation') }}">
    @foreach (AdminMenu::groups() as $section)
        @if ($section['group'] !== '')
            {{-- Heading grup: zinc-500 bukan zinc-400 bawaan Flux agar kontras 4.83:1 (WCAG AA) --}}
            <flux:navlist.group :heading="$section['group']" class="mt-3 [&>div>div>div]:text-zinc-500 dark:[&>div>div>div]:text-zinc-400" />
        @endif

        <flux:navlist>
            @foreach ($section['items'] as $item)
                <flux:sidebar.item href="{{ route($item['route']) }}" :current="request()->routeIs($item['match'])">
                    <i class="{{ $item['icon'] }} w-4 text-center" slot="icon" aria-hidden="true"></i>
                    {{ $item['label'] }}
                </flux:sidebar.item>
            @endforeach
        </flux:navlist>
    @endforeach
</flux:sidebar.nav>
