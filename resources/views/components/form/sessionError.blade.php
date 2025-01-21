@props(['error'])

@if ($error)
    <p {{ $attributes->merge(['class' => 'font-medium text-sm text-error-600 dark:text-error-500']) }}>
        {{ $error }}
    </p>
@endif
