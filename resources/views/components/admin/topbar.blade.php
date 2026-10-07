@props(['title' => 'Dashboard', 'subtitle' => null])

{{-- Clay design (STYLE_GUIDE §2): no full-width bar. The title sits on the
     canvas; the clock and search are pressed wells, the bell and the user chip
     glass, the menus glass panels. --}}
<header
    class="relative z-20 flex items-center gap-4 flex-wrap px-1 lg:px-3 pt-1 shrink-0"
    x-data="{ time: '' }"
    x-init="setInterval(() => time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000); $store.adminNotifications.load(); $store.adminNotifications.poll()"
>
    <div class="flex-1 basis-44 flex items-center gap-3 min-w-0">
        <button aria-label="Open menu" @click="mobileOpen = true" class="lg:hidden w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body shrink-0">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <div class="min-w-0">
            <h1 class="text-[22px] lg:text-[26px] font-extrabold tracking-[-.02em] leading-tight text-ink truncate">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-[13.5px] text-ink-muted truncate">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="ml-auto flex items-center gap-2.5">
        <div class="hidden xl:flex items-center gap-2.5 h-11 px-4 rounded-2xl surface-pressed font-mono text-[13px] text-ink-body tabular-nums whitespace-nowrap">
            <span class="w-2 h-2 rounded-full bg-gold-400 shadow-[0_0_0_3px_rgb(255_225_26/.3)]" aria-hidden="true"></span>
            <span x-text="time"></span>
        </div>

        <label class="hidden md:flex items-center gap-2.5 h-11 w-56 lg:w-64 2xl:w-[360px] px-4 rounded-2xl surface-pressed transition-all duration-[160ms] ease-standard focus-within:bg-white/90 focus-within:shadow-[var(--shadow-pressed-sm),0_0_0_4px_var(--color-brand-100)]">
            <i data-lucide="search" class="w-[18px] h-[18px] text-ink-muted shrink-0 pointer-events-none"></i>
            <input aria-label="Search residents, requests, barangays"
                type="text"
                placeholder="Search residents, requests, barangays..."
                class="w-full min-w-0 bg-transparent border-0 p-0 text-sm text-ink placeholder:text-ink-faint focus:ring-0 focus:outline-none"
            >
        </label>

        <div class="relative" x-data="dropdown" @click.outside="close">
            <button @click="toggle" aria-label="Notifications" class="relative w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body hover:text-brand-600 transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-gold-400 ring-2 ring-white" x-show="$store.adminNotifications.unreadCount"></span>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-cloak
                class="absolute right-0 mt-2 w-[22rem] surface-glass rounded-[22px] shadow-float border-white/90 overflow-hidden z-50 origin-top-right"
            >
                <div class="flex items-center justify-between px-4 py-3 border-b border-brand-600/10">
                    <p class="text-sm font-bold text-ink">Notifications</p>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full clay-gold-light" x-show="$store.adminNotifications.unreadCount" x-text="$store.adminNotifications.unreadCount + ' new'"></span>
                </div>
                <div class="max-h-80 overflow-y-auto scrollbar-thin divide-y divide-brand-600/10">
                    <template x-for="n in $store.adminNotifications.visible" :key="n.id">
                        <a
                            :href="n.link || ((n.portal === 'tulong' ? '{{ route('admin.assistance-requests') }}' : '{{ route('admin.document-requests') }}') + '?ref=' + encodeURIComponent(n.ref || ''))"
                            @click="$store.adminNotifications.markRead(n.id)"
                            class="flex items-start gap-3 px-4 py-3 hover:bg-white/60 transition-colors"
                            :class="n.unread && 'bg-brand-50/50'"
                        >
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="{ brand: 'text-brand-600 bg-brand-50', sky: 'text-sky-600 bg-sky-50', amber: 'text-amber-600 bg-amber-50', rose: 'text-rose-600 bg-rose-50', emerald: 'text-emerald-600 bg-emerald-50', indigo: 'text-indigo-600 bg-indigo-50', purple: 'text-purple-600 bg-purple-50', teal: 'text-teal-600 bg-teal-50' }[n.color] || 'text-brand-600 bg-brand-50'">
                                <i :data-lucide="n.icon" class="w-4 h-4"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-ink truncate" x-text="n.title"></span>
                                <span class="block text-xs text-ink-muted mt-0.5 line-clamp-2" x-text="n.body"></span>
                                <span class="block text-[11px] text-ink-faint mt-1" x-text="n.createdAt ? (new Date(n.createdAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' · ' + new Date(n.createdAt).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })) : ''"></span>
                            </span>
                        </a>
                    </template>
                    <p class="px-4 py-8 text-center text-sm text-ink-muted" x-show="$store.adminNotifications.loading">Loading…</p>
                    <p class="px-4 py-8 text-center text-sm text-ink-muted" x-show="!$store.adminNotifications.loading && !$store.adminNotifications.visible.length">No notifications yet.</p>
                </div>
                <button type="button" @click="$store.adminNotifications.markAllRead()" x-show="$store.adminNotifications.unreadCount" class="block w-full text-center text-sm font-semibold text-brand-600 py-3 border-t border-brand-600/10 hover:bg-white/60 transition-colors">
                    Mark all as read
                </button>
            </div>
        </div>

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
                        <span x-text="$store.session.account?.scope === 'Municipal' ? 'Municipality-wide' : 'Brgy. ' + $store.session.account?.scope"></span>
                    </span>
                </div>
                <a href="#" @click.prevent="open = false; $dispatch('open-my-profile')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-body hover:bg-white/60 transition-colors">
                    <i data-lucide="user" class="w-4 h-4 text-ink-muted"></i>My Profile
                </a>
                <a href="{{ route('admin.settings') }}" x-show="$store.session.can('settings')" x-cloak class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-body hover:bg-white/60 transition-colors">
                    <i data-lucide="settings" class="w-4 h-4 text-ink-muted"></i>Settings
                </a>
                <div class="border-t border-brand-600/10 my-1.5"></div>
                <a href="{{ route('logout') }}" @click="$store.session.logout()" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50/70 transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i>Sign Out
                </a>
            </div>
        </div>
    </div>
</header>
