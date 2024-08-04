@props(['status'])

@if ($status)
    <p {{ $attributes->merge(['class' => 'font-medium text-sm text-success-600 dark:text-success-500']) }}>
        {{ $status }}
    </p>
@endif
