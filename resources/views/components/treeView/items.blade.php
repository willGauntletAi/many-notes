@props(['root'])

<ul @unless ($root)
        x-show="accordionOpen" x-collapse x-cloak
    @endunless
    class="relative w-full pl-4 first:pl-0">
    {{ $slot }}
</ul>
