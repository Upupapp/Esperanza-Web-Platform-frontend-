{{-- The bell and its pop-up, both portals (redesign 28 Nov 2026, after
     eBPCO's). At the top: the title, Mark all as read and a close button; one
     compact filter row: All / Unread with their counts, and the kinds this
     person has (store.categories); the newest notifications (rows:
     x-notifications.row); "See all notifications" opens the Notifications
     page. It closes on a click outside, on Esc, and when a row's link is
     opened. The list and its numbers come from the store (resources/js/
     notifications.js), loaded once by the top bar and kept live there.
     Staff: English only, the panel hangs from the header's right edge below
     sm (sweep 2026-11-18: the profile chip sits right of the bell, and a
     22rem panel ended past a 390px phone's left edge). Residents: both
     languages; below sm the panel is fixed under the top bar. --}}
@props(['portal' => 'admin'])
@php
    $citizen = $portal === 'citizen';
    $S = $citizen ? '$store.citizenNotifications' : '$store.adminNotifications';
    // A phrase in the portal's language: the resident site's in both (as x-ui.t), the staff site's in English.
    $t = fn (string $en, string $fil) => $citizen
        ? new \Illuminate\Support\HtmlString('<span x-text="$store.lang.current === \'en\' ? '.\Illuminate\Support\Js::from($en).' : '.\Illuminate\Support\Js::from($fil).'">'.e($fil).'</span>')
        : $en;
    $page = $citizen ? route('citizen.notifications') : route('admin.notifications');
@endphp
<div class="{{ $citizen ? 'relative' : 'sm:relative' }}" x-data="dropdown" @click.outside="close" @keydown.escape.window="open && close()">
    {{-- How many are unread, not just whether (Esperanza QA, 2026-10-07); nothing at all when every notification is read. --}}
    <button @click="toggle" aria-label="Notifications" :aria-expanded="open" @if($citizen) :aria-label="{{ $S }}.unreadCount ? 'Notifications, ' + {{ $S }}.unreadCount + ' unread' : 'Notifications'" @endif class="relative w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body hover:text-brand-600 transition-colors">
        <i data-lucide="bell" class="w-5 h-5"></i>
        @if($citizen)
            <span class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 inline-flex items-center justify-center rounded-full clay-gold ring-2 ring-white text-[10.5px] font-extrabold leading-none"
                  x-show="$store.citizenNotifications.unreadCount > 0"
                  x-text="$store.citizenNotifications.unreadCount > 99 ? '99+' : $store.citizenNotifications.unreadCount"></span>
        @else
            <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-gold-400 ring-2 ring-white" x-show="$store.adminNotifications.unreadCount"></span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-cloak
        class="{{ $citizen ? 'absolute right-0 mt-2 w-[24rem] max-sm:fixed max-sm:inset-x-3 max-sm:top-20 max-sm:right-3 max-sm:mt-0 max-sm:w-auto' : 'absolute right-0 mt-2 w-[min(24rem,calc(100vw-2rem))]' }} surface-glass rounded-[22px] shadow-float border-white/90 overflow-hidden z-50 origin-top-right"
    >
        <div class="flex items-center gap-2 pl-4 pr-2 pt-2.5 pb-2">
            <p class="flex-1 text-sm font-bold text-ink">{{ $t('Notifications', 'Mga Abiso') }}</p>
            <button type="button" x-show="{{ $S }}.unreadCount" @click="{{ $S }}.markAllRead()" class="inline-flex items-center gap-1.5 min-h-[36px] px-2.5 rounded-xl text-xs font-semibold text-brand-600 hover:bg-white/70 transition-colors">
                <i data-lucide="check-check" class="w-3.5 h-3.5"></i>{{ $t('Mark all as read', 'Markahan lahat na nabasa') }}
            </button>
            <button type="button" @click="close()" @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Close' : 'Isara'" @else aria-label="Close" @endif class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-white/70 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- One row of filters: All / Unread, and the kinds. --}}
        <div class="flex items-center gap-2 px-3 pb-2.5 border-b border-brand-600/10">
            <div class="flex items-center gap-1 surface-pressed rounded-xl p-1 shrink-0" role="group" @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Show' : 'Ipakita'" @else aria-label="Show" @endif>
                <button type="button" @click="{{ $S }}.setBell('all')" :aria-pressed="{{ $S }}.bellTab === 'all'" class="px-2.5 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="{{ $S }}.bellTab === 'all' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">
                    {{ $t('All', 'Lahat') }} <span class="tabular-nums text-ink-faint" x-text="{{ $S }}.allCount"></span>
                </button>
                <button type="button" @click="{{ $S }}.setBell('unread')" :aria-pressed="{{ $S }}.bellTab === 'unread'" class="px-2.5 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="{{ $S }}.bellTab === 'unread' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">
                    {{ $t('Unread', 'Hindi pa nababasa') }} <span class="tabular-nums" :class="{{ $S }}.unreadCount ? 'text-brand-600 font-semibold' : 'text-ink-faint'" x-text="{{ $S }}.unreadCount"></span>
                </button>
            </div>
            <div class="relative min-w-0 flex-1">
                <select x-model="{{ $S }}.bellCategory" @change="{{ $S }}.setBell({{ $S }}.bellTab, $event.target.value)"
                        @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Category' : 'Kategorya'" @else aria-label="Category" @endif
                        class="w-full appearance-none text-[11px] font-medium text-ink-body rounded-xl py-2 pl-2.5 pr-7 border-0 surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-brand-100 truncate">
                    <option value="" @if($citizen) x-text="$store.lang.current === 'en' ? 'All categories' : 'Lahat ng kategorya'" @endif>{{ $citizen ? 'Lahat ng kategorya' : 'All categories' }}</option>
                    <template x-for="k in {{ $S }}.categories" :key="k.key">
                        <option :value="k.key" :selected="k.key === {{ $S }}.bellCategory" x-text="{{ $S }}.kindName(k.key) + ' (' + ({{ $S }}.bellTab === 'unread' ? k.unread : k.total) + ')'"></option>
                    </template>
                </select>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-ink-muted absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
        </div>

        <div class="max-h-[min(26rem,60vh)] overflow-y-auto scrollbar-thin divide-y divide-brand-600/10">
            <template x-for="n in {{ $S }}.bellItems.slice(0, 15)" :key="n.id">
                <x-notifications.row :portal="$portal" dense />
            </template>
            <p class="px-4 py-8 text-center text-sm text-ink-muted" x-show="{{ $S }}.bellLoading && !{{ $S }}.bellItems.length">{{ $t('Loading…', 'Naglo-load…') }}</p>
            <div class="flex flex-col items-center text-center px-6 py-8" x-show="!{{ $S }}.bellLoading && !{{ $S }}.bellItems.length">
                <span class="w-11 h-11 rounded-2xl surface-pressed text-ink-muted grid place-items-center mb-2"><i data-lucide="bell-off" class="w-5 h-5"></i></span>
                <p class="text-xs text-ink-muted" x-show="{{ $S }}.bellTab === 'unread' && !{{ $S }}.bellCategory">{{ $t('You\'re all caught up.', 'Nabasa mo na lahat.') }}</p>
                <p class="text-xs text-ink-muted" x-show="{{ $S }}.bellCategory">{{ $t('No notifications of this kind.', 'Walang abiso sa kategoryang ito.') }}</p>
                <p class="text-xs text-ink-muted" x-show="!{{ $S }}.bellFiltered">{{ $t('No notifications yet.', 'Wala pang abiso.') }}</p>
            </div>
        </div>
        <a href="{{ $page }}" class="block text-center text-sm font-semibold text-brand-600 py-3 border-t border-brand-600/10 hover:bg-white/60 transition-colors">
            {{ $t('See all notifications', 'Tingnan lahat ng abiso') }}
        </a>
    </div>
</div>
