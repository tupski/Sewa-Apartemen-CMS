<?php

namespace App\Livewire\Flux;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.flux')]
#[Title('Shell Demo')]
class ShellDemo extends Component
{
    public function render()
    {
        return view('livewire.flux.shell-demo')
            ->layoutData([
                'breadcrumbs' => collect([
                    ['label' => __('admin.dashboard'), 'url' => route('dashboard')],
                    ['label' => __('admin.shell_demo')],
                ]),
            ]);
    }
}
