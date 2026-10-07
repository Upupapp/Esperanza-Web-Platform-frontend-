@props([
    'label',
    'labelEn' => null,
    'value' => null,
    'icon',
    'color' => 'brand',
    'sublabel' => null,
    'sublabelEn' => null,
    'trend' => null,
    'trendDirection' => 'up',
    'delay' => 0,
    'valueBind' => null,
    'sublabelBind' => null,
    'href' => null,
])

@php
    // Clay design (STYLE_GUIDE §3.4, README "KPI cards"): the icon is a clay coin.
    $colorMap = [
        'brand' => 'clay-blue',
        'green' => 'bg-emerald-100 text-emerald-700 shadow-clay-light',
        'purple' => 'bg-purple-100 text-purple-700 shadow-clay-light',
        'orange' => 'clay-gold',
        'red' => 'bg-rose-100 text-rose-700 shadow-clay-light',
        'gold' => 'clay-gold-light',
        'slate' => 'clay-navy',
        'light' => 'clay-light',
        'deep' => 'bg-gradient-to-br from-brand-600 to-brand-700 text-white shadow-clay-blue',
    ];
    $iconClasses = $colorMap[$color] ?? $colorMap['brand'];
    $tag = $href ? 'a' : 'div';

    // A caller that passes @click without href gets a plain <div>, which a mouse
    // can activate and a keyboard cannot. 28 call sites do exactly that, so the
    // fix belongs here rather than at each of them: put the card in the tab
    // order and mirror the click onto Enter and Space.
    $clickExpr = $attributes->get('@click') ?? $attributes->get('x-on:click');
    $needsKeyboard = ! $href && filled($clickExpr);
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group surface-glass rounded-[26px] p-[18px] pb-4 flex flex-col gap-3.5 transition-all duration-300 hover:-translate-y-1 hover:shadow-glass-hover animate-fade-up' . ($href || $needsKeyboard ? ' cursor-pointer' : '')]) }}
    @if($needsKeyboard)
        tabindex="0"
        role="button"
        @keydown.enter.prevent="{{ $clickExpr }}"
        @keydown.space.prevent="{{ $clickExpr }}"
    @endif
    style="animation-delay: {{ $delay }}ms"
>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11.5px] font-bold uppercase tracking-[.07em] leading-snug text-ink-muted max-w-[112px] text-balance" x-text="$store.lang.current === 'en' ? @js($labelEn ?? $label) : @js($label)">{{ $label }}</p>
        <div class="w-11 h-11 rounded-full {{ $iconClasses }} grid place-items-center shrink-0">
            <i data-lucide="{{ $icon }}" class="w-[19px] h-[19px]"></i>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <p class="text-3xl xl:text-4xl font-extrabold tracking-[-.03em] leading-none text-ink tabular-nums" @if($valueBind) x-text="{{ $valueBind }}" @endif>{{ $value }}</p>
        @if($trend)
            <span class="inline-flex items-center gap-1 text-xs font-semibold surface-pressed-sm rounded-full px-2 py-0.5 {{ $trendDirection === 'up' ? 'text-emerald-600' : 'text-rose-600' }}">
                <i data-lucide="{{ $trendDirection === 'up' ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5"></i>
                {{ $trend }}
            </span>
        @endif
    </div>
    @if($sublabelBind)
        <p class="self-start surface-pressed-sm rounded-full px-2.5 py-1 text-xs font-semibold text-ink-body" x-text="{{ $sublabelBind }}"></p>
    @elseif($sublabel)
        <p class="self-start surface-pressed-sm rounded-full px-2.5 py-1 text-xs font-semibold text-ink-body" x-text="$store.lang.current === 'en' ? @js($sublabelEn ?? $sublabel) : @js($sublabel)">{{ $sublabel }}</p>
    @endif
</{{ $tag }}>
