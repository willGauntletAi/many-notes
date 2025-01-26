<?php

declare(strict_types=1);

namespace App\Livewire\Vault;

use App\Actions\GetPathFromVaultNode;
use App\Livewire\Forms\VaultForm;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultNode;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

final class Index extends Component
{
    public VaultForm $form;

    public bool $showCreateModal = false;

    public function create(): void
    {
        $this->form->create();
        $this->reset('showCreateModal');
        $this->dispatch('toast', message: __('Vault created'), type: 'success');
    }

    public function export(Vault $vault): ?BinaryFileResponse
    {
        $this->authorize('view', $vault);
        $zip = new ZipArchive;
        $zipFileName = $vault->id . '.zip';
        $nodes = $vault->nodes()->whereNull('parent_id')->get();

        if ($zip->open(public_path($zipFileName), ZipArchive::CREATE) !== true) {
            $this->dispatch('toast', message: __('Something went wrong'), type: 'error');

            return null;
        }

        $this->exportNodes($zip, $nodes);
        $zip->close();

        return response()->download(public_path($zipFileName), $vault->name . '.zip')->deleteFileAfterSend(true);
    }

    public function delete(Vault $vault): void
    {
        $this->authorize('delete', $vault);
        DB::beginTransaction();
        try {
            $rootNodes = $vault->nodes()->whereNull('parent_id')->get();
            foreach ($rootNodes as $node) {
                $this->deleteNode($node);
            }
            $vault->delete();
            DB::commit();
            $this->dispatch('toast', message: __('Vault deleted'), type: 'success');
        } catch (Throwable) {
            DB::rollBack();
            $this->dispatch('toast', message: __('Something went wrong'), type: 'error');
        }
    }

    public function render(): Factory|View
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        return view('livewire.vault.index', [
            'vaults' => $currentUser->vaults()->orderBy('updated_at', 'DESC')->get(),
        ]);
    }

    /**
     * @param  Collection<int, VaultNode>  $nodes
     */
    private function exportNodes(ZipArchive &$zip, Collection $nodes, string $path = ''): void
    {
        foreach ($nodes as $node) {
            $nodePath = Str::ltrim("$path/$node->name", '/');

            if ($node->is_file) {
                if ($node->extension === 'md') {
                    $zip->addFromString("$nodePath.$node->extension", (string) $node->content);
                } else {
                    $relativePath = new GetPathFromVaultNode()->handle($node);
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

    private function deleteNode(VaultNode $node): void
    {
        foreach ($node->childs as $child) {
            $this->deleteNode($child);
        }
        $node->delete();
    }
}
