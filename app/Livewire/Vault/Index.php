<?php

namespace App\Livewire\Vault;

use ZipArchive;
use App\Models\Vault;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Livewire\Forms\VaultForm;
use App\Actions\GetPathFromVaultNode;
use Illuminate\Support\Facades\Storage;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;

class Index extends Component
{
    public VaultForm $form;

    public $showCreateModal = false;

    public function create(): void
    {
        $this->validate();
        $this->form->create();
        $this->reset('showCreateModal');
        $this->dispatch('toast', message: __('Vault created'), type: 'success');
    }

    public function export(Vault $vault)
    {
        $this->authorize('view', $vault);

        $zip = new ZipArchive;
        $zipFileName = $vault->id . '.zip';
        $nodes = $vault->nodes()->whereNull('parent_id')->get();

        if ($zip->open(public_path($zipFileName), ZipArchive::CREATE) === TRUE) {
            $this->exportNodes($zip, $nodes);

            $zip->close();

            return response()->download(public_path($zipFileName), $vault->name . '.zip')->deleteFileAfterSend(true);
        }
    }

    private function exportNodes(ZipArchive &$zip, Collection $nodes, string $path = ''): void
    {
        foreach ($nodes as $node) {
            $nodePath = Str::ltrim("$path/$node->name", '/');

            if ($node->is_file) {
                if ($node->extension === 'md') {
                    $zip->addFromString("$nodePath.$node->extension", $node->content);
                } else {
                    $relativePath = (new GetPathFromVaultNode())->handle($node);
                    $filePath = Storage::disk('local')->path($relativePath);
                    $zip->addFile($filePath, "$nodePath.$node->extension");
                }
            } else {
                $zip->addEmptyDir($nodePath);

                if ($node->children->count()) {
                    $this->exportNodes($zip, $node->children, $nodePath);
                }
            }
        }
    }

    public function delete(Vault $vault): void
    {
        $this->authorize('delete', $vault);
        $vault->delete();
        $this->dispatch('toast', message: __('Vault deleted'), type: 'success');
    }

    public function render()
    {
        return view('livewire.vault.index', [
            'vaults' => auth()->user()->vaults()->orderBy('updated_at', 'DESC')->get(),
        ]);
    }
}
