<?php

namespace App\Livewire\Flux;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Phase 0 — smoke test component untuk memverifikasi integrasi
 * Flux UI + Livewire + pipeline Tailwind v4 pada admin.
 *
 * Sengaja tidak menyentuh business logic apa pun; akan dihapus /
 * diganti saat Phase 1 (admin shell) menggantikannya.
 */
#[Layout('layouts.flux')]
#[Title('Flux Setup Smoke Test')]
class SmokeTest extends Component
{
    public string $name = '';

    public ?string $pong = null;

    #[Validate('required|string|min:2')]
    public string $message = '';

    public function ping(): void
    {
        $validated = $this->validate();

        $this->pong = 'Pong: '.$validated['message'];
    }

    public function render()
    {
        return view('livewire.flux.smoke-test');
    }
}
