{{-- A session case's Session panel (welfare round, 2026-10-29): the session
     it is in (from the case detail's session), opening the session window;
     or "Waiting for a session" with Add to a session (the service's
     scheduled sessions). For pre-marriage counseling, whether Family Code
     art. 16 requires it; and the certificate once issued. Inside the Tulong
     case window; logic: caseSession() in resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling. --}}
<div x-data="caseSession()" x-show="csShown()" x-cloak class="rounded-xl border border-indigo-200 bg-indigo-50/30 p-3.5 mt-4" data-testid="case-session">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="calendar-days" class="w-3.5 h-3.5"></i> Session</p>
        <span x-show="csArt16()" class="text-[10.5px] font-semibold rounded-full px-2 py-0.5" :class="csArt16()?.required === true ? 'bg-rose-50 text-rose-700' : (csArt16()?.required === false ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700')" x-text="csArt16()?.required === true ? 'Required (Art. 16)' : (csArt16()?.required === false ? 'Optional' : 'Check ages (Art. 16)')"></span>
    </div>
    <p x-show="csArt16()" class="text-[11px] text-ink-muted mt-1" x-text="csArt16()?.text"></p>
    <template x-if="selected && selected.session">
        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs text-ink">
                <span class="font-semibold" x-text="day(selected.session.date) + ', ' + time(selected.session.start_time)"></span>
                <span class="text-ink-muted" x-text="' · ' + (selected.session.venue || '') + ' · ' + (selected.session.status || '')"></span>
            </p>
            <x-ui.button size="sm" variant="secondary" icon="calendar-days" @click="csOpen()">Open the session</x-ui.button>
        </div>
    </template>
    <div x-show="selected && !detailLoading && !selected.session && !['Completed', 'Rejected', 'Cancelled'].includes(selected.status)" class="mt-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs text-indigo-900 font-semibold">Waiting for a session</p>
            <x-ui.button size="sm" icon="calendar-plus" x-show="csCanAdd() && !csPicking" @click="csPick()">Add to a session</x-ui.button>
        </div>
        <div x-show="csPicking" x-cloak class="mt-2 space-y-1.5">
            <p x-show="csLoading" class="text-xs text-ink-muted">Loading the scheduled sessions…</p>
            <p x-show="!csLoading && !csOptions.length && !csError" class="text-xs text-ink-muted">No scheduled session of this service. Schedule one in the Sessions view.</p>
            <template x-for="s in csOptions" :key="s.ref">
                <button type="button" :disabled="csBusy" @click="csAdd(s)" class="w-full text-left rounded-xl border border-slate-200 bg-white/80 hover:border-brand-200 px-3 py-1.5 text-xs disabled:opacity-50">
                    <span class="font-semibold text-ink" x-text="(s.title || s.service_name) + ' · ' + day(s.date) + ', ' + time(s.start_time)"></span>
                    <span class="block text-[11px] text-ink-muted" x-text="[s.venue, (s.counts?.participants ?? 0) + (s.capacity ? ' of ' + s.capacity : '') + ' participants'].filter(Boolean).join(' · ')"></span>
                </button>
            </template>
            <button type="button" @click="csPicking = false" class="text-[11px] text-ink-muted hover:text-slate-700">Close</button>
        </div>
        <p x-show="csError" x-cloak class="mt-1.5 text-xs text-rose-700" role="alert" x-text="csError"></p>
    </div>
    <ul x-show="csCerts().length" x-cloak class="mt-2 space-y-1">
        <template x-for="d in csCerts()" :key="d.control_number">
            <li class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <span><span class="font-mono font-semibold text-ink" x-text="d.control_number"></span><span x-show="d.revoked" class="text-rose-700"> · Revoked</span></span>
                <button type="button" @click="csCert(d)" class="inline-flex items-center gap-1 font-semibold text-brand-600 hover:underline"><i data-lucide="award" class="w-3.5 h-3.5"></i>View certificate</button>
            </li>
        </template>
    </ul>
</div>
