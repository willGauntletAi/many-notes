<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

use App\Models\Vault;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use App\Services\VaultFiles\Image;
use Illuminate\Contracts\View\View;
use App\Livewire\Forms\VaultNodeForm;
use Illuminate\Contracts\View\Factory;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;

class MarkdownEditorSearch extends Modal
{
    public VaultNodeForm $form;

    public Vault $vault;

    /** @var list<array<string, mixed>> */
    public array $nodes;

    public bool $show = false;

    public string $search = '';

    public string $searchType = 'all';

    public function mount(Vault $vault): void
    {
        $this->authorize('view', $vault);
        $this->vault = $vault;
        $this->form->setVault($vault);
    }

    #[On('open-modal')]
    public function open(string $type = 'all'): void
    {
        $this->searchType = $type;
        $this->reset('search');
        $this->openModal();
    }

    public function search(): void
    {
        $nodes = VaultNode::query()
            ->select('id', 'name', 'extension')
            ->where('vault_id', $this->vault->id)
            ->where('is_file', true)
            ->when($this->searchType === 'image', function (Builder $query): void {
                $query->whereIn('extension', Image::extensions());
            })
            ->when(mb_strlen($this->search), function (Builder $query): void {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $this->nodes = [];
        foreach ($nodes as $node) {
            /**
             * @var string $fullPath
             * @phpstan-ignore-next-line larastan.noUnnecessaryCollectionCall
             */
            $fullPath = $node->ancestorsAndSelf()->get()->last()->full_path;
            $fullPathEncoded = preg_replace('/\s/', '%20', $fullPath);
            $dirName = preg_replace('/'.$node->name.'$/', '', $fullPath);

            $this->nodes[] = [
                'id' => $node->id,
                'name' => $node->name,
                'extension' => $node->extension,
                'full_path' => $fullPath,
                'full_path_encoded' => $fullPathEncoded,
                'dir_name' => $dirName,
            ];
        };
    }

    public function render(): Factory|View
    {
        $this->search();

        return view('livewire.modals.markdownEditorSearch');
    }
}
