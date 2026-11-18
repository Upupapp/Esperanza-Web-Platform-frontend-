@props([
    'label',
    'labelEn' => null,
    'value' => null,
    'icon' => null,       {{-- legacy: still accepted; picks the object when `object` is not given --}}
    'color' => 'brand',   {{-- legacy: still accepted; picks the tone when `tone` is not given --}}
    'tone' => null,       {{-- brand | sky | deep | navy | violet | teal | gold | amber | rose --}}
    'object' => null,     {{-- people | document | tray | ring | stamp | calendar | flame | building | box | bell --}}
    'layout' => 'default',{{-- default (wide: object on the right) | compact (6-up grids: object faded into the corner) --}}
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
        'people'   => ['users', 'users-round', 'user', 'user-round-check', 'user-round-search', 'user-round-x', 'user-x', 'house', 'smile'],
        'document' => ['file-text', 'file-stack', 'clipboard-list', 'pencil-line', 'gallery-horizontal', 'flag', 'eye', 'eye-off'],
        'tray'     => ['inbox', 'file-clock', 'clock', 'loader', 'timer', 'search'],
        'ring'     => ['hand-heart', 'hand-coins', 'heart', 'shield-check', 'shield-half', 'shield-x', 'badge-check', 'wallet', 'triangle-alert', 'circle-x'],
        'stamp'    => ['package-check', 'circle-check', 'megaphone', 'banknote', 'receipt'],
        'calendar' => ['calendar-clock', 'calendar-check', 'calendar-days'],
        'flame'    => ['flame', 'siren'],
        'building' => ['building-2', 'building', 'school'],
        'box'      => ['package', 'boxes'],
        'bell'     => ['bell', 'bell-ring'],
    ];
    if (! $object) {
        $object = 'document';
        foreach ($objectMap as $name => $icons) {
            if (in_array($icon, $icons, true)) { $object = $name; break; }
        }
    }

    $compact = $layout === 'compact';
    $cardClass = $compact
        ? 'group slab slab-diag slab-' . $tone . ' relative overflow-hidden flex flex-col gap-1.5 min-h-[136px] rounded-[22px] p-4 border border-white/30 transition-[transform,box-shadow] duration-[220ms] hover:-translate-y-[2px] animate-fade-up'
        : 'group slab slab-' . $tone . ' relative overflow-hidden flex flex-col justify-center gap-[5px] min-h-[118px] rounded-3xl pl-[22px] pr-[112px] sm:pr-[130px] py-[18px] border border-white/30 transition-[transform,box-shadow] duration-[220ms] hover:-translate-y-[3px] animate-fade-up';
    $objectClass = $compact
        ? 'absolute -right-[30px] -bottom-[34px] w-[104px] h-[104px] -rotate-6 scale-[1.25] origin-[70%_70%] opacity-45 pointer-events-none'
        : 'absolute right-2.5 top-1/2 w-[104px] h-[104px] -translate-y-1/2 -rotate-6 scale-[.85] sm:scale-100 pointer-events-none';

    $tag = $href ? 'a' : 'div';
    $clickExpr = $attributes->get('@click') ?? $attributes->get('x-on:click');
    $needsKeyboard = ! $href && filled($clickExpr);
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => $cardClass . ($href || $needsKeyboard ? ' cursor-pointer' : '')]) }}
    @if($needsKeyboard)
        tabindex="0"
        role="button"
        @keydown.enter.prevent="{{ $clickExpr }}"
        @keydown.space.prevent="{{ $clickExpr }}"
    @endif
    style="animation-delay: {{ $delay }}ms"
>
    {{-- object first so it paints under the text --}}
    <div aria-hidden="true" class="{{ $objectClass }}">
        @include('components.ui.kpi-object.' . $object)
    </div>
    <p class="relative {{ $compact ? 'text-[11.5px] pr-6' : 'text-[12.5px]' }} font-semibold leading-snug text-[var(--slab-sub)]" x-text="$store.lang.current === 'en' ? @js($labelEn ?? $label) : @js($label)">{{ $label }}</p>
    <div class="relative flex items-baseline gap-2 flex-wrap {{ $compact ? 'mt-auto' : '' }}">
        {{-- compact: the number shrinks on phones, so a peso total like ₱2,340.00 stays inside its card (sweep 18 Nov) --}}
        <p class="{{ $compact ? 'text-[length:clamp(20px,6vw,30px)] sm:text-[30px] min-w-0 [overflow-wrap:anywhere]' : 'text-[32px]' }} font-extrabold tracking-[-.03em] leading-none tabular-nums" @if($valueBind) x-text="{{ $valueBind }}" @endif>{{ $value }}</p>
        @if($trend)
            <span class="inline-flex items-center gap-1 text-xs font-bold rounded-full px-2 py-0.5 frost-2">
                <i data-lucide="{{ $trendDirection === 'up' ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5"></i>
                {{ $trend }}
            </span>
        @endif
    </div>
    @if($sublabelBind)
        <p class="relative {{ $compact ? 'text-[11.5px] leading-snug min-h-[30px] max-w-[96px] text-balance' : 'text-xs' }} text-[var(--slab-sub)]" x-text="{{ $sublabelBind }}"></p>
    @elseif($sublabel)
        <p class="relative {{ $compact ? 'text-[11.5px] leading-snug min-h-[30px] max-w-[96px] text-balance' : 'text-xs' }} text-[var(--slab-sub)]" x-text="$store.lang.current === 'en' ? @js($sublabelEn ?? $sublabel) : @js($sublabel)">{{ $sublabel }}</p>
    @elseif($compact)
        {{-- keep the number on one baseline across a row of compact cards --}}
        <p class="relative text-[11.5px] leading-snug min-h-[30px]" aria-hidden="true"></p>
    @endif
</{{ $tag }}>
