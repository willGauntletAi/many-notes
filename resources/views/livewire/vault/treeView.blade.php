<div class="flex flex-grow px-4">
    <x-treeView>
        @if (count($nodes))
            @include('components.vault.treeViewNode', ['nodes' => $nodes, 'root' => true])
        @else
            <p>{{ __('Your vault is empty.') }}</p>
        @endif
    </x-treeView>
</div>
