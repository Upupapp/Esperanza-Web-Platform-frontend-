{{-- ============================================================
     Tulong > Sessions (welfare round, 2026-10-29): the group sessions of the
     session services - the Pre-Marriage Orientation, Pre-Marriage Counseling
     (Family Code art. 16) and the Parent Effectiveness Service - as Upcoming,
     Done and Cancelled (GET /admin/tulong/sessions?status=&service_key=).
     A row opens the session window (admin/tulong/session-desk), where its
     participants are added, their attendance marked, and the session
     finished with its certificates. Logic: sessionList() in
     resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
<section x-show="view === 'sessions'" x-cloak aria-labelledby="sl-title"
         x-data="sessionList()"
         x-effect="if (view === 'sessions' && $store.session.canPerm('tulong_sessions', 'view') && !slLoaded && !slLoading && !slFailed) slLoad()"
         x-on:welfare-session-changed.window="if (slLoaded) slLoad()">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div class="min-w-0">
            <h2 id="sl-title" class="text-base font-bold text-ink">Group sessions</h2>
            <p class="text-xs text-ink-muted mt-0.5 max-w-2xl">Schedule a session, add the cases waiting for one, mark who attended, then finish it: everyone whose case had full attendance receives the certificate in their account.</p>
        </div>
        <x-ui.button size="sm" icon="calendar-plus" x-show="slCanCreate()" @click="slNew()">New session</x-ui.button>
    </div>

    <div x-show="!$store.session.canPerm('tulong_sessions', 'view')" class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-ink-muted">Your role does not include Group Sessions &amp; Certificates.</div>

    <div x-show="$store.session.canPerm('tulong_sessions', 'view')">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit" role="group" aria-label="Which sessions">
                <template x-for="t in slTabs" :key="t[0]">
                    <button type="button" @click="slSetTab(t[0])" :aria-pressed="slTab === t[0]" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="slTab === t[0] ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="t[1]"></button>
                </template>
            </div>
            <select x-model="slService" @change="slLoad()" aria-label="Tulong type" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                <option value="">All Tulong types</option>
                <template x-for="t in slServices()" :key="t.key"><option :value="t.key" x-text="t.name" :selected="slService === t.key"></option></template>
            </select>
        </div>

        <p class="sr-only" aria-live="polite" x-text="slLoading ? 'Loading the sessions' : (slLoaded ? slRows.length + ' sessions' : '')"></p>
        <div x-show="slLoading && !slRows.length" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-16 opacity-80" /></div>
        <div x-show="slFailed" x-cloak><x-ui.error-state messageExpr="'Could not load the sessions.'" retryExpr="slLoad()" :retryable="true" /></div>
        <div x-show="slLoaded && !slLoading && !slFailed && !slRows.length" x-cloak class="flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl">
            <span class="w-14 h-14 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-3"><i data-lucide="calendar-days" class="w-6 h-6"></i></span>
            <p class="text-sm font-medium text-slate-600" x-text="slTab === 'Scheduled' ? 'No upcoming session.' : (slTab === 'Done' ? 'No finished session yet.' : 'No cancelled session.')"></p>
        </div>
        <ul x-show="slRows.length" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <template x-for="s in slRows" :key="s.ref">
                <li>
                    <button type="button" @click="slOpen(s)" class="w-full text-left surface-glass rounded-[22px] p-4 hover:shadow-glass transition-shadow flex items-start gap-3">
                        <span class="w-12 shrink-0 rounded-xl bg-brand-50 text-brand-700 text-center py-1.5">
                            <span class="block text-[10px] font-bold uppercase" x-text="day(s.date).split(' ')[0]"></span>
                            <span class="block text-lg font-extrabold leading-none" x-text="String(s.date || '').slice(8, 10)"></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-ink" x-text="s.title || s.service_name"></span>
                            <span class="block text-[11px] text-ink-muted" x-text="[s.service_name !== s.title ? s.service_name : '', day(s.date), time(s.start_time) + (s.end_time ? ' – ' + time(s.end_time) : ''), s.venue].filter(Boolean).join(' · ')"></span>
                            <span class="block text-[11px] text-ink-muted mt-0.5" x-text="slCount(s)"></span>
                        </span>
                        <span class="shrink-0 text-[10px] font-semibold rounded-full px-2 py-0.5 ring-1 ring-inset" :class="s.status === 'Done' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : (s.status === 'Cancelled' ? 'bg-slate-100 text-slate-600 ring-slate-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-200')" x-text="s.status"></span>
                    </button>
                </li>
            </template>
        </ul>
    </div>
</section>
