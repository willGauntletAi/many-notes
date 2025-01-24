<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

use Livewire\Component;

class Modal extends Component
{
    public bool $show = false;

    public function openModal(): void
    {
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
    }
}
