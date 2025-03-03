<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class TranscribeFile extends Component
{
    public bool $isOpen = false;
    public ?string $transcription = null;
    public ?string $errorMessage = null;

    protected $listeners = [
        'open-transcribe-file-modal' => 'openModal',
    ];

    public function render(): View
    {
        return view('livewire.modals.transcribe-file');
    }

    public function openModal(): void
    {
        $this->reset(['transcription', 'errorMessage']);
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function insertTranscription(): void
    {
        if ($this->transcription) {
            $this->dispatch('insert-transcription', ['transcription' => $this->transcription]);
            $this->closeModal();
        }
    }

    public function set(string $property, mixed $value): void
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }
}