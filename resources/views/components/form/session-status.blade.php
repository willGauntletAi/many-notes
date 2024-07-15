@props(['status'])

@if ($status)
    <p {{ $attributes->merge(['class' => 'font-medium text-sm text-[#2BA84A]']) }}>
        {{ $status }}
    </p>
@endif
