{{-- ============================================================
     Tulong > Services rendered (welfare round, 2026-10-29): every service
     the offices recorded as given to a person - a Tulong case completed, a
     counseling session, a home visit, a referral, a session attended - from
     GET /admin/services-rendered, with its filters, a page at a time. A
     record is never deleted: it is voided with a reason and shown struck
     through. A confidential record (a sensitive service's, always) reaches
     only officers of its own office; the server leaves it out for others.
     Logic: servicesRendered() in resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $srInput = 'text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none';
    $srTh = 'px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted';
@endphp
<section x-show="view === 'services'" x-cloak aria-labelledby="sr-title"
         x-data="servicesRendered()" x-init="srInit()"
         x-effect="if (view === 'services' && $store.session.canPerm('tulong_records', 'view') && !srLoaded && !srLoading && !srFailed) srLoad(1)"
         x-on:welfare-records-changed.window="if (srLoaded) srLoad(srMeta.page)">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div class="min-w-0">
            <h2 id="sr-title" class="text-base font-bold text-ink">Services rendered</h2>
            <p class="text-xs text-ink-muted mt-0.5 max-w-2xl">What the offices gave each person: completed Tulong cases, counseling and home visits, referrals, and sessions attended. Confidential records show only to officers of their own office.</p>
        </div>
        <x-ui.button size="sm" icon="plus" x-show="srCanCreate()" @click="srRecord()">Record a service</x-ui.button>
    </div>

    <div x-show="!$store.session.canPerm('tulong_records', 'view')" class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-ink-muted">Your role does not include the Services Rendered Records.</div>

    <div x-show="$store.session.canPerm('tulong_records', 'view')">
        <p x-show="srPerson" x-cloak class="mb-3 text-[11px] text-brand-700 bg-brand-50 rounded-lg px-3 py-1.5 flex flex-wrap items-center gap-x-2">
            <i data-lucide="user" class="w-3.5 h-3.5"></i>
            <span x-text="'Showing the services given to ' + (srPerson?.name || '') + ((srPerson?.individual_ref || srPerson?.account_no) && srPerson?.name !== (srPerson?.individual_ref || srPerson?.account_no) ? ' (' + (srPerson?.individual_ref || srPerson?.account_no) + ')' : '') + '.'"></span>
            <button type="button" class="font-medium underline" @click="srClearPerson()">Show everyone</button>
        </p>

        <div class="surface-glass rounded-[26px] p-3 mb-4 flex flex-wrap items-end gap-2">
            <div class="relative flex-1 min-w-[180px]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" x-model="srF.q" @input.debounce.400ms="srLoad(1)" aria-label="Search by person, reference or details" placeholder="Person, reference or details…" class="{{ $srInput }} w-full pl-9">
            </div>
            <select x-model="srF.service_key" @change="srLoad(1)" aria-label="Service" class="{{ $srInput }} max-w-[220px]">
                <option value="">All services</option>
                <template x-for="t in srServices()" :key="t.key"><option :value="t.key" x-text="t.name" :selected="srF.service_key === t.key"></option></template>
            </select>
            <select x-model="srF.kind" @change="srLoad(1)" aria-label="Kind of service" class="{{ $srInput }}">
                <option value="">All kinds</option>
                <template x-for="k in srKinds" :key="k[0]"><option :value="k[0]" x-text="k[1]" :selected="srF.kind === k[0]"></option></template>
            </select>
            <label class="text-[11px] text-ink-muted flex items-center gap-1">From <input type="date" x-model="srF.from" @change="srLoad(1)" class="{{ $srInput }}"></label>
            <label class="text-[11px] text-ink-muted flex items-center gap-1">To <input type="date" x-model="srF.to" @change="srLoad(1)" class="{{ $srInput }}"></label>
            <select x-show="isMunicipalScope" x-model="srF.barangay" @change="srLoad(1)" aria-label="Barangay" class="{{ $srInput }}">
                <option value="">All barangays</option>
                <template x-for="b in barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b" :selected="srF.barangay === b"></option></template>
            </select>
            <label class="text-[11px] text-ink-muted flex items-center gap-1.5 px-1 py-2"><input type="checkbox" x-model="srF.include_voided" @change="srLoad(1)" class="rounded text-brand-600 focus:ring-brand-200"> Show voided</label>
            <button type="button" class="text-xs text-ink-muted hover:text-slate-700 px-1 py-2" x-show="srF.q || srF.service_key || srF.kind || srF.from || srF.to || srF.barangay || srF.include_voided" @click="srClear()">Clear</button>
        </div>

        <p class="sr-only" aria-live="polite" x-text="srLoading ? 'Loading the records' : (srLoaded ? srMeta.total + ' records' : '')"></p>

        <x-ui.table>
            <thead>
                <tr class="bg-well/70">
                    <th class="{{ $srTh }}">Date</th>
                    <th class="{{ $srTh }}">Person</th>
                    <th class="{{ $srTh }}">Service</th>
                    <th class="{{ $srTh }} hidden lg:table-cell">Details</th>
                    <th class="{{ $srTh }} hidden md:table-cell text-right">Amount</th>
                    <th class="{{ $srTh }} hidden xl:table-cell">By</th>
                    <th class="px-4 py-2.5"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <template x-for="r in srRows" :key="r.ref">
                <tbody class="border-t border-brand-600/[.08]">
                    <tr :class="r.voided_at ? 'opacity-60' : 'hover:bg-white/60'">
                        <td class="px-4 py-3 text-xs text-ink-muted whitespace-nowrap align-top">
                            <span :class="r.voided_at && 'line-through'" x-text="day(r.rendered_on)"></span>
                            <span class="block font-mono text-[10px] text-ink-faint" x-text="r.ref"></span>
                        </td>
                        <td class="px-4 py-3 align-top min-w-[9rem]">
                            <p class="text-sm font-medium text-slate-700" :class="r.voided_at && 'line-through'" x-text="r.person.name || '—'"></p>
                            <p class="text-[11px] text-ink-muted" x-text="[r.person.individual_ref || r.person.account_no, r.person.barangay ? 'Brgy. ' + r.person.barangay : ''].filter(Boolean).join(' · ')"></p>
                        </td>
                        <td class="px-4 py-3 align-top min-w-[10rem]">
                            <p class="text-sm text-slate-700" :class="r.voided_at && 'line-through'" x-text="r.service_name"></p>
                            <p class="flex flex-wrap items-center gap-1 mt-0.5">
                                <span class="text-[10px] font-semibold rounded-full px-1.5 py-0.5 bg-brand-50 text-brand-700" x-text="kindLabel(r.kind)"></span>
                                <span x-show="r.confidential" class="inline-flex items-center gap-0.5 text-[10px] font-semibold rounded-full px-1.5 py-0.5 bg-rose-50 text-rose-700"><i data-lucide="lock" class="w-2.5 h-2.5"></i>Confidential</span>
                                <span class="text-[10px] text-ink-muted" x-text="[sourceOf(r.source), r.office].filter(Boolean).join(' · ')"></span>
                            </p>
                            <button type="button" x-show="r.request_ref" @click="srCase(r)" class="text-[11px] font-medium text-brand-600 hover:underline" x-text="'Case ' + r.request_ref"></button>
                            <span x-show="r.session_ref" class="block text-[11px] text-ink-muted" x-text="'Session ' + r.session_ref"></span>
                        </td>
                        <td class="px-4 py-3 align-top hidden lg:table-cell max-w-[22rem]">
                            <p class="text-[11px] text-ink-muted" x-text="[modeLabel(r.mode), r.referral_source ? 'Referred by ' + sourceLabel(r.referral_source) : '', r.referred_to ? 'Referred to ' + r.referred_to : ''].filter(Boolean).join(' · ')"></p>
                            <p class="text-xs text-slate-600 line-clamp-2 whitespace-pre-line" x-text="r.details || ''"></p>
                            <p x-show="r.voided_at" class="text-[11px] text-rose-700 mt-0.5" x-text="'Voided ' + day(r.voided_at) + (r.void_reason ? ': ' + r.void_reason : '')"></p>
                        </td>
                        <td class="px-4 py-3 align-top hidden md:table-cell text-right text-xs font-semibold tabular-nums text-slate-700" x-text="peso(r.amount) || '—'"></td>
                        <td class="px-4 py-3 align-top hidden xl:table-cell text-xs text-ink-muted" x-text="r.rendered_by_name || '—'"></td>
                        <td class="px-4 py-3 align-top text-right">
                            <button type="button" x-show="srCanVoid() && !r.voided_at" @click="r._voiding = !r._voiding; r._reason = ''" :aria-expanded="r._voiding" class="text-xs font-medium text-rose-600 hover:underline">Void</button>
                        </td>
                    </tr>
                    <tr x-show="r.voided_at" class="lg:hidden"><td colspan="7" class="px-4 pb-2 text-[11px] text-rose-700" x-text="'Voided ' + day(r.voided_at) + (r.void_reason ? ': ' + r.void_reason : '')"></td></tr>
                    <tr x-show="r._voiding" x-cloak>
                        <td colspan="7" class="px-4 pb-3">
                            <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-3 space-y-2">
                                <label :for="'sr-void-' + r.ref" class="block text-[11px] font-semibold text-rose-800" x-text="'Void ' + r.ref + '? It stays on file, struck through, with your reason.'"></label>
                                <textarea :id="'sr-void-' + r.ref" x-model="r._reason" rows="2" maxlength="250" placeholder="e.g. Recorded on the wrong person" class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-rose-100"></textarea>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" :disabled="r._reason.trim().length < 5 || r._busy" @click="srVoid(r)" class="rounded-xl bg-rose-600 hover:bg-rose-700 px-2.5 py-1.5 text-[11px] font-medium text-white disabled:opacity-40 disabled:pointer-events-none" x-text="r._busy ? 'Voiding…' : 'Void the record'"></button>
                                    <button type="button" @click="r._voiding = false" class="text-[11px] font-medium text-ink-muted hover:text-slate-600">Keep it</button>
                                    <span x-show="r._reason.trim().length < 5" class="text-[10.5px] text-ink-faint">At least 5 characters.</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </template>
        </x-ui.table>

        <div x-show="srLoading && !srRows.length" class="space-y-2 mt-3"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /></div>
        <div x-show="srFailed" x-cloak class="mt-3"><x-ui.error-state messageExpr="'Could not load the services rendered.'" retryExpr="srLoad(srMeta.page)" :retryable="true" /></div>
        <div x-show="srLoaded && !srLoading && !srFailed && !srRows.length" x-cloak class="flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl mt-3">
            <span class="w-14 h-14 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-3"><i data-lucide="clipboard-list" class="w-6 h-6"></i></span>
            <p class="text-sm font-medium text-slate-600">No services on record for these filters.</p>
        </div>
        <div x-show="srLoaded && srMeta.total > srMeta.per_page" x-cloak class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-ink-muted">
            <span x-text="'Page ' + srMeta.page + ' of ' + srPages() + ' · ' + srMeta.total.toLocaleString() + ' records'"></span>
            <div class="flex gap-2">
                <x-ui.button size="sm" variant="secondary" icon="chevron-left" x-bind:disabled="srLoading || srMeta.page <= 1" @click="srLoad(srMeta.page - 1)">Previous</x-ui.button>
                <x-ui.button size="sm" variant="secondary" x-bind:disabled="srLoading || srMeta.page >= srPages()" @click="srLoad(srMeta.page + 1)">Next</x-ui.button>
            </div>
        </div>
    </div>
</section>
