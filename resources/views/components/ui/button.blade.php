@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'type' => 'button',
    // An Alpine expression that is truthy while the action is in flight, e.g.
    // loading="saving". The component then disables itself, sets aria-busy and
    // swaps the leading icon for a spinner -- so TAB 07's "disable during
    // flight and guard against double submission" is a property of the button
    // rather than something every form remembers to do.
    'loading' => null,
])

@php
    // Clay design (STYLE_GUIDE §3.1): clay for anything you press, glass for
    // the secondary button; the pressed shadow is the press feedback (no scale).
    $base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-2xl whitespace-nowrap transition-all duration-[160ms] ease-standard select-none focus:outline-none focus-visible:ring-4 active:duration-[80ms] disabled:pointer-events-none disabled:bg-none disabled:bg-[#e4e9f4] disabled:text-ink-faint disabled:shadow-none disabled:border-transparent';

    // Explicit min-height guarantees the WCAG 2.5.5 touch-target floor
    // regardless of padding/line-height math. `sm` is the intentional
    // "compact" tier — reserved for row/table actions, not general buttons.
    $sizes = [
        'sm' => 'min-h-[36px] px-3.5 py-2 text-xs',
        'md' => 'min-h-[44px] px-4.5 py-2.5 text-sm',
        'lg' => 'min-h-[48px] px-6 py-3.5 text-sm',
    ];

    $variants = [
        'primary' => 'clay-blue btn-shine hover:brightness-95 active:shadow-pressed-dark focus-visible:ring-brand-100',
        'secondary' => 'surface-glass-sm text-ink-body hover:bg-white/90 hover:shadow-glass active:shadow-pressed-sm active:bg-well focus-visible:ring-brand-100',
        'ghost' => 'bg-transparent text-ink-muted hover:bg-white/60 hover:text-ink focus-visible:ring-brand-100',
        'danger' => 'clay-rose btn-shine hover:brightness-95 active:shadow-pressed-dark focus-visible:ring-rose-100',
        'gold' => 'clay-gold btn-shine hover:brightness-[.97] active:shadow-[inset_3px_3px_8px_rgb(140_110_0/.35)] focus-visible:ring-gold-100',
    ];

    $classes = $base . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
    $iconSize = $size === 'lg' ? 'w-[18px] h-[18px]' : 'w-4 h-4';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $iconPosition === 'left')
            <i data-lucide="{{ $icon }}" class="{{ $iconSize }} shrink-0"></i>
        @endif
        <span>{{ $slot }}</span>
        @if($icon && $iconPosition === 'right')
            <i data-lucide="{{ $icon }}" class="{{ $iconSize }} shrink-0"></i>
        @endif
    </a>
@else
    <button
        type="{{ $type }}"
        @if($loading)
            :disabled="{{ $loading }}"
            :aria-busy="{{ $loading }}"
        @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        @if($loading)
            <svg x-show="{{ $loading }}" x-cloak class="animate-spin {{ $iconSize }} shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
            </svg>
        @endif
        @if($icon && $iconPosition === 'left')
            <i data-lucide="{{ $icon }}" class="{{ $iconSize }} shrink-0" @if($loading) x-show="!({{ $loading }})" @endif></i>
        @endif
        <span>{{ $slot }}</span>
        @if($icon && $iconPosition === 'right')
            <i data-lucide="{{ $icon }}" class="{{ $iconSize }} shrink-0"></i>
        @endif
    </button>
@endif
