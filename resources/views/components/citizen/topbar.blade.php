@props(['title' => 'Dashboard', 'subtitle' => null, 'active' => null, 'public' => false])

@php
    // The Filipino translation of each page's title/subtitle is real
    // product copy (TAB 01 bilingual requirement), not a mock business
    // record, so it stays inline rather than moving behind an API call —
    // there is no "title translations" table and none is warranted.
    $filTitles = [
        'dashboard' => 'Home', 'document-requests' => 'Dokyu', 'assistance-requests' => 'Tulong',
        'announcements' => 'Balita', 'events' => 'Mga Kaganapan', 'directory' => 'Direktoryo ng Pamahalaan',
        'help' => 'Suporta', 'profile' => 'Aking Profile', 'notifications' => 'Mga Abiso', 'settings' => 'Mga Setting',
        'legal' => 'Privacy at mga Tuntunin', 'appointments' => 'Appointment',
        'emergency' => 'Sakuna at Emergency',
    ];
    $filSubtitles = [
        'dashboard' => 'Narito ang buod ng iyong account.',
        'document-requests' => 'Humiling at subaybayan ang mga dokumento ng munisipyo online.',
        'assistance-requests' => 'Magsumite at subaybayan ang mga kahilingan para sa tulong.',
        'announcements' => 'Pinakabagong balita at abiso mula sa munisipyo.',
        'events' => 'Mga paparating na kaganapan sa Esperanza.',
        'directory' => 'Hanapin ang mga opisina at contact ng pamahalaan.',
        'help' => 'Mga tanong, gabay, at suporta.',
        'profile' => 'Pamahalaan ang iyong impormasyon bilang residente.',
        'notifications' => 'Lahat ng update sa iyong account, nasa isang lugar.',
        'settings' => 'Pamahalaan ang iyong mga kagustuhan sa account.',
        'legal' => 'Ang Patakaran sa Privacy at ang mga Tuntunin at Kundisyon.',
        'services' => 'Lahat ng dokumento at programang tulong na iniaalok ng munisipyo.',
        'appointments' => 'Mag-book ng oras sa opisina at tingnan ang iyong mga appointment.',
        'emergency' => 'Mga hotline, evacuation center, babala, at ulat ng insidente.',
    ];
    $filTitle = $filTitles[$active] ?? $title;
    $filSubtitle = $filSubtitles[$active] ?? $subtitle;
@endphp

@php
    // The top bar's search (x-ui.global-search): the pages, and where its
    // results open; the rest is loaded on first focus.
    $searchConfig = [
        'portal' => 'citizen',
        'pages' => [
            ['en' => 'Dashboard', 'fil' => 'Home', 'icon' => 'layout-dashboard', 'href' => route('citizen.dashboard')],
            ['en' => 'Dokyu — Document Requests', 'fil' => 'Dokyu — Kahilingan sa Dokumento', 'icon' => 'file-text', 'href' => route('citizen.document-requests')],
            ['en' => 'Tulong — Assistance Requests', 'fil' => 'Tulong — Kahilingan sa Tulong', 'icon' => 'hand-heart', 'href' => route('citizen.assistance-requests')],
            ['en' => 'Appointments', 'fil' => 'Appointment', 'icon' => 'calendar-clock', 'href' => route('citizen.appointments')],
            ['en' => 'Balita — Announcements', 'fil' => 'Balita', 'icon' => 'megaphone', 'href' => route('citizen.announcements')],
            ['en' => 'Events', 'fil' => 'Mga Kaganapan', 'icon' => 'calendar', 'href' => route('citizen.events')],
            ['en' => 'Emergency — Hotlines & Incident Reports', 'fil' => 'Sakuna — Hotline at Ulat ng Insidente', 'icon' => 'siren', 'href' => route('citizen.emergency')],
            ['en' => 'Government Directory', 'fil' => 'Direktoryo ng Pamahalaan', 'icon' => 'building-2', 'href' => route('citizen.directory')],
            ['en' => 'Help & Support', 'fil' => 'Suporta', 'icon' => 'life-buoy', 'href' => route('citizen.faqs')],
            ['en' => 'My Profile', 'fil' => 'Aking Profile', 'icon' => 'user-round', 'href' => route('citizen.profile')],
            ['en' => 'Notifications', 'fil' => 'Mga Abiso', 'icon' => 'bell', 'href' => route('citizen.notifications')],
            ['en' => 'Settings', 'fil' => 'Mga Setting', 'icon' => 'settings', 'href' => route('citizen.settings')],
        ],
        'routes' => [
            'dokyu' => route('citizen.document-requests'),
            'tulong' => route('citizen.assistance-requests'),
            'balita' => route('citizen.announcements'),
            'events' => route('citizen.events'),
        ],
    ];
