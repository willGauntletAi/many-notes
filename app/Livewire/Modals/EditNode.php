<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

use App\Models\Vault;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use Illuminate\Contracts\View\View;
use App\Livewire\Forms\VaultNodeForm;
use Illuminate\Contracts\View\Factory;

class EditNode extends Modal
{
    public VaultNodeForm $form;

    public bool $show = false;

    public function mount(Vault $vault): void
    {
        $this->authorize('update', $vault);
        $this->form->setVault($vault);
    }

    #[On('open-modal')]
    public function open(VaultNode $node): void
    {
        $this->authorize('update', $node->vault);
        $this->form->setNode($node);
        $this->openModal();
    }

    public function edit(): void
    {
        $this->form->update();
        $this->closeModal();
        $this->dispatch('node-updated');
        $this->dispatch('file-refresh', node: $this->form->node);
        $message = $this->form->is_file ? __('File edited') : __('Folder edited');
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function render(): Factory|View
    {
        return view('livewire.modals.editNode');
    }
}
