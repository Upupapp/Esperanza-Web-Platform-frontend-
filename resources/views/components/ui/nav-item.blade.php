@props(['href' => '#', 'icon', 'label', 'labelEn' => null, 'active' => false, 'badge' => null, 'badgeBind' => null, 'badgeTitle' => null, 'dense' => false, 'moduleKey' => null, 'showIf' => null])

{{-- Clay design (STYLE_GUIDE §2): 40px items. The active one is blue clay whose
     shadow falls straight down, so the scrolling nav cannot clip it sideways.
     `dense` is still accepted; every sidebar item now has the same size. --}}
<a
    href="{{ $href }}"
    class="group relative flex items-center gap-3 min-h-[40px] px-3.5 rounded-2xl font-semibold text-[14.5px] transition-all duration-200
        {{ $active ? 'clay-blue font-bold shadow-[0_2px_6px_-3px_rgb(47_98_245/.3)]' : 'text-sidebar-text hover:bg-white/[.07] hover:text-white' }}"
    :class="collapsed && 'justify-center px-0'"
    :title="collapsed ? (($store.lang.current === 'en' ? @js($labelEn ?? $label) : @js($label))) : ''"
    @if($showIf) x-show="{{ $showIf }}" x-cloak @elseif($moduleKey) x-show="$store.session.can('{{ $moduleKey }}')" x-cloak @endif
>
    <i data-lucide="{{ $icon }}" class="w-[18px] h-[18px] shrink-0 {{ $active ? 'text-white' : 'text-sidebar-text group-hover:text-white' }} transition-colors"></i>
    <span x-show="!collapsed" x-transition.opacity.duration.150ms class="truncate" x-text="$store.lang.current === 'en' ? @js($labelEn ?? $label) : @js($label)">{{ $label }}</span>
    {{-- badgeBind: a live count (an Alpine expression), hidden while it is
         unknown or zero. A count is a yellow clay pill, active or not. --}}
    @if($badgeBind)
        <span x-show="!collapsed && {{ $badgeBind }}" x-cloak x-text="{{ $badgeBind }}" @if($badgeTitle) :title="{{ $badgeTitle }}" :aria-label="{{ $badgeTitle }}" @endif class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full clay-gold text-[11.5px] font-extrabold grid place-items-center"></span>
    @elseif($badge)
        <span x-show="!collapsed" class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full clay-gold text-[11.5px] font-extrabold grid place-items-center">{{ $badge }}</span>
    @endif
</a>
