@props(['name' => '', 'src' => null, 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-14 h-14 text-lg',
    ];
    $dimension = $sizes[$size] ?? $sizes['md'];
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

@if($src)
    <img
        src="{{ $src }}"
        alt="{{ $name }}"
        {{ $attributes->merge(['class' => "$dimension rounded-full object-cover ring-2 ring-white/90 shadow-glass-sm shrink-0"]) }}
    >
@else
    <div {{ $attributes->merge(['class' => "$dimension rounded-full clay-blue flex items-center justify-center font-extrabold shrink-0"]) }}>
        {{ strtoupper($initials) }}
    </div>
@endif
