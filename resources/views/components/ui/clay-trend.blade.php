{{-- The clay trend chart (design handoff README "Requests trend").
     data: an Alpine expression for { labels: [...], series: [{ label, values,
     color, fill?, fillOpacity? }] }. Hovering a month shows a dashed guide, a
     dot on each line and a glass tooltip. --}}
@props(['data', 'label' => 'Trend chart'])

<div x-data="clayTrend()" x-effect="draw({{ $data }})" @mouseleave="hide()" {{ $attributes->merge(['class' => 'relative']) }}>
    <svg x-ref="svg" viewBox="0 0 600 250" class="w-full h-auto overflow-visible" role="img" aria-label="{{ $label }}"></svg>
    <div
        x-show="tip" x-cloak
        class="absolute top-1 -translate-x-1/2 surface-glass-sm rounded-xl px-3 py-2 pointer-events-none whitespace-nowrap"
        :style="tip ? 'left:' + tip.left : ''"
    >
        <p class="font-mono text-[11px] text-ink-muted" x-text="tip?.label"></p>
        <p class="text-[12.5px] font-semibold text-ink-body" x-text="tip?.text"></p>
    </div>
</div>
