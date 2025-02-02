<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

trait Modal
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
