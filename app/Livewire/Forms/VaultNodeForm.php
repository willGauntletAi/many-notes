<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Vault;
use App\Models\VaultNode;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;

class VaultNodeForm extends Form
{
    public Vault $vault;

    public ?VaultNode $node = null;

    public ?int $parent_id = null;

    public $is_file = true;

    #[Validate]
    public $name = '';

    public ?string $extension = null;

    public ?string $content = null;

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'min:3',
                'regex:/^[\w]+[\s\w.-]+$/u',
                Rule::unique(VaultNode::class)
                    ->where('vault_id', $this->vault->id)
                    ->where('parent_id', $this->parent_id)
                    ->where('extension', $this->extension)
                    ->ignore($this->node),
            ],
        ];
    }

    public function setVault(Vault $vault): void
    {
        $this->vault = $vault;
    }

    public function setNode(VaultNode $node): void
    {
        $this->node = $node;
        $this->parent_id = $node->parent_id;
        $this->is_file = $node->is_file;
        $this->name = $node->name;
        $this->extension = $node->extension;
        $this->content = $node->content;
    }

    public function create(): VaultNode
    {
        $this->validate();
        $this->name = Str::trim($this->name);
        $node = $this->vault->nodes()->create([
            'parent_id' => $this->parent_id,
            'is_file' => $this->is_file,
            'name' => $this->name,
            'extension' => $this->is_file ? 'md' : null,
            'content' => $this->content,
        ]);
        $this->reset(['name']);

        return $node;
    }

    public function update(): void
    {
        $this->validate();
        $this->name = Str::trim($this->name);
        $this->node->update([
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'content' => $this->content,
        ]);
    }
}
