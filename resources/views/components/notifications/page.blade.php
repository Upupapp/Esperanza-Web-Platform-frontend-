{{-- The Notifications page, both portals (redesign 28 Nov 2026, after
     eBPCO's). A breadcrumb back to the Dashboard (the staff site has no
     sidebar entry for this page: it opens from the bell's "See all
     notifications"); All / Unread tabs with counts and Mark all as read; the
     filters: Search, Category (the kinds this person has, with counts) and
     When (Any time, Today, Last 7 days, Last 30 days, On a date… with a date
     picker); Clear filters once one is set; the list (x-notifications.row);
     a plain empty state with a way back; Load more while the server has more.
     Component notificationsPage in resources/js/notifications-page.js. On
     the resident site in both languages. --}}
@props(['portal' => 'admin'])
@php
    $citizen = $portal === 'citizen';
    $store = $citizen ? 'citizenNotifications' : 'adminNotifications';
    $S = '$store.'.$store;
    // A phrase in the portal's language: the resident site's in both (as x-ui.t), the staff site's in English.
    $t = fn (string $en, string $fil) => $citizen
        ? new \Illuminate\Support\HtmlString('<span x-text="$store.lang.current === \'en\' ? '.\Illuminate\Support\Js::from($en).' : '.\Illuminate\Support\Js::from($fil).'">'.e($fil).'</span>')
        : $en;
    $home = $citizen ? route('citizen.dashboard') : route('admin.dashboard');
    $tab = 'px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap inline-flex items-center gap-1.5';
    $field = 'text-xs rounded-2xl py-2 border-0 surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-brand-100 transition-all duration-[160ms] ease-standard';
@endphp
<div x-data="notificationsPage('{{ $store }}')" class="animate-fade-up">
    <nav @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Breadcrumb' : 'Kinalalagyan'" @else aria-label="Breadcrumb" @endif class="mb-3 px-1">
        <ol class="flex items-center gap-1.5 text-[13px]">
            <li><a href="{{ $home }}" class="font-semibold text-ink-muted hover:text-brand-600 transition-colors">{{ $t('Dashboard', 'Home') }}</a></li>
            <li class="text-ink-faint" aria-hidden="true">/</li>
            <li class="font-semibold text-ink" aria-current="page">{{ $t('Notifications', 'Mga Abiso') }}</li>
        </ol>
    </nav>

    <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1 surface-pressed rounded-xl p-1 w-fit max-w-full" role="tablist">
                <button type="button" role="tab" :aria-selected="tab === 'all'" @click="setTab('all')" class="{{ $tab }}" :class="tab === 'all' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">
                    {{ $t('All', 'Lahat') }}<span class="tabular-nums text-ink-faint" x-text="{{ $S }}.allCount"></span>
                </button>
                <button type="button" role="tab" :aria-selected="tab === 'unread'" @click="setTab('unread')" class="{{ $tab }}" :class="tab === 'unread' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">
                    {{ $t('Unread', 'Hindi pa nababasa') }}<span class="text-[10px] font-semibold rounded-full px-1.5 py-0.5 tabular-nums" :class="{{ $S }}.unreadCount ? 'bg-brand-100 text-brand-700' : 'text-ink-faint'" x-text="{{ $S }}.unreadCount"></span>
                </button>
            </div>
            <button type="button" x-show="{{ $S }}.unreadCount" @click="markAllRead()" :disabled="markingAll"
                    class="inline-flex items-center gap-1.5 min-h-[36px] px-3 rounded-xl text-xs font-semibold text-brand-600 hover:bg-white/70 disabled:opacity-50 transition-colors">
                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="markingAll" x-cloak></i>
                <i data-lucide="check-check" class="w-3.5 h-3.5" x-show="!markingAll"></i>{{ $t('Mark all as read', 'Markahan lahat na nabasa') }}
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" x-model.debounce.350ms="q" maxlength="120"
                       @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Search notifications' : 'Maghanap sa mga abiso'" :placeholder="$store.lang.current === 'en' ? 'Search notifications' : 'Maghanap sa mga abiso'" @else aria-label="Search notifications" placeholder="Search notifications" @endif
                       class="w-full pl-9 pr-3 {{ $field }}">
            </div>
            <div class="relative w-full sm:w-auto">
                <select x-model="category" @change="apply()" @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Category' : 'Uri'" @else aria-label="Category" @endif
                        class="w-full sm:w-56 appearance-none pl-3 pr-8 text-ink-body {{ $field }}">
                    <option value="" @if($citizen) x-text="$store.lang.current === 'en' ? 'All categories' : 'Lahat ng uri'" @endif>{{ $citizen ? 'Lahat ng uri' : 'All categories' }}</option>
                    <template x-for="k in {{ $S }}.categories" :key="k.key">
                        <option :value="k.key" :selected="k.key === category" x-text="kindLabel(k)"></option>
                    </template>
                </select>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-ink-muted absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
            <div class="relative w-full sm:w-auto">
                <select :value="when" @change="setWhen($event.target.value)" @if($citizen) :aria-label="$store.lang.current === 'en' ? 'When' : 'Kailan'" @else aria-label="When" @endif
                        class="w-full sm:w-44 appearance-none pl-3 pr-8 text-ink-body {{ $field }}">
                    <option value="any" @if($citizen) x-text="$store.lang.current === 'en' ? 'Any time' : 'Kahit kailan'" @endif>{{ $citizen ? 'Kahit kailan' : 'Any time' }}</option>
                    <option value="today" @if($citizen) x-text="$store.lang.current === 'en' ? 'Today' : 'Ngayong araw'" @endif>{{ $citizen ? 'Ngayong araw' : 'Today' }}</option>
                    <option value="7d" @if($citizen) x-text="$store.lang.current === 'en' ? 'Last 7 days' : 'Nakaraang 7 araw'" @endif>{{ $citizen ? 'Nakaraang 7 araw' : 'Last 7 days' }}</option>
                    <option value="30d" @if($citizen) x-text="$store.lang.current === 'en' ? 'Last 30 days' : 'Nakaraang 30 araw'" @endif>{{ $citizen ? 'Nakaraang 30 araw' : 'Last 30 days' }}</option>
                    <option value="on" @if($citizen) x-text="$store.lang.current === 'en' ? 'On a date…' : 'Pumili ng petsa…'" @endif>{{ $citizen ? 'Pumili ng petsa…' : 'On a date…' }}</option>
                </select>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-ink-muted absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
            <input type="date" x-ref="date" x-show="when === 'on'" x-cloak x-model="date" @change="apply()" :max="today"
                   @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Date' : 'Petsa'" @else aria-label="Date" @endif
                   class="w-full sm:w-auto px-3 text-ink-body {{ $field }}">
            <button type="button" x-show="filtered" x-cloak @click="clearFilters()" class="inline-flex items-center gap-1.5 min-h-[36px] px-3 rounded-xl text-xs font-semibold text-ink-muted hover:text-ink hover:bg-white/70 transition-colors">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>{{ $t('Clear filters', 'I-clear ang filters') }}
            </button>
        </div>
    </div>

    <div x-show="loading && !rows.length" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-16 opacity-80" /><x-ui.skeleton class="h-16 opacity-60" /></div>
    <div x-show="failed && !rows.length" x-cloak>
        <x-ui.error-state :title="$citizen ? 'May nangyaring mali' : 'Something went wrong'" :title-en="$citizen ? 'Something went wrong' : null"
                          :message-expr="$citizen ? '$store.lang.current === \'en\' ? \'Could not load notifications.\' : \'Hindi mai-load ang mga abiso.\'' : '\'Could not load notifications.\''" retry-expr="feed.load()" :retryable="true" />
    </div>

    <div x-show="rows.length" x-cloak>
        <x-ui.card :padded="false">
            <div class="divide-y divide-brand-600/[.08] overflow-hidden rounded-[26px]">
                <template x-for="n in rows" :key="n.id">
                    <x-notifications.row :portal="$portal" />
                </template>
            </div>
        </x-ui.card>
    </div>

    {{-- Nothing to show: say why, and offer the way back. --}}
    <div x-show="empty" x-cloak class="surface-glass rounded-[26px]">
        <div class="flex flex-col items-center justify-center text-center py-14 px-6">
            <div class="w-16 h-16 rounded-[22px] surface-pressed flex items-center justify-center mb-4" aria-hidden="true">
                <i data-lucide="search-x" class="w-7 h-7 text-ink-muted" x-show="filtered"></i>
                <i data-lucide="bell-off" class="w-7 h-7 text-ink-muted" x-show="!filtered"></i>
            </div>
            <h3 class="text-sm font-bold text-ink" x-show="filtered">{{ $t('No notifications match these filters.', 'Walang abisong tugma sa mga filter na ito.') }}</h3>
            <h3 class="text-sm font-bold text-ink" x-show="!filtered && tab === 'unread'">{{ $t('You\'re all caught up.', 'Nabasa mo na lahat.') }}</h3>
            <h3 class="text-sm font-bold text-ink" x-show="!filtered && tab === 'all'">{{ $t('No notifications yet.', 'Wala pang abiso.') }}</h3>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <x-ui.button variant="secondary" size="sm" icon="x" x-show="filtered || tab === 'unread'" @click="showAll()">{{ $t('Show all notifications', 'Ipakita lahat ng abiso') }}</x-ui.button>
                <x-ui.button variant="ghost" size="sm" icon="arrow-left" :href="$home">{{ $t('Back to Dashboard', 'Bumalik sa Home') }}</x-ui.button>
            </div>
        </div>
    </div>

    {{-- Older notifications, a page at a time, while the server has more
         (meta.last_page). Only the newest 25 were ever reachable before
         (parity check, 2026-10-26). --}}
    <div class="mt-4 flex flex-col items-center gap-2" x-show="feed && feed.hasMore" x-cloak data-notifications-load-more>
        <button type="button" @click="feed.loadMore()" x-bind:disabled="feed && feed.loadingMore"
                class="inline-flex items-center gap-2 rounded-xl surface-glass px-4 py-2 text-xs font-semibold text-brand-700 hover:text-brand-800 disabled:opacity-60 transition-colors">
            <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="feed && feed.loadingMore" x-cloak></i>
            <i data-lucide="chevrons-down" class="w-3.5 h-3.5" x-show="!(feed && feed.loadingMore)"></i>
            <span x-show="!(feed && feed.loadingMore)">{{ $t('Load more', 'Mag-load pa') }}</span>
            <span x-show="feed && feed.loadingMore" x-cloak>{{ $t('Loading…', 'Naglo-load…') }}</span>
        </button>
        <p class="text-[11px] text-rose-600" x-show="feed && feed.moreFailed" x-cloak>{{ $t('Could not load older notifications. Try again.', 'Hindi mai-load ang mga naunang abiso. Subukang muli.') }}</p>
    </div>
</div>
