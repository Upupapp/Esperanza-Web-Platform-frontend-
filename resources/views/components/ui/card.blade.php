@props(['padded' => true, 'hover' => false])

@php
    // A caller that passes @click gets a plain <div>: usable with a mouse,
    // unreachable by keyboard. admin/reports alone renders eight such cards.
    // Fixing it here covers every caller rather than each call site.
    $clickExpr = $attributes->get('@click') ?? $attributes->get('x-on:click');
    $needsKeyboard = filled($clickExpr);
@endphp

<div {{ $attributes->merge([
        // Clay design (STYLE_GUIDE §3.3): a glass container.
        'class' => 'surface-glass rounded-[26px] '
            . ($hover ? 'transition-[transform,box-shadow] duration-[220ms] ease-standard hover:-translate-y-1 hover:shadow-glass-hover focus-within:shadow-glass-hover ' : '')
            . ($padded ? 'p-5' : ''),
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
