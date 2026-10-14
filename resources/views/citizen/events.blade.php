<x-layouts.citizen title="Events" subtitle="Upcoming municipal and barangay events." active="events">
    <div
        x-data="{
            events: [],
            loading: false,
            failed: false,
            year: new Date().getFullYear(),
            month: new Date().getMonth() + 1,
            weeks: [],
            selectedDate: null,
            myBarangayOnly: false,
            categoryColor: {
                'Civic': 'bg-brand-400', 'Festival': 'bg-gold-400', 'Health': 'bg-emerald-400',
                'Sports': 'bg-purple-400', 'Government': 'bg-navy-400', 'Community': 'bg-sky-400',
                'Education': 'bg-teal-400', 'Disaster Preparedness': 'bg-rose-400',
            },
            get monthLabel() {
                return new Date(this.year, this.month - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            },
            get categories() {
                const seen = new Set(this.events.map((e) => e.category).filter(Boolean));
                return [...seen].map((name) => ({ name, color: this.categoryColor[name] || 'bg-slate-400' }));
            },
            buildWeeks() {
                const first = new Date(this.year, this.month - 1, 1);
                const daysInMonth = new Date(this.year, this.month, 0).getDate();
                const cells = [];
                for (let i = 0; i < first.getDay(); i++) cells.push(null);
                for (let d = 1; d <= daysInMonth; d++) {
                    const key = this.year + '-' + String(this.month).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                    cells.push({ day: d, key });
                }
                while (cells.length % 7 !== 0) cells.push(null);
                this.weeks = [];
                for (let i = 0; i < cells.length; i += 7) this.weeks.push(cells.slice(i, i + 7));
            },
            async load() {
                this.loading = true;
                this.failed = false;
                this.selectedDate = null;
                this.buildWeeks();
                try {
                    const monthParam = this.year + '-' + String(this.month).padStart(2, '0');
                    const result = await $api.getAll('/events', { params: { month: monthParam } });
                    this.events = (result.data || []).map((e) => {
                        const d = new Date(e.date + 'T00:00:00');
                        return {
                            ...e,
                            key: e.date,
                            title: e.title || e.name,
                            day: d.getDate(),
                            monthShort: d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase(),
                            dotColor: this.categoryColor[e.category] || 'bg-slate-400',
                        };
                    });
                } catch {
                    this.events = [];
                    this.failed = true;
                } finally {
                    this.loading = false;
                }
            },
            init() { this.load(); },
            eventsOn(dateKey) { return this.events.filter(e => e.key === dateKey); },
            get selectedEvents() {
                const base = this.selectedDate ? this.eventsOn(this.selectedDate) : this.events;
                if (!this.myBarangayOnly) return base;
                return base.filter(e => e.barangay === this.$store.citizenSession.account?.barangay);
            },
            selectDay(cell) {
                if (!cell) return;
                this.selectedDate = this.selectedDate === cell.key ? null : cell.key;
            },
            prevMonth() { this.month--; if (this.month < 1) { this.month = 12; this.year--; } this.load(); },
            nextMonth() { this.month++; if (this.month > 12) { this.month = 1; this.year++; } this.load(); },
        }"
        class="animate-fade-up grid grid-cols-1 lg:grid-cols-[380px_minmax(0,1fr)] gap-5 items-start"
    >
        <!-- Calendar -->
        <div class="surface-glass rounded-[26px] p-4">
            <!-- Legend: fixed at the very top so it never shifts position -->
            <div class="flex flex-wrap gap-x-3.5 gap-y-1.5 mb-3 pb-3 border-b border-brand-600/10" x-show="categories.length">
                <template x-for="c in categories" :key="c.name">
                    <span class="flex items-center gap-1.5 text-[11px] text-ink-muted">
                        <span class="w-2 h-2 rounded-full shrink-0" :class="c.color"></span>
                        <span x-text="c.name"></span>
                    </span>
                </template>
            </div>

            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-ink" x-text="monthLabel"></p>
                <div class="flex items-center gap-1">
                    <button aria-label="Previous month" type="button" @click="prevMonth()" class="w-7 h-7 rounded-xl flex items-center justify-center text-ink-muted hover:bg-slate-100 hover:text-slate-700 transition-colors">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>
                    <button aria-label="Next month" type="button" @click="nextMonth()" class="w-7 h-7 rounded-xl flex items-center justify-center text-ink-muted hover:bg-slate-100 hover:text-slate-700 transition-colors">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 mb-1.5">
                <template x-for="(d, i) in ['S','M','T','W','T','F','S']" :key="i">
                    <p class="text-center text-[10px] font-semibold uppercase text-ink-muted" x-text="d"></p>
                </template>
            </div>

            <template x-for="(week, wi) in weeks" :key="wi">
                <div class="grid grid-cols-7 gap-1 mb-1">
                    <template x-for="(cell, ci) in week" :key="ci">
                        <button
                            type="button"
                            @click="selectDay(cell)"
                            :disabled="!cell"
                            class="aspect-square rounded-xl flex flex-col items-center justify-center gap-0.5 text-xs transition-all duration-[160ms] ease-standard relative"
                            :class="!cell ? 'pointer-events-none' : (selectedDate === cell.key ? 'clay-blue font-semibold' : (eventsOn(cell.key).length ? 'text-ink font-medium hover:bg-brand-50' : 'text-ink-muted hover:bg-white/60'))"
                        >
                            <span x-show="cell" x-text="cell?.day"></span>
                            <span class="flex items-center gap-0.5" x-show="cell && eventsOn(cell.key).length">
                                <template x-for="(e, ei) in (cell ? eventsOn(cell.key) : []).slice(0, 3)" :key="ei">
                                    <span class="w-1 h-1 rounded-full" :class="selectedDate === cell?.key ? 'bg-white' : e.dotColor"></span>
                                </template>
                            </span>
                        </button>
                    </template>
                </div>
            </template>

            <div class="flex items-center justify-between mt-3 pt-3 border-t border-brand-600/10" x-show="selectedDate">
                <p class="text-[11px] text-ink-muted" x-text="$store.lang.current === 'en' ? 'Showing events for the selected date' : 'Ipinapakita ang mga kaganapan sa napiling petsa'"></p>
                <button type="button" @click="selectedDate = null" class="text-[11px] font-medium text-brand-600 hover:underline"><x-ui.t fil="I-clear" en="Clear" /></button>
            </div>
        </div>

        <!-- Event list -->
        <div class="space-y-3.5 min-w-0">
            <div class="flex items-center justify-between" x-show="$store.barangay.mine">
                <p class="text-xs text-ink-muted" x-text="selectedEvents.length + ' ' + ($store.lang.current === 'en' ? 'events' : 'kaganapan')"></p>
                <button
                    type="button" @click="myBarangayOnly = !myBarangayOnly"
                    class="inline-flex items-center gap-1.5 text-[11px] font-medium rounded-xl px-2.5 py-1.5 transition-colors"
                    :class="myBarangayOnly ? 'clay-blue' : 'bg-slate-100 text-ink-muted hover:bg-slate-200'"
                ><i data-lucide="map-pinned" class="w-3 h-3"></i><x-ui.t fil="Aking Barangay Lang" en="My Barangay Only" /></button>
            </div>

            <div x-show="loading" class="space-y-3">
                <x-ui.skeleton class="h-24 rounded-2xl" />
                <x-ui.skeleton class="h-24 rounded-2xl opacity-70" />
            </div>

            <div x-show="failed" x-cloak>
                <x-ui.error-state messageExpr="($store.lang.current === 'en' ? 'Could not load events.' : 'Hindi ma-load ang mga kaganapan.')" retryExpr="load()" :retryable="true" />
            </div>

            <template x-if="!loading && !failed">
                {{-- Remind me (scope round 2026-10-15): a calendar file with a
                     reminder the day before, or Google Calendar - the button
                     used to only say a reminder was set (eventCalendar in
                     resources/js/scope-citizen.js). --}}
                <div class="space-y-3.5" x-data="eventCalendar()" @keydown.escape.window="menu = null">
                    <template x-for="(event, i) in selectedEvents" :key="event.key + event.title">
                        <x-ui.card ::class="menu === event.key + event.title ? 'relative z-20' : ''">
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-4 gap-y-3">
                                <div class="w-14 h-14 rounded-xl bg-brand-50 text-brand-700 flex flex-col items-center justify-center shrink-0">
                                    <span class="text-lg font-bold leading-none" x-text="event.day"></span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide" x-text="event.monthShort"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-semibold text-ink" x-text="event.title"></p>
                                        <span class="text-[11px] font-medium text-brand-700 bg-brand-50 border border-brand-100 rounded-full px-2 py-0.5" x-text="event.category"></span>
                                        <span
                                            class="text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2 py-0.5 inline-flex items-center gap-1"
                                            x-show="event.barangay && event.barangay === $store.citizenSession.account?.barangay"
                                        ><i data-lucide="map-pinned" class="w-3 h-3"></i><x-ui.t fil="Sa Aking Barangay" en="In My Barangay" /></span>
                                    </div>
                                    <p class="text-xs text-ink-muted mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5"></i><span x-text="event.time"></span></span>
                                        <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i><span x-text="event.venue"></span></span>
                                    </p>
                                </div>
                                <div class="relative w-full sm:w-auto shrink-0" @click.outside="if (menu === event.key + event.title) menu = null">
                                    <button
                                        type="button"
                                        @click="toggle(event.key + event.title)"
                                        aria-haspopup="true"
                                        :aria-expanded="menu === event.key + event.title"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl px-3.5 py-2 text-xs font-medium bg-white text-slate-700 border border-slate-200 shadow-card hover:bg-white/60 hover:border-slate-300 transition-all duration-[160ms] ease-standard active:scale-[0.97]"
                                    >
                                        <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                                        <span x-text="$store.lang.current === 'en' ? 'Remind Me' : 'Paalalahanan Ako'"></span>
                                        <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <div
                                        x-show="menu === event.key + event.title"
                                        x-cloak
                                        x-transition:enter="transition duration-[160ms] ease-emphasized"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        role="menu"
                                        class="absolute right-0 mt-2 w-72 max-w-[calc(100vw-4rem)] surface-glass rounded-2xl shadow-float border border-white/90 p-1.5"
                                    >
                                        <button type="button" role="menuitem" @click="save(event)" class="w-full flex items-start gap-2.5 rounded-xl px-3 py-2 text-left hover:bg-white/70 transition-colors">
                                            <i data-lucide="calendar-days" class="w-4 h-4 text-brand-600 shrink-0 mt-0.5"></i>
                                            <span class="min-w-0">
                                                <span class="block text-xs font-semibold text-ink"><x-ui.t fil="I-save sa aking kalendaryo" en="Save to my calendar" /></span>
                                                <span class="block text-[11px] text-ink-muted"><x-ui.t fil="File na .ics, may paalala isang araw bago" en="An .ics file, with a reminder a day before" /></span>
                                            </span>
                                        </button>
                                        <a role="menuitem" :href="google(event)" target="_blank" rel="noopener" @click="menu = null" class="w-full flex items-start gap-2.5 rounded-xl px-3 py-2 text-left hover:bg-white/70 transition-colors">
                                            <i data-lucide="external-link" class="w-4 h-4 text-brand-600 shrink-0 mt-0.5"></i>
                                            <span class="min-w-0">
                                                <span class="block text-xs font-semibold text-ink"><x-ui.t fil="Idagdag sa Google Calendar" en="Add to Google Calendar" /></span>
                                                <span class="block text-[11px] text-ink-muted"><x-ui.t fil="Bubukas sa bagong tab" en="Opens in a new tab" /></span>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>
                    </template>

                    <div class="flex flex-col items-center text-center py-12 bg-white rounded-2xl border border-brand-600/10" x-show="selectedEvents.length === 0">
                        <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="calendar-x" class="w-7 h-7"></i></span>
                        <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang kaganapan sa buwang ito." en="No events this month." /></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.citizen>
