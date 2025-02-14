@props(['nodes', 'root'])

<x-treeView.items :root="$root">
    @foreach ($nodes as $node)
        <x-vault.treeViewRow :$node :key="$node->id" />
    @endforeach
</x-treeView.items>
