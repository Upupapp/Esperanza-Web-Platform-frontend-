@props([
    'label',
    'labelEn' => null,
    'value' => null,
    'icon' => null,       {{-- legacy: still accepted; picks the object when `object` is not given --}}
    'color' => 'brand',   {{-- legacy: still accepted; picks the tone when `tone` is not given --}}
    'tone' => null,       {{-- brand | sky | deep | navy | violet | teal | gold | amber | rose --}}
    'object' => null,     {{-- people | document | tray | ring | stamp | calendar --}}
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
    // Colour slab + frosted object (STYLE_GUIDE §3.4, README "KPI cards").
    $toneMap = [
        'brand' => 'brand', 'light' => 'sky', 'deep' => 'deep', 'slate' => 'navy',
        'purple' => 'violet', 'green' => 'teal', 'gold' => 'gold', 'orange' => 'amber', 'red' => 'rose',
    ];
    $tone = $tone ?? ($toneMap[$color] ?? 'brand');

    // Legacy icon names → object, so existing call sites render sensibly without edits.
    $objectMap = [
        'people'   => ['users', 'users-round', 'user', 'user-round-check', 'user-round-search', 'user-round-x', 'user-x', 'house', 'building-2', 'smile'],
        'document' => ['file-text', 'file-stack', 'clipboard-list', 'pencil-line', 'gallery-horizontal', 'flag', 'eye', 'eye-off'],
        'tray'     => ['inbox', 'file-clock', 'clock', 'loader', 'timer', 'search', 'bell'],
        'ring'     => ['hand-heart', 'hand-coins', 'heart', 'shield-check', 'shield-half', 'shield-x', 'badge-check', 'wallet', 'triangle-alert', 'siren', 'circle-x'],
        'stamp'    => ['package-check', 'circle-check', 'megaphone', 'banknote', 'receipt'],
        'calendar' => ['calendar-clock', 'calendar-check', 'calendar-days'],
    ];
    if (! $object) {
        $object = 'document';
        foreach ($objectMap as $name => $icons) {
            if (in_array($icon, $icons, true)) { $object = $name; break; }
        }
    }

    $tag = $href ? 'a' : 'div';
    $clickExpr = $attributes->get('@click') ?? $attributes->get('x-on:click');
    $needsKeyboard = ! $href && filled($clickExpr);
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group slab slab-' . $tone . ' relative overflow-hidden flex flex-col justify-center gap-[5px] min-h-[118px] rounded-3xl pl-[22px] pr-[112px] sm:pr-[130px] py-[18px] border border-white/30 transition-[transform,box-shadow] duration-[220ms] hover:-translate-y-[3px] animate-fade-up' . ($href || $needsKeyboard ? ' cursor-pointer' : '')]) }}
    @if($needsKeyboard)
        tabindex="0"
        role="button"
        @keydown.enter.prevent="{{ $clickExpr }}"
        @keydown.space.prevent="{{ $clickExpr }}"
    @endif
    style="animation-delay: {{ $delay }}ms"
>
    <p class="relative text-[12.5px] font-semibold leading-snug text-[var(--slab-sub)]" x-text="$store.lang.current === 'en' ? @js($labelEn ?? $label) : @js($label)">{{ $label }}</p>
    <div class="relative flex items-baseline gap-2 flex-wrap">
        <p class="text-[32px] font-extrabold tracking-[-.03em] leading-none tabular-nums" @if($valueBind) x-text="{{ $valueBind }}" @endif>{{ $value }}</p>
        @if($trend)
            <span class="inline-flex items-center gap-1 text-xs font-bold rounded-full px-2 py-0.5 frost-2">
                <i data-lucide="{{ $trendDirection === 'up' ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5"></i>
                {{ $trend }}
            </span>
        @endif
    </div>
    @if($sublabelBind)
        <p class="relative text-xs text-[var(--slab-sub)]" x-text="{{ $sublabelBind }}"></p>
    @elseif($sublabel)
        <p class="relative text-xs text-[var(--slab-sub)]" x-text="$store.lang.current === 'en' ? @js($sublabelEn ?? $sublabel) : @js($sublabel)">{{ $sublabel }}</p>
    @endif
    <div aria-hidden="true" class="absolute right-2.5 top-1/2 w-[104px] h-[104px] -translate-y-1/2 -rotate-6 scale-[.85] sm:scale-100 pointer-events-none">
        @include('components.ui.kpi-object.' . $object)
    </div>
</{{ $tag }}>
