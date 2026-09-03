@props(['padded' => true, 'hover' => false])

@php
    // A caller that passes @click gets a plain <div>: usable with a mouse,
    // unreachable by keyboard. admin/reports alone renders eight such cards.
    // Fixing it here covers every caller rather than each call site.
    $clickExpr = $attributes->get('@click') ?? $attributes->get('x-on:click');
    $needsKeyboard = filled($clickExpr);
@endphp

<div {{ $attributes->merge([
        'class' => 'bg-white rounded-2xl border border-slate-100 shadow-card '
            . ($hover ? 'transition-shadow duration-[220ms] ease-standard hover:shadow-card-hover focus-within:shadow-card-hover ' : '')
            . ($padded ? 'p-4' : ''),
    ]) }}
    @if($needsKeyboard)
        tabindex="0"
        role="button"
        @keydown.enter.prevent="{{ $clickExpr }}"
        @keydown.space.prevent="{{ $clickExpr }}"
    @endif
>
    {{ $slot }}
</div>
