@props(['node'])

<x-treeView.item>
    @if (!$node->is_file)
        <x-treeView.itemFolder :$node />

        @if (!empty($node->children) && $node->children->count())
            @include('components.vault.treeViewNode', ['nodes' => $node->children, 'root' => false])
        @endif
    @else
        <x-treeView.itemFile :$node />
    @endif
</x-treeView.item>