@endphp

{{-- Clay design (STYLE_GUIDE §2): no full-width bar. The title sits on the
     canvas; the clock and search are pressed wells, the buttons and the user
     chip glass, the menus glass panels. --}}
<header
    class="relative z-20 flex items-center gap-4 flex-wrap px-1 lg:px-3 pt-1 shrink-0"
    x-data="{
        time: '',
        // A public page read without a session (the legal pages): no bell,
        // no My IDs, no search of the resident's requests - each of those
        // calls answers 401 for a visitor - and a Sign In link instead.
        guest: {{ $public ? '!window.EsperanzaApi.getToken()' : 'false' }},
    }"
    x-init="const tick = () => time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }); tick(); setInterval(tick, 30000); if (!guest) { $store.citizenNotifications.load(); $store.citizenNotifications.poll() }"
>
    <div class="flex-1 basis-44 flex items-center gap-3 min-w-0">
        <button aria-label="Open menu" @click="mobileOpen = true" class="lg:hidden w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body shrink-0">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <div class="min-w-0">
            <h1 class="text-[22px] lg:text-[26px] font-extrabold tracking-[-.02em] leading-tight text-ink truncate" x-text="$store.lang.current === 'en' ? @js($title) : @js($filTitle)">{{ $filTitle }}</h1>
            @if($subtitle)
                <p class="text-[13.5px] text-ink-muted truncate" x-text="$store.lang.current === 'en' ? @js($subtitle) : @js($filSubtitle)">{{ $filSubtitle }}</p>
            @endif
        </div>

    </div>

    <a x-show="guest" x-cloak href="{{ route('citizen.login') }}" class="ml-auto inline-flex items-center gap-2 min-h-[44px] px-4 py-2.5 rounded-2xl text-sm font-semibold clay-blue btn-shine hover:brightness-95">
        <i data-lucide="log-in" class="w-4 h-4"></i><x-ui.t fil="Mag-sign In" en="Sign In" />
    </a>

    <template x-if="!guest">
    <div class="ml-auto flex items-center gap-2.5">
        <div class="hidden xl:flex items-center gap-2.5 h-11 px-4 rounded-2xl surface-pressed font-mono text-[13px] text-ink-body tabular-nums whitespace-nowrap">
            <span class="w-2 h-2 rounded-full bg-gold-400 shadow-[0_0_0_3px_rgb(255_225_26/.3)]" aria-hidden="true"></span>
            <span x-text="time"></span>
        </div>

        {{-- My IDs — the identity/verification documents a citizen has on
             file, read from their own Papeles (production-readiness
             programme, 2026-09-25: this used to be a 3-item fixture with
             fabricated masked ID numbers). Papeles does not carry a masked
             document number, so this panel shows type + status only. --}}
        <div
            class="relative"
            x-data="{
                open: false,
                toggle() { this.open = !this.open; },
                close() { this.open = false; },
                idFilter: 'all',
                myIds: [],
                // The ID sent to verify the account (Profile, Verify ID), then
                // any identity documents kept in Papeles. Papeles alone read
                // 0 IDs right after a resident sent theirs.
                async init() {
                    const [verification, papeles] = await Promise.all([
                        $api.get('/citizen/verification/id').catch(() => null),
                        $api.get('/citizen/papeles').catch(() => null),
                    ]);
                    const labels = {
                        'Approved': ['Verified', 'Verified'],
                        'Pending Review': ['Under review', 'Sinusuri'],
                        'Rejected': ['Not accepted', 'Hindi tinanggap'],
                    };
                    const entry = (type, status) => {
                        const [en, fil] = labels[status] || ['Unverified', 'Unverified'];
                        return { type, status: status === 'Approved' ? 'Verified' : 'Unverified', labelEn: en, labelFil: fil };
                    };
                    const list = [];
                    const sent = verification?.data?.submission;
                    if (sent) list.push(entry(sent.id_type, sent.status));
                    (papeles?.data || [])
                        .filter((d) => d.category === 'Identity / Verification')
                        .forEach((d) => { if (!list.some((x) => x.type === d.type)) list.push(entry(d.type, d.status)); });
                    this.myIds = list;
                    this.$nextTick(() => window.renderIcons?.());
                },
                get filteredIds() { return this.idFilter === 'all' ? this.myIds : this.myIds.filter(d => d.status === this.idFilter); },
            }"
            @click.outside="close"
        >
            <button @click="toggle" class="relative w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body hover:text-brand-600 transition-colors" :title="$store.lang.current === 'en' ? 'My IDs' : 'Aking mga ID'">
                <i data-lucide="id-card" class="w-5 h-5"></i>
                <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-gold-400 ring-2 ring-white" x-show="myIds.some((d) => d.status !== 'Verified')"></span>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-cloak
                class="absolute right-0 mt-2 w-80 surface-glass rounded-[22px] shadow-float border-white/90 overflow-hidden z-50 origin-top-right"
            >
                <div class="flex items-center justify-between px-4 py-3 border-b border-brand-600/10">
                    <p class="text-sm font-bold text-ink"><x-ui.t fil="Aking mga ID" en="My IDs" /></p>
                    <span class="text-xs font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full" x-text="filteredIds.length"></span>
                </div>
                <div class="flex items-center gap-1 px-4 py-2 border-b border-brand-600/10 bg-well/50">
                    <button type="button" @click="idFilter = 'all'" class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors" :class="idFilter === 'all' ? 'bg-white text-navy-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                        <x-ui.t fil="Lahat" en="All" />
                    </button>
                    <button type="button" @click="idFilter = 'Verified'" class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors" :class="idFilter === 'Verified' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-200' : 'text-slate-500 hover:text-slate-700'">
                        <x-ui.t fil="Verified" en="Verified" />
                    </button>
                    <button type="button" @click="idFilter = 'Unverified'" class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors" :class="idFilter === 'Unverified' ? 'bg-white text-amber-700 shadow-sm ring-1 ring-amber-200' : 'text-slate-500 hover:text-slate-700'">
                        <x-ui.t fil="Unverified" en="Unverified" />
                    </button>
                </div>
                <div class="max-h-80 overflow-y-auto scrollbar-thin divide-y divide-brand-600/10">
                    <template x-for="idDoc in filteredIds" :key="idDoc.type">
                        <div class="flex items-center gap-3 px-4 py-3">
                            <span class="w-9 h-9 rounded-xl clay-light flex items-center justify-center shrink-0"><i data-lucide="id-card" class="w-4 h-4"></i></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink truncate" x-text="idDoc.type"></p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 min-h-[26px] px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap shrink-0"
                                :class="idDoc.status === 'Verified' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="idDoc.status === 'Verified' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                <span x-text="$store.lang.current === 'en' ? idDoc.labelEn : idDoc.labelFil"></span>
                            </span>
                        </div>
                    </template>
                    <div class="flex flex-col items-center text-center py-8" x-show="filteredIds.length === 0">
                        <span class="w-11 h-11 rounded-2xl surface-pressed text-ink-muted flex items-center justify-center mb-2"><i data-lucide="id-card" class="w-5 h-5"></i></span>
                        <p class="text-xs text-slate-500"><x-ui.t fil="Walang ID sa filter na ito." en="No IDs match this filter." /></p>
                    </div>
                </div>
                <a href="{{ route('citizen.profile') }}" class="block text-center text-sm font-semibold text-brand-600 py-3 border-t border-brand-600/10 hover:bg-white/60 transition-colors">
                    <x-ui.t fil="Pamahalaan ang mga ID sa Profile" en="Manage IDs in Profile" />
                </a>
            </div>
        </div>

        {{-- mobile: a search button at phone width opens it full width (scope round 2026-10-15). --}}
        <x-ui.global-search :config="$searchConfig" placeholder-en="Search dokyu, tulong, balita..." placeholder-fil="Maghanap ng dokyu, tulong, balita..." width="w-56 2xl:w-[360px]" mobile />

        {{-- Real, request- and account-driven notifications from
             $store.citizenNotifications (production-readiness programme,
             2026-09-25). Loaded once on the shell's own init below, so this
             dropdown and the dedicated Notifications page share one fetch. --}}
        <div class="relative" x-data="dropdown" @click.outside="close">
            {{-- How many are unread, not just whether (Esperanza QA, 2026-10-07);
                 nothing at all when every notification is read. --}}
            <button @click="toggle" aria-label="Notifications" :aria-label="$store.citizenNotifications.unreadCount ? 'Notifications, ' + $store.citizenNotifications.unreadCount + ' unread' : 'Notifications'" class="relative w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body hover:text-brand-600 transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 inline-flex items-center justify-center rounded-full clay-gold ring-2 ring-white text-[10.5px] font-extrabold leading-none"
                      x-show="$store.citizenNotifications.unreadCount > 0"
                      x-text="$store.citizenNotifications.unreadCount > 99 ? '99+' : $store.citizenNotifications.unreadCount"></span>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-cloak
                class="absolute right-0 mt-2 w-[22rem] max-sm:fixed max-sm:inset-x-3 max-sm:top-16 max-sm:right-3 max-sm:mt-0 max-sm:w-auto surface-glass rounded-[22px] shadow-float border-white/90 overflow-hidden z-50 origin-top-right"
            >
                <div class="flex items-center justify-between px-4 py-3 border-b border-brand-600/10">
                    <p class="text-sm font-bold text-ink" x-text="$store.lang.current === 'en' ? 'Notifications' : 'Mga Abiso'"></p>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full clay-gold-light" x-show="$store.citizenNotifications.unreadCount" x-text="$store.citizenNotifications.unreadCount + ($store.lang.current === 'en' ? ' new' : ' bago')"></span>
                </div>
                <div class="max-h-80 overflow-y-auto scrollbar-thin divide-y divide-brand-600/10">
                    <template x-for="n in $store.citizenNotifications.visible.slice(0, 8)" :key="n.id">
                        <div class="group relative flex items-start gap-3 px-4 py-3 hover:bg-white/60 transition-colors">
                            <a
                                :href="n.link || '{{ route('citizen.notifications') }}'"
                                @click="$store.citizenNotifications.markRead(n.id); close()"
                                class="flex items-start gap-3 flex-1 min-w-0"
                            >
                                <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="{ brand: 'text-brand-600 bg-brand-50', purple: 'text-purple-600 bg-purple-50', indigo: 'text-indigo-600 bg-indigo-50', teal: 'text-teal-600 bg-teal-50', amber: 'text-amber-600 bg-amber-50', rose: 'text-rose-600 bg-rose-50' }[n.color] || 'text-brand-600 bg-brand-50'">
                                    <i :data-lucide="n.icon" class="w-4 h-4"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-ink truncate" x-text="n.title"></span>
                                    <span class="block text-xs text-ink-muted mt-0.5 line-clamp-2" x-text="n.body"></span>
                                </span>
                            </a>
                            <button aria-label="Close"
                                type="button" x-show="!n.pinned"
                                @click.prevent.stop="$store.citizenNotifications.dismiss(n.id)"
                                class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 transition-opacity shrink-0 mt-0.5 focus-within:opacity-100 focus:opacity-100"
                            ><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </template>
                    <p class="px-4 py-8 text-center text-sm text-slate-500" x-show="!$store.citizenNotifications.loading && !$store.citizenNotifications.visible.length">
                        <x-ui.t fil="Wala pang abiso." en="No notifications yet." />
                    </p>
                </div>
                <a href="{{ route('citizen.notifications') }}" class="block text-center text-sm font-semibold text-brand-600 py-3 border-t border-brand-600/10 hover:bg-white/60 transition-colors">
                    <x-ui.t fil="Tingnan lahat ng abiso" en="View all notifications" />
                </a>
            </div>
        </div>

        <div class="relative" x-data="dropdown" @click.outside="close">
            <button @click="toggle" class="h-[52px] pl-1.5 pr-1.5 lg:pr-3.5 flex items-center gap-2.5 rounded-[20px] surface-glass-sm hover:bg-white/80 transition-colors">
                <x-citizen.avatar class="w-10 h-10 text-sm" />
                <span class="hidden lg:block text-left max-w-[140px]">
                    <span class="block text-sm font-bold text-ink leading-tight truncate" x-text="$store.citizenSession.account?.fullName ?? 'Guest Citizen'"></span>
                    <span class="block text-xs text-ink-muted leading-tight truncate" x-text="$store.citizenSession.account ? (($store.lang.current === 'en' ? 'Resident — Brgy. ' : 'Residente — Brgy. ') + $store.citizenSession.account.barangay) : ($store.lang.current === 'en' ? 'Not signed in' : 'Hindi naka-sign in')"></span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-ink-muted hidden lg:block transition-transform duration-[160ms] ease-standard" :class="open && 'rotate-180'"></i>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-cloak
                class="absolute right-0 mt-2 w-60 surface-glass rounded-[22px] shadow-float border-white/90 overflow-hidden z-50 origin-top-right py-1.5"
            >
                <div class="flex items-center gap-3 px-4 py-3 border-b border-brand-600/10">
                    <x-citizen.avatar class="w-11 h-11 text-sm" />
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-ink truncate" x-text="$store.citizenSession.account?.fullName ?? 'Guest Citizen'"></p>
                        <p class="text-xs text-slate-500 truncate" x-text="$store.citizenSession.account?.email ?? 'No account selected'"></p>
                    </div>
                </div>
                <a href="{{ route('citizen.profile') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-body hover:bg-white/60 transition-colors">
                    <i data-lucide="user" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Aking Profile" en="My Profile" />
                </a>
                <a href="{{ route('citizen.settings') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-body hover:bg-white/60 transition-colors">
                    <i data-lucide="settings" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Mga Setting" en="Settings" />
                </a>
                <div class="border-t border-brand-600/10 my-1.5"></div>
                <a href="{{ route('logout') }}" @click="$store.citizenSession.logout()" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50/70 transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i><x-ui.t fil="Mag-sign Out" en="Sign Out" />
                </a>
            </div>
        </div>
    </div>
    </template>
</header>
