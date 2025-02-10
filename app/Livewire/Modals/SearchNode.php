<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

use App\Models\Vault;
use App\Models\VaultNode;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as IlluminateBuilder;
use Livewire\Attributes\On;
use Livewire\Component;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;

final class SearchNode extends Component
{
    use Modal;

    public Vault $vault;

    /** @var list<array<string, mixed>> */
    public array $nodes;

    public string $search = '';

    public function mount(Vault $vault): void
    {
        $this->authorize('view', $vault);
        $this->vault = $vault;
    }

    #[On('open-modal')]
    public function open(string $search = ''): void
    {
        $this->openModal();
        $this->search = $search;
    }

    public function search(): void
    {
        $this->nodes = [];
        preg_match('/tag:(#[\w:-]+)/', $this->search, $matches);
        $nodes = $matches === [] ? $this->searchText() : $this->searchTag($matches[1]);

        foreach ($nodes as $node) {
            /**
             * @var string $fullPath
             *
             * @phpstan-ignore-next-line larastan.noUnnecessaryCollectionCall
             */
            $fullPath = $node->ancestorsAndSelf()->get()->last()->full_path;
            $dirName = preg_replace('/' . $node->name . '$/', '', $fullPath);

            $this->nodes[] = [
                'id' => $node->id,
                'name' => $node->name,
                'extension' => $node->extension,
                'full_path' => $fullPath,
                'dir_name' => $dirName,
            ];
        }
    }

    public function render(): Factory|View
    {
        $this->search();

        return view('livewire.modals.searchNode');
    }

    /**
     * Searches for nodes with a given name.
     *
     * @return Collection<int, VaultNode>
     */
    private function searchText(): Collection
    {
        return VaultNode::query()
            ->select('id', 'name', 'extension')
            ->where('vault_id', $this->vault->id)
            ->where('is_file', true)
            ->when(mb_strlen($this->search), function (Builder $query): void {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();
    }

    /**
     * Searches for nodes with a given tag.
     *
     * @return Collection<int, VaultNode>
     */
    private function searchTag(string $tag): Collection
    {
        return VaultNode::query()
            ->select('id', 'name', 'extension')
            ->where('vault_id', $this->vault->id)
            ->where('is_file', true)
            ->whereHas('tags', fn (IlluminateBuilder $query): IlluminateBuilder => $query->where('name', $tag))
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();
    }
}
