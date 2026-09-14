<div class="min-h-screen p-10">
    <flux:heading level="1" size="xl">Phase 0 — Flux Setup Smoke Test</flux:heading>

    <flux:callout class="mt-6 max-w-xl" icon="check-circle" color="green">
        <flux:callout.heading>Flux UI aktif</flux:callout.heading>

        <flux:callout.text>
            Komponen Flux, Livewire, dan pipeline Tailwind v4 ter-render dari halaman admin terisolasi.
        </flux:callout.text>
    </flux:callout>

    <flux:card class="mt-6 max-w-md">
        <form wire:submit="ping" class="space-y-4">
            <flux:field>
                <flux:label>Nama</flux:label>

                <flux:input wire:model="name" placeholder="Ketik sesuatu…" />

                <flux:error field="name" />
            </flux:field>

            <flux:field>
                <flux:label>Pesan (validasi: min. 2 karakter)</flux:label>

                <flux:input wire:model="message" placeholder="Ping…" />

                <flux:error field="message" />
            </flux:field>

            <flux:button variant="primary" type="submit">Ping server</flux:button>
        </form>

        @if ($pong)
            <flux:separator class="my-4" />

            <flux:text>
                {{ $pong }} — Livewire round-trip OK.
            </flux:text>
        @endif
    </flux:card>
</div>
