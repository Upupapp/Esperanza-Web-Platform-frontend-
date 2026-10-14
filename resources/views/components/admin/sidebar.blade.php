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
@php
    // The Dokyu and Tulong badges count what each page's "Needs Action" card
    // counts, from the same lists (live test 2026-10-13: the badge said 1
    // while the page said 3 needed action).
    $needsAction = [
        'dokyu' => (require base_path('resources/data/admin/document-requests.php'))['actionStatuses'],
        'tulong' => (require base_path('resources/data/admin/assistance-requests.php'))['actionStatuses'],
    ];
@endphp
<aside
    x-init="$store.queueCounts.load('admin', @js($needsAction))"
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
            <p class="text-xs text-sidebar-muted truncate">Administrative Portal</p>
        </div>
        <button aria-label="Close" @click="mobileOpen = false" class="ml-auto lg:hidden w-9 h-9 rounded-xl grid place-items-center text-sidebar-text hover:bg-white/10 hover:text-white">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Signed-in account + access scope -->
    <div x-show="$store.session.account && !collapsed" x-cloak class="sidebar-well rounded-[20px] px-3.5 py-3 mt-0.5 mb-1.5 shrink-0">
        <p class="text-[10.5px] font-bold uppercase tracking-[.08em] text-sidebar-label mb-0.5">Signed in as</p>
        <p class="text-[14.5px] font-bold text-white truncate" x-text="$store.session.account?.name"></p>
        <p class="text-xs text-sidebar-muted truncate mb-1.5" x-text="$store.session.account?.id + ' · ' + $store.session.account?.role"></p>
        <span
            class="inline-flex items-center gap-1.5 text-[11.5px] font-bold rounded-full px-2.5 py-[3px] border whitespace-nowrap"
            :class="$store.session.account?.scope === 'Municipal' ? 'bg-gold-400/15 border-gold-400/35 text-gold-400' : 'bg-purple-400/15 border-purple-300/35 text-purple-200'"
        >
            <i :data-lucide="$store.session.account?.scope === 'Municipal' ? 'landmark' : 'map-pin'" class="w-3 h-3"></i>
            <span x-text="$store.session.account?.scope === 'Municipal' ? 'Municipality-wide' : 'Brgy. ' + $store.session.account?.scope"></span>
        </span>
    </div>

    <nav class="flex-1 overflow-y-auto scrollbar-dark -mx-1 px-1 pt-1 pb-2 space-y-[2px]">
        <x-ui.nav-item dense :href="route('admin.dashboard')" icon="layout-dashboard" label="Dashboard" :active="$active === 'dashboard'" />
        <x-ui.nav-item dense :href="route('admin.constituents')" icon="users" label="Constituents" :active="$active === 'constituents'" moduleKey="constituents" />
        <x-ui.nav-item dense :href="route('admin.papeles')" icon="folder-open" label="Papeles" :active="$active === 'papeles'" moduleKey="papeles" />
        <x-ui.nav-item dense :href="route('admin.document-requests')" icon="file-text" label="Dokyu" badgeBind="$store.queueCounts.dokyu" badgeTitle="$store.queueCounts.needsAction($store.queueCounts.dokyu)" :active="$active === 'document-requests'" moduleKey="dokyu" />
        <x-ui.nav-item dense :href="route('admin.assistance-requests')" icon="hand-heart" label="Tulong" badgeBind="$store.queueCounts.tulong" badgeTitle="$store.queueCounts.needsAction($store.queueCounts.tulong)" :active="$active === 'assistance-requests'" moduleKey="tulong" />
        <x-ui.nav-item dense :href="route('admin.sakuna')" icon="shield-alert" label="Sakuna" :active="$active === 'sakuna'" moduleKey="sakuna" />
        <x-ui.nav-item dense :href="route('admin.payments')" icon="receipt" label="Payments" :active="$active === 'payments'" moduleKey="payments" />
        {{-- One entry: the Directory and the Blotter are tabs of Balita. The
             owner wants as few sidebar entries as possible (2026-10-07), so the
             separate Directory and Blotter entries of the live test (NAV-2) are
             gone again. Shown to a role holding any of Balita's tabs. --}}
        <x-ui.nav-item dense :href="route('admin.communications')" icon="megaphone" label="Balita" :active="$active === 'communications'"
            showIf="$store.session.can('communications') && ['comm_news','comm_banners','comm_community','comm_events','comm_directory','comm_barangay_directory'].some((s) => $store.session.canPerm(s, 'view'))" />
        {{-- Officers who answer residents' feedback but publish nothing (department
             and barangay staff) reach the Feedback desk here; anyone with Balita
             finds it as a tab there, so no one sees two entries (2026-10-15). --}}
        <x-ui.nav-item dense :href="route('admin.communications.feedback')" icon="messages-square" label="Feedback" :active="$active === 'communications'"
            showIf="$store.session.canPerm('comm_feedback', 'view') && !['comm_news','comm_banners','comm_community','comm_events','comm_directory','comm_barangay_directory'].some((s) => $store.session.canPerm(s, 'view'))" />
        <x-ui.nav-item dense :href="route('admin.reports')" icon="file-chart-column" label="Reports & Analytics" :active="$active === 'reports'" moduleKey="reports" />
        <x-ui.nav-item dense :href="route('admin.users')" icon="user-cog" label="User Management" :active="$active === 'users'" moduleKey="user_management" />
        <x-ui.nav-item dense :href="route('admin.internal-forms')" icon="clipboard-list" label="Internal Forms" :active="$active === 'internal-forms'" />
        {{-- Settings, with its Support Tickets and Audit Logs tabs, is in the
             profile menu at the top right: the owner wants as few sidebar
             entries as possible (2026-10-07). --}}
    </nav>

    <div class="pt-1.5 shrink-0">
        <button
            @click="collapsed = !collapsed"
            class="w-full h-11 hidden lg:flex items-center justify-center gap-2 rounded-2xl bg-white/[.06] border border-white/[.08] text-[13px] font-semibold text-sidebar-text hover:text-white hover:bg-white/[.09] shadow-[0_1px_3px_rgb(0_0_0/.15)] active:shadow-pressed-dark transition-colors"
        >
            <i data-lucide="panel-left-close" class="w-4 h-4 transition-transform duration-[160ms] ease-standard" :class="collapsed && 'rotate-180'"></i>
            <span x-show="!collapsed">Collapse</span>
        </button>
    </div>
</aside>
