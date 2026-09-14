<div>
    <x-admin.page-header
        :eyebrow="__('admin.shell_demo_eyebrow')"
        :title="__('admin.shell_demo')"
        :description="__('admin.shell_demo_description')"
    >
        <x-slot:actions>
            <flux:button-or-link href="{{ route('dashboard') }}" variant="ghost">
                {{ __('admin.back_to_dashboard') }}
            </flux:button-or-link>

            <flux:button variant="primary" onclick="window.toast(@js(__('admin.shell_demo_toast')), 'success')" wire:ignore>
                {{ __('admin.shell_demo_action') }}
            </flux:button>
        </x-slot:actions>
    </x-admin.page-header>

    <flux:card>
        <flux:heading level="2">{{ __('admin.shell_demo_card_title') }}</flux:heading>

        <flux:callout variant="inline" color="blue" class="mt-4">
            <flux:callout.text>{{ __('admin.shell_demo_callout') }}</flux:callout.text>
        </flux:callout>

        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('admin.shell_demo_body') }}</p>
    </flux:card>
</div>
