<div
    x-data="{ modalOpen: false }"
    x-modelable="modalOpen"
    {{ $attributes }}
    tabindex="-1"
>
    {{ $slot }}
</div>
