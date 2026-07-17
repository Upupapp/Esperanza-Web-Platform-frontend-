@props(['active' => 'dashboard'])

<div
    x-show="mobileOpen"
    x-transition.opacity
    x-cloak
    @click="mobileOpen = false"
    class="fixed inset-0 bg-navy-950/60 z-40 lg:hidden"
></div>

<aside
    class="fixed lg:static inset-y-0 left-0 z-50 flex flex-col bg-navy-900 text-white shrink-0 w-64 transition-transform duration-300 ease-out -translate-x-full lg:translate-x-0"
    :class="[collapsed ? 'lg:w-[76px]' : 'lg:w-64', mobileOpen ? 'translate-x-0' : '-translate-x-full']"
>
    <div class="flex items-center gap-2.5 px-4 h-16 border-b border-white/10 shrink-0">
        <img
            src="{{ asset('images/esperanza/esperanza-seal.png') }}"
            alt="Municipality of Esperanza Seal"
            class="w-9 h-9 rounded-full ring-2 ring-white/15 shrink-0 object-cover"
        >
        <div x-show="!collapsed" x-transition.opacity.duration.150ms class="min-w-0">
            <p class="text-[13px] font-semibold tracking-wide leading-tight truncate">Esperanza LGU</p>
            <p class="text-[10.5px] text-slate-400 truncate">Administrative Portal</p>
        </div>
        <button @click="mobileOpen = false" class="ml-auto lg:hidden p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-white">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto scrollbar-thin px-2.5 py-3 space-y-0.5">
        <x-ui.nav-item dense :href="route('admin.dashboard')" icon="layout-dashboard" label="Dashboard" :active="$active === 'dashboard'" />
        <x-ui.nav-item dense :href="route('admin.constituents')" icon="users" label="Constituents" :active="$active === 'constituents'" />
        <x-ui.nav-item dense :href="route('admin.document-requests')" icon="file-text" label="Dokyu" badge="18" :active="$active === 'document-requests'" />
        <x-ui.nav-item dense :href="route('admin.assistance-requests')" icon="hand-heart" label="Tulong" badge="9" :active="$active === 'assistance-requests'" />
        <x-ui.nav-item dense :href="route('admin.payments')" icon="receipt" label="Payments" :active="$active === 'payments'" />
        <x-ui.nav-item dense :href="route('admin.communications')" icon="megaphone" label="Communications" :active="$active === 'communications'" />
        <x-ui.nav-item dense :href="route('admin.reports')" icon="file-bar-chart" label="Reports & Analytics" :active="$active === 'reports'" />
        <x-ui.nav-item dense :href="route('admin.users')" icon="user-cog" label="User Management" :active="$active === 'users'" />
    </nav>

    <div class="border-t border-white/10 p-2.5 shrink-0">
        <x-ui.nav-item dense :href="route('admin.settings')" icon="settings" label="Settings" :active="$active === 'settings'" />
        <button
            @click="collapsed = !collapsed"
            class="mt-0.5 w-full hidden lg:flex items-center justify-center gap-2 text-xs text-slate-400 hover:text-white py-2 rounded-xl hover:bg-white/[0.06] transition-colors"
        >
            <i data-lucide="panel-left-close" class="w-4 h-4 transition-transform duration-300" :class="collapsed && 'rotate-180'"></i>
            <span x-show="!collapsed">Collapse</span>
        </button>
    </div>
</aside>
