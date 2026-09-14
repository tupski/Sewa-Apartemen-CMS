@use(\Illuminate\Support\Facades\Auth)
@use(\Illuminate\Support\Facades\Cache)

@php
    $authUser = Auth::user();
    $authRoleName = $authUser?->roles->first()?->name;

    $gitUpdateState = Cache::get(\App\Console\Commands\CheckForGitUpdates::CACHE_KEY);
    $gitUpdateAvailable = ($gitUpdateState['available'] ?? false) === true;
    $gitUpdateCount = (int) ($gitUpdateState['commits_behind'] ?? 0);

    $breadcrumbs = $breadcrumbs ?? collect();
@endphp

<div class="flex w-full items-center justify-between gap-3 min-w-0">
    <div class="flex items-center gap-2 min-w-0">
        <flux:sidebar.toggle class="lg:hidden" />

        @if ($breadcrumbs->isNotEmpty())
            <flux:breadcrumbs class="max-lg:hidden">
                @foreach ($breadcrumbs as $crumb)
                    <flux:breadcrumbs.item href="{{ $crumb['url'] ?? null }}">{{ $crumb['label'] }}</flux:breadcrumbs.item>
                @endforeach
            </flux:breadcrumbs>
        @endif
    </div>

    <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <flux:button-or-link href="{{ url('/') }}" target="_blank" rel="noopener" variant="ghost" size="sm" square
            :aria-label="__('admin.view_website')" :title="__('admin.view_website')">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            <span class="sr-only">{{ __('admin.view_website') }}</span>
        </flux:button-or-link>

        @if ($gitUpdateAvailable)
            <flux:button-or-link href="{{ route('admin.settings.index', ['group' => 'version_control']) }}" variant="danger" size="sm"
                data-testid="git-update-badge"
                :aria-label="__('git.update_badge_aria', ['count' => $gitUpdateCount])"
                :title="__('git.update_badge_aria', ['count' => $gitUpdateCount])">
                <i class="fa-solid fa-cloud-arrow-down" aria-hidden="true"></i>
                <span class="hidden sm:inline">{{ __('git.update_badge_label', ['count' => $gitUpdateCount]) }}</span>
            </flux:button-or-link>
        @endif

        <flux:dropdown position="bottom" align="end">
            <flux:profile :name="$authUser?->name" :initials="$authUser?->initials()" data-testid="profile-menu-trigger" />

            <flux:menu class="w-56">
                <flux:menu.heading>{{ $authUser?->name }}</flux:menu.heading>
                <flux:menu.item class="opacity-70 pointer-events-none">{{ $authRoleName ?? __('admin.no_role') }}</flux:menu.item>

                <flux:menu.separator />

                <flux:menu.item icon="user" href="{{ route('profile.edit') }}">{{ __('admin.profile') }}</flux:menu.item>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item variant="danger" type="submit">{{ __('admin.logout') }}</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>
</div>
