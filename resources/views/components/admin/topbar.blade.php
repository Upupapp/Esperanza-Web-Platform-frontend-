@props(['title' => 'Dashboard', 'subtitle' => null])

@php
    // The top bar's search (x-ui.global-search): the pages this account holds
    // (by module), and where its results open; the rest loads on first focus.
    $searchConfig = [
        'portal' => 'admin',
        'pages' => [
            ['en' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => route('admin.dashboard')],
            ['en' => 'Constituents', 'icon' => 'users', 'href' => route('admin.constituents'), 'module' => 'constituents'],
            ['en' => 'Papeles', 'icon' => 'folder-open', 'href' => route('admin.papeles'), 'module' => 'papeles'],
            ['en' => 'Dokyu — Document Requests', 'icon' => 'file-text', 'href' => route('admin.document-requests'), 'module' => 'dokyu'],
            ['en' => 'Tulong — Assistance Requests', 'icon' => 'hand-heart', 'href' => route('admin.assistance-requests'), 'module' => 'tulong'],
            ['en' => 'Sakuna — Disaster Management', 'icon' => 'shield-alert', 'href' => route('admin.sakuna'), 'module' => 'sakuna'],
            ['en' => 'Payments', 'icon' => 'receipt', 'href' => route('admin.payments'), 'module' => 'payments'],
            ['en' => 'Balita — News, Events, Blotter', 'icon' => 'megaphone', 'href' => route('admin.communications'), 'module' => 'communications'],
            ['en' => 'Reports & Analytics', 'icon' => 'file-chart-column', 'href' => route('admin.reports'), 'module' => 'reports'],
            ['en' => 'User Management', 'icon' => 'user-cog', 'href' => route('admin.users'), 'module' => 'user_management'],
            ['en' => 'Internal Forms', 'icon' => 'clipboard-list', 'href' => route('admin.internal-forms')],
            ['en' => 'Settings', 'icon' => 'settings', 'href' => route('admin.settings')],
            ['en' => 'Direktoryo — Offices and Barangays', 'icon' => 'book-user', 'href' => route('admin.settings.directory')],
            // A tab of Settings (2026-11-18). comm_feedback is a Communications submodule, so its holders hold the module.
            ['en' => 'Feedback — Residents\' Tickets', 'icon' => 'message-square-reply', 'href' => route('admin.settings.feedback'), 'module' => 'communications'],
            ['en' => 'Audit Logs', 'icon' => 'scroll-text', 'href' => route('admin.audit-logs')],
        ],
        'routes' => [
            'dokyu' => route('admin.document-requests'),
            'tulong' => route('admin.assistance-requests'),
            'constituents' => route('admin.constituents'),
        ],
        'barangays' => config('esperanza.barangays'),
    ];
@endphp

