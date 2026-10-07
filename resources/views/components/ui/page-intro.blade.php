@props(['id', 'icon' => 'sparkles', 'title', 'titleEn' => null, 'color' => 'brand'])

@php
    // Clay design (STYLE_GUIDE §3.11): a small glass panel with a clay icon tile.
    $tiles = [
        'brand' => 'clay-light',
        'purple' => 'bg-purple-100 text-purple-700 shadow-clay-light',
        'gold' => 'clay-gold-light',
    ];
    $tile = $tiles[$color] ?? $tiles['brand'];
@endphp

<div
    x-data="{
        show: false,
        init() { this.show = !localStorage.getItem('esperanza_intro_{{ $id }}'); },
        dismiss() { this.show = false; localStorage.setItem('esperanza_intro_{{ $id }}', '1'); },
    }"
    x-show="show"
    x-cloak
    x-transition
    class="relative flex items-start gap-3 surface-glass-sm rounded-[22px] px-4 py-3.5 mb-4"
>
    <span class="w-9 h-9 rounded-xl {{ $tile }} flex items-center justify-center shrink-0">
        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-ink" x-text="$store.lang.current === 'en' ? @js($titleEn ?? $title) : @js($title)">{{ $title }}</p>
        <p class="text-xs text-ink-muted mt-0.5 leading-relaxed">{{ $slot }}</p>
    </div>
    <button @click="dismiss()" class="text-ink-muted hover:text-ink shrink-0 transition-colors" aria-label="Dismiss">
        <i data-lucide="x" class="w-4 h-4"></i>
    </button>
</div>
