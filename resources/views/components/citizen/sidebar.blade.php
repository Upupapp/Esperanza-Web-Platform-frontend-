@props(['active' => 'dashboard'])

<div
    x-show="mobileOpen"
    x-transition.opacity
    x-cloak
    @click="mobileOpen = false"
    class="fixed inset-0 bg-navy-950/60 z-40 lg:hidden"

        role="button"
        tabindex="0"
        @keydown.enter.prevent="mobileOpen = false"
        @keydown.space.prevent="mobileOpen = false"></div>

{{-- Clay design (STYLE_GUIDE §2): a dark glass panel, rounded and inset from
     the page on large screens; the mobile drawer stays full height and square. --}}
<aside
    x-init="$store.queueCounts.load('citizen')"
    class="fixed lg:static inset-y-0 left-0 z-50 flex flex-col sidebar-glass lg:rounded-[30px] lg:my-0 lg:h-[calc(100vh-2.5rem)] text-white shrink-0 w-64 px-3 py-3 transition-transform duration-[260ms] ease-standard -translate-x-full lg:translate-x-0"
    :class="[collapsed ? 'lg:w-[76px]' : 'lg:w-64', mobileOpen ? 'translate-x-0' : '-translate-x-full']"
>
    <div class="flex items-center gap-3 px-0.5 pb-2 shrink-0">
        <span class="w-[46px] h-[46px] rounded-full bg-gold-400 shadow-clay-gold p-[2px] shrink-0">
            <img
                src="{{ asset('images/esperanza/esperanza-seal-128.png') }}"
                alt="Municipality of Esperanza Seal"
                class="w-full h-full rounded-full object-cover"
            >
        </span>
        <div x-show="!collapsed" x-transition.opacity.duration.150ms class="min-w-0">
            <p class="text-[15px] font-extrabold leading-tight text-white truncate" data-brand-name>Esperanza LGU</p>
            <p class="text-xs text-sidebar-muted truncate" x-text="$store.lang.current === 'en' ? 'Citizen Portal' : 'Portal ng Mamamayan'"></p>
        </div>
        <button aria-label="Close" @click="mobileOpen = false" class="ml-auto lg:hidden w-9 h-9 rounded-xl grid place-items-center text-sidebar-text hover:bg-white/10 hover:text-white">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- My Barangay identity strip -->
    <div x-show="!collapsed && $store.barangay.mine" x-cloak class="sidebar-well rounded-[20px] flex items-center gap-2.5 px-3.5 py-3 mt-0.5 mb-1.5 shrink-0">
        <div class="relative w-7 h-7 shrink-0">
            <div class="absolute inset-0 rounded-full bg-white/10 flex items-center justify-center text-white/40"><i data-lucide="map-pinned" class="w-3.5 h-3.5"></i></div>
            <img
                :src="$store.barangay.mine?.seal ? '{{ rtrim(asset(''), '/') }}/' + $store.barangay.mine.seal : ''"
                x-show="$store.barangay.mine?.seal" x-on:error="$el.style.display='none'" alt=""
                class="relative w-7 h-7 rounded-full object-cover"
            >
        </div>
        <p class="text-[13px] text-sidebar-muted truncate">
            Brgy. <span class="font-bold text-white" x-text="$store.barangay.mine?.label"></span>
        </p>
    </div>

    <nav class="flex-1 overflow-y-auto scrollbar-dark -mx-1 px-1 pt-1 pb-2 space-y-[2px]">
        <x-ui.nav-item dense :href="route('citizen.dashboard')" icon="layout-dashboard" label="Home" labelEn="Dashboard" :active="$active === 'dashboard'" />
        <x-ui.nav-item dense :href="route('citizen.document-requests')" icon="file-text" label="Dokyu" badgeBind="$store.queueCounts.dokyu" :active="$active === 'document-requests'" />
        <x-ui.nav-item dense :href="route('citizen.assistance-requests')" icon="hand-heart" label="Tulong" badgeBind="$store.queueCounts.tulong" :active="$active === 'assistance-requests'" />
        <x-ui.nav-item dense :href="route('citizen.announcements')" icon="megaphone" label="Balita" :active="$active === 'announcements'" />
        <x-ui.nav-item dense :href="route('citizen.events')" icon="calendar-days" label="Kaganapan" labelEn="Events" :active="$active === 'events'" />
        <x-ui.nav-item dense :href="route('citizen.directory')" icon="building-2" label="Direktoryo" labelEn="Directory" :active="$active === 'directory'" />
        <x-ui.nav-item dense :href="route('citizen.faqs')" icon="life-buoy" label="Suporta" labelEn="Help" :active="$active === 'help'" />
    </nav>

    <div class="pt-1.5 shrink-0">
        <button
            @click="collapsed = !collapsed"
            class="w-full h-11 hidden lg:flex items-center justify-center gap-2 rounded-2xl bg-white/[.06] border border-white/[.08] text-[13px] font-semibold text-sidebar-text hover:text-white hover:bg-white/[.09] shadow-[4px_5px_12px_rgb(0_0_0/.25),inset_0_1px_0_rgb(255_255_255/.12)] active:shadow-pressed-dark transition-colors"
        >
            <i data-lucide="panel-left-close" class="w-4 h-4 transition-transform duration-[160ms] ease-standard" :class="collapsed && 'rotate-180'"></i>
            <span x-show="!collapsed" x-text="$store.lang.current === 'en' ? 'Collapse' : 'I-collapse'"></span>
        </button>
    </div>
</aside>