{{-- Clay design (STYLE_GUIDE §2): no full-width bar. The title sits on the
     canvas; the clock and search are pressed wells, the bell and the user chip
     glass, the menus glass panels.
     z-30, not z-20 (manual test 2026-11-06): a page card lifted to z-20 for
     its pop-over (Sakuna Reports' date range) painted over the account and
     bell menus. Below the phone menu's overlay (z-40).
     Live updates (2026-11-15) start here, beside the bell: $store.live reads
     the API's change counters every second (resources/js/app.js). --}}
<header
    class="relative z-30 flex items-center gap-4 flex-wrap px-1 lg:px-3 pt-1 shrink-0"
    x-data="{ time: '' }"
    x-init="const tick = () => time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }); tick(); setInterval(tick, 30000); $store.adminNotifications.load(); $store.adminNotifications.poll(); $store.live.start()"
>
    <div class="flex-1 basis-44 flex items-center gap-3 min-w-0">
        <button aria-label="Open menu" @click="mobileOpen = true" class="lg:hidden w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body shrink-0">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <div class="min-w-0">
            {{-- data-page-*: a page with tabs may retitle itself (Balita's Feedback tab did, 2026-10-15, until Feedback moved to Settings, 2026-11-18). --}}
            <h1 data-page-title class="text-[22px] lg:text-[26px] font-extrabold tracking-[-.02em] leading-tight text-ink truncate">{{ $title }}</h1>
            @if($subtitle)
                {{-- Two lines rather than cut off (2026-11-17: beside the clock, search and
                     profile, most subtitles lost their end, e.g. "the bara..."). --}}
                <p data-page-subtitle class="text-[13.5px] leading-snug text-ink-muted line-clamp-2">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="ml-auto flex items-center gap-2.5">
        <div class="hidden xl:flex items-center gap-2.5 h-11 px-4 rounded-2xl surface-pressed font-mono text-[13px] text-ink-body tabular-nums whitespace-nowrap">
            <span class="w-2 h-2 rounded-full bg-gold-400 shadow-[0_0_0_3px_rgb(255_225_26/.3)]" aria-hidden="true"></span>
            <span x-text="time"></span>
        </div>

        {{-- A placeholder that fits the box (UI sweep 2026-11-18): the box leaves
             about 165px for text beside its icon and the "/" chip, and "Search
             residents, requests, barangays..." read "Search residents, reques" on
             every staff page at 1440px. A wider box would take the room the
             page subtitle needs. --}}
        <x-ui.global-search :config="$searchConfig" placeholder-en="Residents, requests…" />

        {{-- Scan ID (welfare round 2026-10-29): who a presented ID belongs to,
             and a minor's or a PWD's guardian. For officers who read the
             registry; the window and its logic load on the first press. --}}
        <button type="button" x-show="$store.session.canPerm('const_resident', 'view')" x-cloak @click="window.openIdScan()" aria-label="Scan ID" title="Scan ID: who it belongs to, and a minor's or a PWD's guardian" class="w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body hover:text-brand-600 transition-colors">
            <i data-lucide="scan-qr-code" class="w-5 h-5"></i>
        </button>

        {{-- The bell (x-notifications.bell): filters, Mark all as read, See all notifications (28 Nov 2026). --}}
        <x-notifications.bell portal="admin" />

        <div class="relative" x-data="dropdown" @click.outside="close">
            <button @click="toggle" class="h-[52px] pl-1.5 pr-1.5 lg:pr-3.5 flex items-center gap-2.5 rounded-[20px] surface-glass-sm hover:bg-white/80 transition-colors">
                <div class="w-10 h-10 rounded-full clay-blue grid place-items-center font-extrabold text-sm shrink-0" x-text="$store.session.initials()"></div>
                <span class="hidden lg:block text-left max-w-[140px]">
                    <span class="block text-sm font-bold text-ink leading-tight truncate" x-text="$store.session.account?.name ?? 'Guest Preview'"></span>
                    <span class="block text-xs text-ink-muted leading-tight truncate" x-text="$store.session.account?.role ?? 'Not signed in'"></span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-ink-muted hidden lg:block transition-transform duration-[160ms] ease-standard" :class="open && 'rotate-180'"></i>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-cloak
                class="absolute right-0 mt-2 w-64 surface-glass rounded-[22px] shadow-float border-white/90 overflow-hidden z-50 origin-top-right py-1.5"
            >
                <div class="px-4 py-3 border-b border-brand-600/10">
                    <p class="text-sm font-bold text-ink" x-text="$store.session.account?.name ?? 'Guest Preview'"></p>
                    <p class="text-xs text-ink-muted font-mono" x-text="$store.session.account?.id ?? 'No account selected'"></p>
                    <span
                        x-show="$store.session.account"
                        x-cloak
                        class="inline-flex items-center gap-1 text-[10.5px] font-semibold rounded-full px-2 py-0.5 mt-1.5"
                        :class="$store.session.account?.scope === 'Municipal' ? 'clay-gold-light' : 'bg-purple-50 text-purple-700'"
                    >
                        <i :data-lucide="$store.session.account?.scope === 'Municipal' ? 'landmark' : 'map-pin'" class="w-2.5 h-2.5"></i>
                        <span x-text="$store.session.scopePill()"></span>
                    </span>
                </div>
                <a href="#" @click.prevent="open = false; $dispatch('open-my-profile')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-body hover:bg-white/60 transition-colors">
                    <i data-lucide="user" class="w-4 h-4 text-ink-muted"></i>My Profile
                </a>
                {{-- Without Settings: Feedback for whoever answers residents' tickets (2026-11-18), Direktoryo,
                     or the Facebook Page card for anyone who can post in Balita (2026-10-29), in that order (as the page lands).
                     A Department Head's Service Catalog (their types, round 2) is not Settings: canSettings() in app.js. --}}
                <a :href="$store.session.canSettings() ? '{{ route('admin.settings') }}' : ($store.session.canPerm('comm_feedback', 'view') ? '{{ route('admin.settings.feedback') }}' : (($store.session.canPerm('comm_directory', 'view') || $store.session.canPerm('comm_barangay_directory', 'view')) ? '{{ route('admin.settings.directory') }}' : '{{ route('admin.settings') }}?tab=integrations'))" href="{{ route('admin.settings') }}"
                   x-show="$store.session.canSettings() || $store.session.canPerm('comm_feedback', 'view') || $store.session.canPerm('comm_directory', 'view') || $store.session.canPerm('comm_barangay_directory', 'view') || $store.session.canPerm('comm_news', 'create') || $store.session.canPerm('set_system', 'view')" x-cloak class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-body hover:bg-white/60 transition-colors">
                    <i data-lucide="settings" class="w-4 h-4 text-ink-muted"></i>Settings
                </a>
                <div class="border-t border-brand-600/10 my-1.5"></div>
                <a href="{{ route('logout') }}" @click="$store.session.logout()" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50/70 transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i>Sign Out
                </a>
            </div>
        </div>
    </div>
    <x-admin.id-scan />
</header>
