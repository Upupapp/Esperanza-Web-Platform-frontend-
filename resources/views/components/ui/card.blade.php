@props(['padded' => true, 'hover' => false])

<div {{ $attributes->merge([
        'class' => 'bg-white rounded-2xl border border-slate-100 shadow-card '
            . ($hover ? 'transition-shadow duration-[220ms] ease-standard hover:shadow-card-hover focus-within:shadow-card-hover ' : '')
            . ($padded ? 'p-4' : ''),
    ]) }}
>
    {{ $slot }}
</div>
